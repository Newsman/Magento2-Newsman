<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config\Product\GetAdditionalAttributes;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\ProductFactory as ProductHelperFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Get products feed
 */
class ProductsFeed extends AbstractRetriever
{
    public const DEFAULT_PAGE_SIZE = 1000;

    /**
     * Category separator
     */
    public const CATEGORY_SEPARATOR = '>';

    /**
     * Categories separator
     */
    public const CATEGORIES_SEPARATOR = '|';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductHelperFactory
     */
    protected $productHelperFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GetAdditionalAttributes
     */
    protected $getAdditionalAttributes;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param ProductHelperFactory $productHelperFactory
     * @param Logger $logger
     * @param GetAdditionalAttributes $getAdditionalAttributes
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        ProductHelperFactory $productHelperFactory,
        Logger $logger,
        GetAdditionalAttributes $getAdditionalAttributes,
        StockRegistryInterface $stockRegistry
    ) {
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productHelperFactory = $productHelperFactory;
        $this->logger = $logger;
        $this->getAdditionalAttributes = $getAdditionalAttributes;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $websiteIds = [];
        foreach ($storeIds as $storeId) {
            $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
        }
        $websiteIds = array_unique($websiteIds);

        if (isset($data['product_id']) && !is_array($data['product_id'])) {
            if (empty($data['product_id'])) {
                return [];
            }

            $oneStoreId = null;
            if (!empty($storeIds)) {
                $oneStoreId = current($storeIds);
            }

            $this->logger->info(__('Export product feed %1, store ID %2', $data['product_id'], $oneStoreId));
            $product = $this->productRepository->getById($data['product_id'], false, $oneStoreId);
            $result = [$this->processProduct($product, $websiteIds, [$oneStoreId])];
            $this->logger->info(__('Exported product feed %1, store ID %2', $data['product_id'], $oneStoreId));
            return $result;
        }

        $params = $this->processListParameters($data, self::DEFAULT_PAGE_SIZE);

        $this->logger->info(
            __(
                'Export products feed %1, %2, storeIDs %3',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds)
            )
        );

        $collection = $this->createCollection($websiteIds, $storeIds, $params);

        $count = $collection->getSize();
        $result = [];
        $pageOffset = $params['currentPage'] * $params['limit'];
        $prevPageOffset = ($params['currentPage'] - 1) * $params['limit'];
        if (($count >= $pageOffset)
            || (($count < $pageOffset) && ($count > $prevPageOffset))
        ) {
            /** @var Product $product */
            foreach ($collection as $product) {
                try {
                    $result[] = $this->processProduct($product, $websiteIds, $storeIds);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $this->logger->info(
            __(
                'Exported products feed %1, %2, storeIDs %3: %4',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
    }

    /**
     * Map product data into an export row.
     *
     * @param Product|ProductInterface $product
     * @param array $websiteIds
     * @param array $storeIds
     * @return array
     */
    public function processProduct($product, $websiteIds, $storeIds)
    {
        $imageUrl = $this->productHelperFactory->create()
            ->getImageUrl($product);

        $priceInfo = $product->getPriceInfo();
        $price = $priceInfo->getPrice('final_price')->getValue();
        $oldPrice = $priceInfo->getPrice('regular_price')->getValue();
        $specialPrice = $priceInfo->getPrice('special_price')->getValue();
        $regularPrice = $priceInfo->getPrice('regular_price')->getValue();

        $row = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'stock_quantity' => (int)$this->getProductQuantity($product, $websiteIds),
            'price' => (float) $price,
            'image_url' => $imageUrl,
            'url' => $product->getProductUrl()
        ];

        if ((!empty($specialPrice) && $specialPrice < $regularPrice) || $price < $regularPrice) {
            $row['price_full'] = (float) $regularPrice;
            $row['price_discount'] = (float) $price;
            unset($row['price']);
        }

        // Categories
        $categoryCollection = $product->getCategoryCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('path');

        $categories = [];

        foreach ($categoryCollection as $category) {
            $pathIds = explode('/', $category->getPath());
            // Remove root categories (usually first 2 in Magento)
            if (count($pathIds) > 2) {
                $categories[] = $category->getName();
            }
        }

        if (!empty($categories)) {
            $row['category'] = $categories[0];
            $row['subcategories'] = implode(' ' . self::CATEGORIES_SEPARATOR . ' ', $categories);
        } else {
            $row['category'] = '';
            $row['subcategories'] = '';
        }

        // Stock status
        $row['in_stock'] = ($row['stock_quantity'] > 0) ? 1 : 0;
        $row['variants'] = '';

        foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $fieldName) {
            $row[$fieldName] = $product->getResource()
                ->getAttribute($attributeCode)
                ->getFrontend()
                ->getValue($product);

            if ($row[$fieldName] === false) {
                $row[$fieldName] = '';
            }
        }

        return $row;
    }

    /**
     * Retrieve current quantity for a product.
     *
     * @param Product|ProductInterface $product
     * @param array $websiteIds
     * @return float
     */
    public function getProductQuantity($product, $websiteIds)
    {
        $maxQty = 0.0;
        foreach ($websiteIds as $websiteId) {
            $stockStatus = $this->stockRegistry->getStockStatusBySku(
                $product->getSku(),
                $websiteId
            );

            if ((is_object($stockStatus) || is_array($stockStatus))
                && isset($stockStatus['qty']) && $stockStatus['qty'] > $maxQty) {
                $maxQty = $stockStatus['qty'];
            }
        }

        return (float) $maxQty;
    }

    /**
     * Create product collection for export.
     *
     * @param array $websiteIds
     * @param array $storeIds
     * @param array $params
     * @return Collection
     * @throws LocalizedException
     */
    public function createCollection($websiteIds, $storeIds, $params)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        // Get out of stock products too
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addAttributeToSelect(['*'])
            ->addWebsiteFilter($websiteIds);

        $this->applyFiltersToCollection($collection, $params);

        if (!empty($additionalAttributes)) {
            $collection->addAttributeToSelect(array_keys($additionalAttributes));
        }

        return $this->processCollection($collection, $websiteIds, $storeIds);
    }

    /**
     * Hook for 3rd party modules to modify the product collection.
     *
     * @param Collection $collection
     * @param array $websiteIds
     * @param array $storeIds
     * @return Collection
     */
    public function processCollection($collection, $websiteIds, $storeIds)
    {
        return $collection;
    }

    /**
     * Retrieve additional attributes mapping for the given store IDs.
     *
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalAttributes($storeIds)
    {
        return $this->getAdditionalAttributes->get($storeIds);
    }

    /**
     * Get allowed request parameters
     *
     * @return array
     */
    public function getWhereParametersMapping()
    {
        return [
            'created_at' => [
                'field' => 'created_at',
                'multiple' => false,
            ],
            'modified_at' => [
                'field' => 'updated_at',
                'multiple' => false,
            ],
            'product_id' => [
                'field' => 'entity_id',
                'multiple' => false,
            ],
            'product_ids' => [
                'field' => 'entity_id',
                'multiple' => true,
            ],
        ];
    }

    /**
     * Get allowed sort fields
     *
     * @return array
     */
    public function getAllowedSortFields()
    {
        return [
            'created_at' => 'created_at',
            'modified_at' => 'updated_at',
            'product_id' => 'entity_id',
            'name' => 'name',
            'price' => 'price'
        ];
    }
}

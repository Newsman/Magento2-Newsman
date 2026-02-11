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
use \Magento\Catalog\Api\Data\ProductInterface;
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
 * Get products or a product
 */
class Products implements RetrieverInterface
{
    public const DEFAULT_PAGE_SIZE = 1000;

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

        if (isset($data['product_id'])) {
            if (empty($data['product_id'])) {
                return [];
            }

            $oneStoreId = null;
            if (!empty($storeIds)) {
                $oneStoreId = current($storeIds);
            }

            $this->logger->info(__('Export product %1, store ID %2', $data['product_id'], $oneStoreId));
            $product = $this->productRepository->getById($data['product_id'], $oneStoreId);
            $result = [$this->processProduct($product, $websiteIds, [$oneStoreId])];
            $this->logger->info(__('Exported product %1, store ID %2', $data['product_id'], $oneStoreId));
            return $result;
        }

        $pageSize = self::DEFAULT_PAGE_SIZE;
        if (!empty($data['limit']) && (int) $data['limit'] > 0) {
            $pageSize = (int) $data['limit'];
        }
        $start = (!empty($data['start']) && (int) $data['start'] >= 0) ? (int) $data['start'] : 0;
        $currentPage = (int) floor($start / $pageSize) + 1;

        $this->logger->info(
            __(
                'Export products %1, %2, storeIDs %3',
                $currentPage,
                $pageSize,
                implode(",", $storeIds)
            )
        );

        $collection = $this->createCollection($websiteIds, $storeIds, $currentPage, $pageSize);

        $count = $collection->getSize();
        $result = [];
        if (($count >= $currentPage * $pageSize)
            || (($count < $currentPage * $pageSize) && ($count > ($currentPage - 1) * $pageSize))
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
                'Exported products %1, %2, storeIDs %3: %4',
                $currentPage,
                $pageSize,
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

        if (!empty($specialPrice)) {
            if (empty($regularPrice) || $regularPrice == 0) {
                $oldPrice = $specialPrice;
            } else {
                $price = $specialPrice;
                $oldPrice = $regularPrice;
            }
        }

        $row = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'stock_quantity' => $this->getProductQuantity($product, $websiteIds),
            'price' => (float) $price,
            'price_old' => (float) $oldPrice,
            'image_url' => $imageUrl,
            'url' => $product->getProductUrl()
        ];

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
     * @param int $currentPage
     * @param int $pageSize
     * @return Collection
     * @throws LocalizedException
     */
    public function createCollection($websiteIds, $storeIds, $currentPage, $pageSize)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        // Get out of stock products too
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addAttributeToSelect(['*'])
            ->addWebsiteFilter($websiteIds)
            ->setCurPage($currentPage)
            ->setPageSize($pageSize);

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
}

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
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\ProductFactory as ProductHelperFactory;

/**
 * Get products or a product
 */
class Products implements RetrieverInterface
{
    public const DEFAULT_PAGE_SIZE = 1000;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

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
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param ProductHelperFactory $productHelperFactory
     * @param Logger $logger
     * @param GetAdditionalAttributes $getAdditionalAttributes
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        ProductHelperFactory $productHelperFactory,
        Logger $logger,
        GetAdditionalAttributes $getAdditionalAttributes
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productHelperFactory = $productHelperFactory;
        $this->logger = $logger;
        $this->getAdditionalAttributes = $getAdditionalAttributes;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $oneStoreId = null;
        if (!empty($storeIds)) {
            $oneStoreId = current($storeIds);
        }

        if (isset($data['product_id'])) {
            if (empty($data['product_id'])) {
                return [];
            }
            $this->logger->info(__('Export product %1, store ID %2', $data['product_id'], $oneStoreId));
            $product = $this->productRepository->getById($data['product_id'], $oneStoreId);
            $result = [$this->processProduct($product, [$oneStoreId])];
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

        $websiteIds = [];
        foreach ($storeIds as $storeId) {
            $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
        }
        $websiteIds = array_unique($websiteIds);

        $websitesFilter = $this->filterBuilder
            ->setField('website_id')
            ->setValue(implode(",", $websiteIds))
            ->create();

        if ($oneStoreId) {
            $storeFilter = $this->filterBuilder
                ->setField('store_id')
                ->setValue($oneStoreId)
                ->create();
        }

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaBuilder->setPageSize($pageSize)
            ->setCurrentPage($currentPage);

        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteriaBuilder->addFilters([$websitesFilter]);
        if ($oneStoreId) {
            $searchCriteriaBuilder->addFilters([$storeFilter]);
        }

        $searchCriteria = $searchCriteriaBuilder->create();

        $count = $this->productRepository->getList($searchCriteria)->getTotalCount();
        $result = [];
        if (($count >= $currentPage * $pageSize)
            || (($count < $currentPage * $pageSize) && ($count > ($currentPage - 1) * $pageSize))
        ) {
            $products = $this->productRepository->getList($searchCriteria)->getItems();
            /** @var ProductInterface $product */
            foreach ($products as $product) {
                try {
                    $result[] = $this->processProduct($product, [$oneStoreId]);
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
     * @param ProductInterface|Product $product
     * @param array $storeIds
     * @return array
     */
    public function processProduct($product, $storeIds)
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
            'stock_quantity' => (float) $product->getQty(),
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
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalAttributes($storeIds)
    {
        return $this->getAdditionalAttributes->get($storeIds);
    }
}

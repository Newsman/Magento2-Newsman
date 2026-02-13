<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use DateTime;
use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Product\AttributeValue;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Area;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\ProductFactory as ProductHelperFactory;

/**
 * Get orders or an order
 */
class Orders extends AbstractRetriever
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
     * @var SortOrderBuilderFactory
     */
    protected $sortOrderBuilderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var AttributeValue
     */
    protected $attributeValue;

    /**
     * @var ImageFactory
     */
    protected $imageHelperFactory;

    /**
     * @var Emulation
     */
    protected $appEmulation;

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
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $imageId;

    /**
     * @var bool
     */
    protected $isAddTelephone = false;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param AttributeValue $attributeValue
     * @param ImageFactory $imageHelperFactory
     * @param Emulation $appEmulation
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param ProductHelperFactory $productHelperFactory
     * @param Config $config
     * @param Logger $logger
     * @param string $imageId
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        OrderRepositoryInterface $orderRepository,
        AttributeValue $attributeValue,
        ImageFactory $imageHelperFactory,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        ProductHelperFactory $productHelperFactory,
        Config $config,
        Logger $logger,
        $imageId = ''
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->orderRepository = $orderRepository;
        $this->attributeValue = $attributeValue;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productHelperFactory = $productHelperFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->imageId = $imageId;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $this->isAddTelephone = $this->config->isOrderSendTelephoneByStoreIds($storeIds);

        if (isset($data['order_id']) && !is_array($data['order_id'])) {
            if (empty($data['order_id'])) {
                return [];
            }
            $this->logger->info(__('Export order %1', $data['order_id']));
            $order = $this->orderRepository->get($data['order_id']);
            $result = [$this->processOrder($order)];
            $this->logger->info(__('Exported order %1', $data['order_id']));
            return $result;
        }

        $params = $this->processListParameters($data, self::DEFAULT_PAGE_SIZE);

        $this->logger->info(
            __(
                'Export orders %1, %2, storeIds %3',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds)
            )
        );

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $this->applyFiltersToSearchCriteria(
            $searchCriteriaBuilder,
            $this->filterBuilder,
            $this->sortOrderBuilderFactory,
            $params
        );

        $searchCriteriaBuilder->addFilters([
            $this->filterBuilder
                ->setField('store_id')
                ->setConditionType('in')
                ->setValue($storeIds)
                ->create()
        ]);

        $afterDate = $this->config->getOrderAfterDate();
        if (!empty($afterDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $afterDate)) {
            $searchCriteriaBuilder->addFilters([
                $this->filterBuilder
                    ->setField('created_at')
                    ->setConditionType('gteq')
                    ->setValue($afterDate . ' 00:00:00')
                    ->create()
            ]);
        }

        $searchCriteria = $searchCriteriaBuilder->create();

        $result = [];
        $orderList = $this->orderRepository->getList($searchCriteria);
        $count = $orderList->getTotalCount();

        $pageOffset = $params['currentPage'] * $params['limit'];
        $prevPageOffset = ($params['currentPage'] - 1) * $params['limit'];
        if (($count >= $pageOffset)
            || (($count < $pageOffset) && ($count > $prevPageOffset))
        ) {
            $orders = $orderList->getItems();
            /** @var OrderInterface $order */
            foreach ($orders as $order) {
                try {
                    $result[] = $this->processOrder($order);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $this->logger->info(
            __(
                'Exported orders %1, %2, store IDs %3: %4',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
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
            'order_id' => [
                'field' => 'entity_id',
                'multiple' => false,
            ],
            'order_ids' => [
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
            'order_id' => 'entity_id',
        ];
    }

    /**
     * Map order data into an export row.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function processOrder($order)
    {
        $currentStoreId = $this->storeManager->getStore()->getId();

        $products = [];
        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $storeId = $item->getStoreId();
            $productId = $item->getProductId();

            try {
                if ($currentStoreId != $storeId) {
                    $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                }

                $url = $this->attributeValue->getUrl($productId, $storeId);

                $product = $this->productFactory->create()
                    ->setId($productId)
                    ->setStoreId($storeId)
                    ->setImage($this->attributeValue->getValue($productId, 'image', $storeId));

                if (!empty($this->imageId)) {
                    $product->setSmallImage($this->attributeValue->getValue($productId, 'small_image', $storeId))
                        ->setThumbnail($this->attributeValue->getValue($productId, 'thumbnail', $storeId));

                    $imageUrl = $this->imageHelperFactory->create()
                        ->init($product, $this->imageId)
                        ->getUrl();
                } else {
                    $imageUrl = $this->productHelperFactory->create()
                        ->getImageUrl($product);
                }

                if ($currentStoreId != $storeId) {
                    $this->appEmulation->stopEnvironmentEmulation();
                }
            } catch (\Exception $e) {
                if ($currentStoreId != $storeId) {
                    $this->appEmulation->stopEnvironmentEmulation();
                }
                throw $e;
            }

            $products[] = [
                'id' => $productId,
                'name' => $item->getName(),
                'quantity' => (int) $item->getQtyOrdered(),
                'unit_price' => (float) $item->getBasePriceInclTax(),
            ];
        }

        $billingPhone = '';
        if ($this->isAddTelephone) {
            $billingPhone = $order->getBillingAddress()->getTelephone();
        }

        $result = [
            'id' => $order->getId(),
            'billing_name' => trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()),
            'billing_company_name' => $order->getBillingAddress()->getCompany(),
            'billing_phone' => $billingPhone,
            'customer_email' => $order->getCustomerEmail(),
            'shipping_amount' => (float)$order->getBaseShippingInclTax(),
            'tax_amount' => (float)$order->getBaseTaxAmount(),
            'total_amount' => (float)$order->getBaseGrandTotal(),
            'currency' => $order->getBaseCurrencyCode(),
            'subtotal_amount' => (float)$order->getBaseSubtotalInclTax(),
            'discount' => abs($order->getBaseDiscountAmount()),
            'discount_code' => $order->getCouponCode(),
            'status' => $order->getStatus(),
            'date_created' => $order->getCreatedAt(),
            'date_modified' => $order->getUpdatedAt(),
            'products' => $products
        ];

        return $result;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel\Checkout;

use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\ViewModel\Marketing;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Checkout purchase view model
 */
class Purchase extends Marketing
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Registry $registry
     * @param SerializerInterface $serializer
     * @param Escaper $escaper
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        Registry $registry,
        SerializerInterface $serializer,
        Escaper $escaper,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($storeManager, $config, $registry, $serializer, $escaper);
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve marketing data for the given order IDs.
     *
     * @see https://kb.newsman.ro/remarketing-javascript-api-pentru-dezvoltatori/
     *
     * @param array $orderIds
     * @return array
     */
    public function getMarketingData($orderIds)
    {
        $data = [];

        if (empty($orderIds) || !is_array($orderIds)) {
            return $data;
        }
        $this->searchCriteriaBuilder->addFilter(
            'entity_id',
            $orderIds,
            'in'
        );
        $collection = $this->orderRepository->getList($this->searchCriteriaBuilder->create());

        foreach ($collection->getItems() as $order) {
            $productsData = [];
            /** @var Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                if (!($item->getProduct() instanceof Product && $item->getProduct()->getId() > 0)) {
                    continue;
                }

                $productData = [
                    'id' => $this->escapeValue($item->getProduct()->getSku()),
                    'name' => $this->escapeValue($item->getName()),
                    'price' => number_format((float) $item->getPrice(), 2, '.', ''),
                    'quantity' => $item->getQtyOrdered()
                ];
                $brandCode = $this->config->getBrandAttribute();
                if (!empty($brandCode) && !empty($item->getProduct()->getData($brandCode))) {
                    $productData['brand'] = $this->escapeValue($item->getProduct()->getAttributeText($brandCode));
                }

                $productsData[] = $productData;
            }

            $orderData = [
                'order' => [
                    'id' => $order->getIncrementId(),
                    'affiliation' => $this->escapeValue($this->storeManager->getStore()->getFrontendName()),
                    'revenue' => number_format((float) $order->getGrandTotal(), 2, '.', ''),
                    'tax' => number_format((float) $order->getTaxAmount(), 2, '.', ''),
                    'shipping' => number_format((float) $order->getShippingAmount(), 2, '.', ''),
                    'currency' => $order->getOrderCurrencyCode()
                ],
                'products' => $productsData,
                'buyer' => [
                    'email' => $order->getCustomerEmail(),
                    'first_name' => $order->getCustomerFirstname(),
                    'last_name' => $order->getCustomerLastname()
                ]
            ];

            if ($this->config->getConfig()->isOrderSendTelephone()) {
                $phone = '';
                if ($order->getBillingAddress() && $order->getBillingAddress()->getTelephone()) {
                    $phone = $order->getBillingAddress()->getTelephone();
                } elseif ($order->getShippingAddress() && $order->getShippingAddress()->getTelephone()) {
                    $phone = $order->getShippingAddress()->getTelephone();
                }
                if (!empty($phone)) {
                    $orderData['buyer']['phone'] = $phone;
                }
            }

            $data[] = $orderData;
        }

        return $data;
    }
}

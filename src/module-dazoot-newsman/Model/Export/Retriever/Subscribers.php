<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Config\Customer\GetAdditionalAttributes;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get subscribers
 */
class Subscribers implements RetrieverInterface
{
    public const DEFAULT_PAGE_SIZE = 100000;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var GetAdditionalAttributes
     */
    protected $getAdditionalAttributes;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $isAddTelephone = false;

    /**
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Config $config
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        CustomerCollectionFactory $customerCollectionFactory,
        GetAdditionalAttributes $getAdditionalAttributes,
        Config $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->getAdditionalAttributes = $getAdditionalAttributes;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $this->isAddTelephone = $this->config->isCustomerSendTelephoneByStoreIds($storeIds);

        $pageSize = false;
        $currentPage = false;
        if (isset($data['start']) && isset($data['limit'])) {
            $pageSize = self::DEFAULT_PAGE_SIZE;
            if (!empty($data['limit']) && (int) $data['limit'] > 0) {
                $pageSize = (int) $data['limit'];
            }
            $start = (!empty($data['start']) && (int) $data['start'] >= 0) ? (int) $data['start'] : 0;
            $currentPage = (int) floor($start / $pageSize) + 1;
        }

        $this->logger->info(
            __('Export subscribers %1, %2, store IDs %3', $currentPage, $pageSize, implode(",", $storeIds))
        );

        $collection = $this->createSubscriberCollection($storeIds, $pageSize, $currentPage);
        $result = [];
        if ($pageSize !== false && $currentPage !== false) {
            $collection->setPageSize($pageSize);
            $collection->setCurPage($currentPage);

            $count = $collection->getSize();
            if (($count >= $currentPage * $pageSize)
                || (($count < $currentPage * $pageSize) && ($count > ($currentPage - 1) * $pageSize))
            ) {
                $emails = $collection->getColumnValues('subscriber_email');
                $customerCollection = $this->createCustomerCollection($storeIds, $emails);
                $customersData = $this->getCustomerData($customerCollection, $storeIds);

                /** @var Subscriber $subscriber */
                foreach ($collection as $subscriber) {
                    try {
                        $result[] = $this->processCustomer($subscriber, $customersData, $storeIds);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        } else {
            $emails = $collection->getColumnValues('subscriber_email');
            $customerCollection = $this->createCustomerCollection($storeIds, $emails);
            $customersData = $this->getCustomerData($customerCollection, $storeIds);

            /** @var Subscriber $subscriber */
            foreach ($collection as $subscriber) {
                try {
                    $result[] = $this->processCustomer($subscriber, $customersData, $storeIds);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $this->logger->info(
            __(
                'Exported subscribers %1, %2, store IDs %3: %4',
                $currentPage,
                $pageSize,
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
    }

    /**
     * Create subscriber collection
     *
     * @param $storeIds
     * @param $pageSize
     * @param $currentPage
     * @return Collection
     */
    public function createSubscriberCollection($storeIds, $pageSize, $currentPage)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED);
        if (!empty($storeIds)) {
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }

        return $this->processSubscriberCollection($collection, $storeIds, $pageSize, $currentPage);
    }

    /**
     * Process subscriber collection for 3rd party plugins
     *
     * @param Collection $collection
     * @param array $storeIds
     * @param int $pageSize
     * @param int $currentPage
     * @return Collection
     */
    public function processSubscriberCollection($collection, $storeIds, $pageSize, $currentPage)
    {
        return $collection;
    }

    /**
     * @param CustomerCollection $collection
     * @return array
     * @throws LocalizedException
     */
    public function getCustomerData($collection, $storeIds)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        $customersData = [];
        /** @var Customer $customer */
        foreach ($collection as $customer) {
            $email = $customer->getEmail();
            $customersData[$email] = [
                'entity_id' => $customer->getId(),
                'email' => $email,
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
            ];

            if ($this->isAddTelephone) {
                $customersData[$email]['billing_telephone'] = '';
                $customersData[$email]['telephone'] = '';
                $billingAddress = $customer->getPrimaryBillingAddress();
                if ($billingAddress && $billingAddress->getTelephone()) {
                    $customersData[$email]['telephone'] = $billingAddress->getTelephone();
                    $customersData[$email]['billing_telephone'] = $billingAddress->getTelephone();
                }

                $customersData[$email]['shipping_telephone'] = '';
                $shippingAddress = $customer->getPrimaryShippingAddress();
                if ($shippingAddress && $shippingAddress->getTelephone()) {
                    $customersData[$email]['shipping_telephone'] = $shippingAddress->getTelephone();
                }
            }

            foreach ($additionalAttributes as $attributeCode => $field) {
                $customersData[$email][$attributeCode] = $customer->getResource()
                    ->getAttribute($attributeCode)
                    ->getFrontend()
                    ->getValue($customer);
                if ($customersData[$email][$attributeCode] === false) {
                    $customersData[$email][$attributeCode] = '';
                }
            }
        }

        return $customersData;
    }

    /**
     * Create customer collection
     *
     * @param array $storeIds
     * @param array $emails
     * @return CustomerCollection
     * @throws LocalizedException
     */
    public function createCustomerCollection($storeIds, $emails)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        /** @var CustomerCollection $collection */
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname'])
            ->addAttributeToSelect('email', ['in' => $emails])
            ->addAttributeToFilter('store_id', ['in' => $storeIds]);

        if (!empty($additionalAttributes)) {
            $collection->addAttributeToSelect(array_keys($additionalAttributes));
        }

        return $this->processCustomerCollection($collection, $storeIds, $emails);
    }

    /**
     * Process customer collection for 3rd party plugins
     *
     * @param CustomerCollection $collection
     * @param array $storeIds
     * @param array $emails
     * @return CustomerCollection
     */
    public function processCustomerCollection($collection, $storeIds, $emails)
    {
        return $collection;
    }

    /**
     * @param Subscriber $subscriber
     * @param array $customersData
     * @param array $storeIds
     * @return array
     */
    public function processCustomer($subscriber, $customersData, $storeIds)
    {
        $email = $subscriber->getSubscriberEmail();
        $row = [
            'email' => $email,
            'firstname' => '',
            'lastname' => ''
        ];

        foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $field) {
            $row[$field] = '';
        }

        if (!isset($customersData[$email])) {
            return $row;
        }

        $cdata = $customersData[$email];
        if (isset($cdata['entity_id'])) {
            unset($cdata['entity_id']);
        }

        if (isset($cdata['firstname'])) {
            $row['firstname'] = $cdata['firstname'];
        }
        if (isset($cdata['lastname'])) {
            $row['lastname'] = $cdata['lastname'];
        }

        foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $field) {
            $row[$field] = isset($cdata[$attributeCode]) ? $cdata[$attributeCode] : '';
        }

        if ($this->isAddTelephone) {
            $row['telephone'] = isset($cdata['billing_telephone']) ? $cdata['billing_telephone'] : '';
            $row['billing_telephone'] = isset($cdata['billing_telephone']) ? $cdata['billing_telephone'] : '';
            $row['shipping_telephone'] = isset($cdata['shipping_telephone']) ? $cdata['shipping_telephone'] : '';
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

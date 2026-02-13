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
class Subscribers extends AbstractRetriever
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
     * @param GetAdditionalAttributes $getAdditionalAttributes
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

        $params = $this->processListParameters($data, self::DEFAULT_PAGE_SIZE);

        $this->logger->info(
            __(
                'Export subscribers %1, %2, store IDs %3',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds)
            )
        );

        $collection = $this->createSubscriberCollection($storeIds, $params);
        $result = [];

        $count = $collection->getSize();
        $pageOffset = $params['currentPage'] * $params['limit'];
        $prevPageOffset = ($params['currentPage'] - 1) * $params['limit'];
        if (($count >= $pageOffset)
            || (($count < $pageOffset) && ($count > $prevPageOffset))
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

        $this->logger->info(
            __(
                'Exported subscribers %1, %2, store IDs %3: %4',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
    }

    /**
     * Create subscriber collection for export.
     *
     * @param array $storeIds
     * @param array $params
     * @return Collection
     */
    public function createSubscriberCollection($storeIds, $params)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED);
        if (!empty($storeIds)) {
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }

        $this->applyFiltersToCollection($collection, $params);

        return $this->processSubscriberCollection($collection, $storeIds, $params);
    }

    /**
     * Hook for 3rd party modules to modify the subscriber collection.
     *
     * @param Collection $collection
     * @param array $storeIds
     * @param array $params
     * @return Collection
     */
    public function processSubscriberCollection($collection, $storeIds, $params)
    {
        return $collection;
    }

    /**
     * Get allowed request parameters
     *
     * @return array
     */
    public function getWhereParametersMapping()
    {
        return [
            'subscriber_id' => [
                'field' => 'subscriber_id',
                'multiple' => false,
            ],
            'subscriber_ids' => [
                'field' => 'subscriber_id',
                'multiple' => true,
            ],
            'email' => [
                'field' => 'subscriber_email',
                'multiple' => false,
            ],
            'customer_id' => [
                'field' => 'customer_id',
                'multiple' => false,
            ],
            'status' => [
                'field' => 'subscriber_status',
                'multiple' => false,
            ],
            'modified_at' => [
                'field' => 'change_status_at',
                'multiple' => false,
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
            'email' => 'subscriber_email',
            'subscriber_id' => 'subscriber_id',
            'modified_at' => 'change_status_at',
        ];
    }

    /**
     * Extract relevant customer data from the collection.
     *
     * @param CustomerCollection $collection
     * @param array $storeIds
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
     * Create customer collection for mapping data to subscribers.
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
     * Hook for 3rd party modules to modify the customer collection.
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
     * Map subscriber and customer data into an export row.
     *
     * @param Subscriber $subscriber
     * @param array $customersData
     * @param array $storeIds
     * @return array
     */
    public function processCustomer($subscriber, $customersData, $storeIds)
    {
        $email = $subscriber->getSubscriberEmail();
        $row = [
            'subscriber_id' => $subscriber->getId(),
            'email' => $email,
            'firstname' => '',
            'lastname' => '',
            'confirmed' => 1,
            'source' => 'Magento2 subscribers'
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
            $row['phone'] = isset($cdata['telephone']) ? $cdata['telephone'] : '';
        }

        return $row;
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

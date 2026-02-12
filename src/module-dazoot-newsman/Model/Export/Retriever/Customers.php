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
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CollectionFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get customers
 */
class Customers extends AbstractRetriever
{
    public const DEFAULT_PAGE_SIZE = 1000;

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
     * @param GetAdditionalAttributes $getAdditionalAttributes
     * @param Config $config
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        GetAdditionalAttributes $getAdditionalAttributes,
        Config $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
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
                'Export customers %1, %2, store IDs %3',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds)
            )
        );

        $websiteIds = [];
        foreach ($storeIds as $storeId) {
            $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
        }
        $websiteIds = array_unique($websiteIds);
        $collection = $this->createCollection($websiteIds, $storeIds, $params);

        $count = $collection->getSize();
        $result = [];

        if (($count >= $params['currentPage'] * $params['limit'])
            || (($count < $params['currentPage'] * $params['limit']) && ($count > ($params['currentPage'] - 1) * $params['limit']))
        ) {
            /** @var CustomerInterface $customer */
            foreach ($collection as $customer) {
                try {
                    $result[] = $this->processCustomer($customer, $storeIds);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $this->logger->info(
            __(
                'Exported customers %1, %2, store IDs %3: %4',
                $params['currentPage'],
                $params['limit'],
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
    }

    /**
     * Create customer collection
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
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname'])
            ->addAttributeToFilter('website_id', ['in' => $websiteIds]);

        $this->applyFiltersToCollection($collection, $params);

        if (!empty($additionalAttributes)) {
            $collection->addAttributeToSelect(array_keys($additionalAttributes));
        }

        return $this->processCollection($collection, $websiteIds, $storeIds);
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
            'customer_id' => [
                'field' => 'entity_id',
                'multiple' => false,
            ],
            'customer_ids' => [
                'field' => 'entity_id',
                'multiple' => true,
            ],
            'email' => [
                'field' => 'email',
                'multiple' => false,
            ],
            'firstname' => [
                'field' => 'firstname',
                'multiple' => false,
            ],
            'lastname' => [
                'field' => 'lastname',
                'multiple' => false,
            ],
            'customer_group_id' => [
                'field' => 'group_id',
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
            'email' => 'email',
            'created_at' => 'created_at',
            'modified_at' => 'updated_at',
            'customer_id' => 'entity_id',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
        ];
    }

    /**
     * Process customer collection for 3rd party plugins
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
     * Map customer data into an export row.
     *
     * @param CustomerInterface|Customer $customer
     * @param array $storeIds
     * @return array
     */
    public function processCustomer($customer, $storeIds)
    {
        $row = [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'date_created' => $customer->getCreatedAt(),
            'source' => 'Magento2 customers'
        ];

        $row = $this->processTelephone($customer, $storeIds, $row);

        foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $fieldName) {
            $row[$fieldName] = $customer->getResource()
                ->getAttribute($attributeCode)
                ->getFrontend()
                ->getValue($customer);

            if ($row[$fieldName] === false) {
                $row[$fieldName] = '';
            }
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

    /**
     * Process and add telephone data to the customer export row.
     *
     * @param Customer $customer
     * @param array $storeIds
     * @param array $row
     * @return array
     */
    public function processTelephone($customer, $storeIds, $row)
    {
        if (!$this->isAddTelephone) {
            return $row;
        }

        $billingAddress = $customer->getPrimaryBillingAddress();
        $row['phone'] = '';
        if ($billingAddress) {
            $row['phone'] = $billingAddress->getTelephone();
        }

        return $row;
    }
}

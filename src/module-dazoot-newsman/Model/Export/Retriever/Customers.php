<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
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
class Customers implements RetrieverInterface
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
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param GetAdditionalAttributes $getAdditionalAttributes
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        GetAdditionalAttributes $getAdditionalAttributes
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->getAdditionalAttributes = $getAdditionalAttributes;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $pageSize = self::DEFAULT_PAGE_SIZE;
        if (!empty($data['limit']) && (int) $data['limit'] > 0) {
            $pageSize = (int) $data['limit'];
        }
        $start = (!empty($data['start']) && (int) $data['start'] >= 0) ? (int) $data['start'] : 0;
        $currentPage = (int) floor($start / $pageSize) + 1;

        $this->logger->info(__('Export customers %1, %2, store IDs', $currentPage, $pageSize, implode(",", $storeIds)));

        $websiteIds = [];
        foreach ($storeIds as $storeId) {
            $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
        }
        $websiteIds = array_unique($websiteIds);
        $collection = $this->createCollection($websiteIds, $storeIds, $currentPage, $pageSize);

        $count = $collection->getSize();
        $result = [];

        if (($count >= $currentPage * $pageSize)
            || (($count < $currentPage * $pageSize) && ($count > ($currentPage - 1) * $pageSize))
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
                $currentPage,
                $pageSize,
                implode(",", $storeIds),
                count($result)
            )
        );

        return $result;
    }

    /**
     * Create customer collection
     *
     * @param $websiteIds
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
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname'])
            ->addAttributeToFilter('website_id', ['in' => $websiteIds])
            ->setCurPage($currentPage)
            ->setPageSize($pageSize);

        if (!empty($additionalAttributes)) {
            $collection->addAttributeToSelect(array_keys($additionalAttributes));
        }

        return $this->processCollection($collection, $websiteIds, $storeIds);
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
     * @param CustomerInterface|Customer $customer
     * @param array $storeIds
     * @return array
     */
    public function processCustomer($customer, $storeIds)
    {
        $row = [
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname()
        ];

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
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalAttributes($storeIds)
    {
        return $this->getAdditionalAttributes->get($storeIds);
    }
}

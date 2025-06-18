<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get customers
 */
class Customers implements RetrieverInterface
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
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

        $websitesFilter = $this->filterBuilder
            ->setField('website_id')
            ->setConditionType('in')
            ->setValue($websiteIds)
            ->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaBuilder->setPageSize($pageSize)
            ->setCurrentPage($currentPage);

        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $searchCriteriaBuilder->addFilters([$websitesFilter])
            ->create();

        $count = $this->customerRepository->getList($searchCriteria)->getTotalCount();
        $result = [];

        if (($count >= $currentPage * $pageSize)
            || (($count < $currentPage * $pageSize) && ($count > ($currentPage - 1) * $pageSize))
        ) {
            $customers = $this->customerRepository->getList($searchCriteria)->getItems();
            /** @var CustomerInterface $customer */
            foreach ($customers as $customer) {
                try {
                    $result[] = $this->processCustomer($customer);
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
     * @param CustomerInterface|Customer $customer
     * @return array
     */
    public function processCustomer($customer)
    {
        return [
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname()
        ];
    }
}

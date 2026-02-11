<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsmanmarketing\Model\Order;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Dazoot\Newsmanmarketing\Api\OrderQueueRepositoryInterface;
use Dazoot\Newsmanmarketing\Api\Data\OrderQueueInterface;
use Dazoot\Newsmanmarketing\Api\Data\OrderQueueSearchResultInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Dazoot\Newsmanmarketing\Api\Data\OrderQueueSearchResultInterfaceFactory;
use Dazoot\Newsmanmarketing\Model\ResourceModel\Order\Queue\Collection;
use Dazoot\Newsmanmarketing\Model\ResourceModel\Order\Queue\CollectionFactory;
use Dazoot\Newsmanmarketing\Model\Spi\OrderQueueResourceInterface;

/**
 * Order Queue repository class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueueRepository implements OrderQueueRepositoryInterface
{
    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var OrderQueueSearchResultInterfaceFactory
     */
    protected $searchResultFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderQueueResourceInterface
     */
    protected $resourceModel;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @param QueueFactory $queueFactory
     * @param OrderQueueSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionFactory $collectionFactory
     * @param OrderQueueResourceInterface $resourceModel
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        QueueFactory $queueFactory,
        OrderQueueSearchResultInterfaceFactory $searchResultFactory,
        CollectionFactory $collectionFactory,
        OrderQueueResourceInterface $resourceModel,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->queueFactory = $queueFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Save order queue entry.
     *
     * @param OrderQueueInterface $queue
     * @return OrderQueueInterface
     * @throws NoSuchEntityException If queue ID is sent but the queue not exists
     * @throws LocalizedException
     */
    public function save(OrderQueueInterface $queue)
    {
        $queueId = $queue->getQueueId();
        if ($queueId) {
            try {
                $existingQueue = $this->getById($queueId);
                $mergedData = array_merge($existingQueue->getData(), $queue->getData());
                $queue->setData($mergedData);
            } catch (NoSuchEntityException $e) {
                $existingQueue = null;
            }
        }

        $this->resourceModel->save($queue);
        return $queue;
    }

    /**
     * Get order queue entry by ID.
     *
     * @param int $queueId
     * @return OrderQueueInterface
     * @throws NoSuchEntityException If $queueId is not found
     * @throws LocalizedException
     */
    public function getById($queueId)
    {
        $queue = $this->queueFactory->create()
            ->load($queueId);
        if (!$queue->getQueueId()) {
            throw new NoSuchEntityException();
        }
        return $queue;
    }

    /**
     * Get order queue entries by order ID and state.
     *
     * @param int $orderId
     * @param string|null $state
     * @return OrderQueueInterface[]
     * @throws NoSuchEntityException
     */
    public function getByOrderId($orderId, $state = null)
    {
        $orderIdFilter = $this->filterBuilder->setField(
            'order_id'
        )->setValue($orderId)
            ->create();

        $stateFilter = null;
        if (!empty($state)) {
            $stateFilter = $this->filterBuilder->setField(
                'state'
            )->setValue($state)
                ->create();
        }

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection(SortOrder::SORT_ASC)
            ->create();

        $searchCriteriaBuilder->addSortOrder($sortOrder);

        $searchCriteriaBuilder->addFilters([$orderIdFilter]);
        if ($stateFilter !== null) {
            $searchCriteriaBuilder->addFilters([$stateFilter]);
        }
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $searchCriteriaBuilder->create();

        $queueList = $this->getList($searchCriteria)->getItems();
        if (!(is_array($queueList) && count($queueList) > 0)) {
            $queueList = [];
        }

        return $queueList;
    }

    /**
     * Get order queue list by search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderQueueSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $interfaceName = OrderQueueInterface::class;
        $this->extensionAttributesJoinProcessor->process($collection, $interfaceName);

        $this->collectionProcessor->process($searchCriteria, $collection);
        /** @var OrderQueueSearchResultInterface $searchResults */
        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * Delete queue by queue id.
     *
     * @param int $queueId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($queueId)
    {
        $queue = $this->queueFactory->create()
            ->load($queueId);

        if (!$queue->getQueueId()) {
            throw new NoSuchEntityException();
        }

        $this->resourceModel->delete($queue);
        return true;
    }
}

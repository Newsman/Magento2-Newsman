<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Dazoot\Newsmanmarketing\Api\Data\OrderQueueInterface;
use Dazoot\Newsmanmarketing\Api\Data\OrderQueueSearchResultInterface;

/**
 * Order Queue CRUD interface
 */
interface OrderQueueRepositoryInterface
{
    /**
     * @param OrderQueueInterface $queue
     * @return OrderQueueInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function save(OrderQueueInterface $queue);

    /**
     * @param int $queueId
     * @return OrderQueueInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getById($queueId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderQueueSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $orderId
     * @param string|null $state
     * @return OrderQueueInterface[]
     * @throws NoSuchEntityException
     */
    public function getByOrderId($orderId, $state = null);

    /**
     * @param int $queueId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($queueId);
}

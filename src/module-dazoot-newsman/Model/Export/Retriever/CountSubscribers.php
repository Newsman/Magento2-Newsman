<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get count subscribers
 */
class CountSubscribers implements RetrieverInterface
{
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
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $this->logger->info(__('Export count subscribers store IDs %1', implode(",", $storeIds)));

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED);
        if (!empty($storeIds)) {
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }

        $count = $collection->getSize();
        $result = ['subscribers' => $count];
        $this->logger->info(__('Exported count subscribers store IDs %1: %2', implode(",", $storeIds), $count));

        return $result;
    }
}

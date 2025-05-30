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
        $currentPage = (!empty($data['start']) && $data['start'] >= 0) ? $data['start'] : 1;
        $pageSize = (empty($data['limit'])) ? self::DEFAULT_PAGE_SIZE : $data['limit'];
        $this->logger->info(
            __('Export subscribers %1, %2, store IDs %3', $currentPage, $pageSize, implode(",", $storeIds))
        );

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED);
        if (!empty($storeIds)) {
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }

        $result = [];
        /** @var Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            try {
                $result[] = $this->processCustomer($subscriber);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
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
     * @param Subscriber $subscriber
     * @return array
     */
    public function processCustomer($subscriber)
    {
        return [
            'email' => $subscriber->getSubscriberEmail(),
            'firstname' => '',
            'lastname' => ''
        ];
    }
}

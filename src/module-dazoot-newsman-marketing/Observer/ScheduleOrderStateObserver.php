<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsmanmarketing\Observer;

use Dazoot\Newsmanmarketing\Api\OrderQueueRepositoryInterface;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Order\Queue;
use Dazoot\Newsmanmarketing\Model\Order\QueueFactory;
use Dazoot\Newsmanmarketing\Model\Export\Order\State\Publish;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Dazoot\Newsman\Logger\Logger;

/**
 * Add new order state in queue and schedule for sending to Newsman API
 */
class ScheduleOrderStateObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderQueueRepositoryInterface
     */
    protected $orderQueueRepository;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var Publish
     */
    protected $publish;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param OrderQueueRepositoryInterface $orderQueueRepository
     * @param QueueFactory $queueFactory
     * @param Publish $publish
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        OrderQueueRepositoryInterface $orderQueueRepository,
        QueueFactory $queueFactory,
        Publish $publish,
        Logger $logger
    ) {
        $this->config = $config;
        $this->orderQueueRepository = $orderQueueRepository;
        $this->queueFactory = $queueFactory;
        $this->publish = $publish;
        $this->logger = $logger;
    }

    /**
     * Add new order state in queue and schedule for sending to Newsman API.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        if (!($order instanceof Order && $order->getId() > 0)) {
            return;
        }

        if (!$this->config->isActive($order->getStoreId())) {
            return;
        }

        if (!$this->isStateChanged($order)) {
            return;
        }

        try {
            /** @var Queue $queue */
            $queue = $this->queueFactory->create();
            $queue->setOrderId($order->getId())
                ->setStoreId($order->getStoreId())
                ->setState($order->getState())
                ->setIncrementId($order->getIncrementId())
                ->setSent(0)
                ->setFailures(0);
            $this->orderQueueRepository->save($queue);
            $this->logger->info(__(
                'Order %1 with state %2 added to queue.',
                $order->getIncrementId(),
                $order->getState()
            ));

            $this->publish->execute((int) $order->getId(), (int) $order->getStoreId());
        } catch (\Exception $e) {
            if (is_object($queue) && $queue->getId() > 0) {
                $this->orderQueueRepository->deleteById($queue->getId());
                $this->logger->info(__('Order %1 with state %2 removed from queue.', $order->getIncrementId()));
            }
            $this->logger->error($e);
        }
    }

    /**
     * Check if the order state has changed.
     *
     * @param Order $order
     * @return boolean
     */
    public function isStateChanged($order)
    {
        return $order->dataHasChangedFor('state');
    }
}

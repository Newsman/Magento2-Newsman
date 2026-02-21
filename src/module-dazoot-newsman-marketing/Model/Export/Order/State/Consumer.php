<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Export\Order\State;

use Dazoot\Newsmanmarketing\Api\Data\OrderQueueInterface;
use Dazoot\Newsmanmarketing\Api\OrderQueueRepositoryInterface;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsmanmarketing\Model\Order\Mapper as OrderMapper;
use Dazoot\Newsmanmarketing\Model\Service\Context\SaveOrderContext;
use Dazoot\Newsmanmarketing\Model\Service\Context\SaveOrderContextFactory;
use Dazoot\Newsmanmarketing\Model\Service\Context\SetPurchaseStatusContext;
use Dazoot\Newsmanmarketing\Model\Service\Context\SetPurchaseStatusContextFactory;
use Dazoot\Newsmanmarketing\Model\Service\SaveOrder;
use Dazoot\Newsmanmarketing\Model\Service\SetPurchaseStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Export order state to Newsman API consumer
 * @see Publish
 */
class Consumer
{
    /**
     * Maximum number of attempts to send a queued state.
     */
    public const MAX_ATTEMPTS = 3;

    /**
     * Module configuration.
     *
     * @var Config
     */
    public $config;

    /**
     * Order queue repository.
     *
     * @var OrderQueueRepositoryInterface
     */
    protected $queueRepository;

    /**
     * Store manager instance.
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Factory for building order state API context.
     *
     * @var SetPurchaseStatusContextFactory
     */
    protected $contextFactory;

    /**
     * Service to send purchase status to Newsman.
     *
     * @var SetPurchaseStatus
     */
    protected $setPurchaseStatus;

    /**
     * Service to save order data to Newsman.
     *
     * @var SaveOrder
     */
    protected $saveOrder;

    /**
     * Factory for building save order API context.
     *
     * @var SaveOrderContextFactory
     */
    protected $saveOrderContextFactory;

    /**
     * Maps Magento order to Newsman API payload.
     *
     * @var OrderMapper
     */
    protected $orderMapper;

    /**
     * Magento order repository.
     *
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Newsman logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param OrderQueueRepositoryInterface $queueRepository
     * @param StoreManagerInterface $storeManager
     * @param SetPurchaseStatusContextFactory $contextFactory
     * @param SetPurchaseStatus $setPurchaseStatus
     * @param SaveOrder $saveOrder
     * @param SaveOrderContextFactory $saveOrderContextFactory
     * @param OrderMapper $orderMapper
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        OrderQueueRepositoryInterface $queueRepository,
        StoreManagerInterface $storeManager,
        SetPurchaseStatusContextFactory $contextFactory,
        SetPurchaseStatus $setPurchaseStatus,
        SaveOrder $saveOrder,
        SaveOrderContextFactory $saveOrderContextFactory,
        OrderMapper $orderMapper,
        OrderRepositoryInterface $orderRepository,
        Logger $logger
    ) {
        $this->config = $config;
        $this->queueRepository = $queueRepository;
        $this->storeManager = $storeManager;
        $this->contextFactory = $contextFactory;
        $this->setPurchaseStatus = $setPurchaseStatus;
        $this->saveOrder = $saveOrder;
        $this->saveOrderContextFactory = $saveOrderContextFactory;
        $this->orderMapper = $orderMapper;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Process order state export for the specified order ID.
     *
     * @param int $orderId
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute($orderId)
    {
        $aQueueList = $this->queueRepository->getByOrderId($orderId);

        if (empty($aQueueList)) {
            return;
        }

        $queue = current($aQueueList);
        if (!$this->config->isActive($queue->getStoreId())) {
            return;
        }

        // Build consecutive array keys
        $queueList = [];
        foreach ($aQueueList as $queue) {
            $queueList[] = $queue;
        }

        // Find last sent state of the order.
        $lastSentKey = 0;
        $markSentList = [];
        foreach ($queueList as $key => $queue) {
            if ($queue->getSent()) {
                $lastSentKey = $key;
            }
        }
        // For everything before last sent key, do not send to API.
        foreach ($queueList as $key => $queue) {
            if ($lastSentKey > 0 && $lastSentKey > $key && !$queue->getSent()) {
                $markSentList[] = $queue;
            }
        }

        // Mark all not sent old order states as sent to ignore them now and in the future.
        foreach ($markSentList as $queue) {
            try {
                $queue->setSent(1);
                $this->queueRepository->save($queue);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        // Send last order states that were not sent. But do not send same consecutive states.
        $lastState = current($queueList)->getState();
        foreach ($queueList as $key => $queue) {
            // Start to send after last sent order state
            if ($lastSentKey > 0 && $key <= $lastSentKey) {
                $lastState = $queue->getState();
                continue;
            }

            if ($queue->getSent() == 1) {
                $lastState = $queue->getState();
                continue;
            }

            // Send state if none was sent or not same consecutive states
            if (($lastSentKey == 0 && $key == 0) || $lastState != $queue->getState()) {
                $sentSuccess = false;
                try {
                    $this->exportOrder($queue);
                    $sentSuccess = true;
                } catch (\Exception $e) {
                    $this->logger->error($e);

                    try {
                        // Do not try to send a queued state over and over. Limit by number of maximum attempts.
                        if ($queue->getFailures() < self::MAX_ATTEMPTS) {
                            $queue->setFailures((int) $queue->getFailures() + 1);
                        } else {
                            $queue->setSent(1);
                        }
                        $this->queueRepository->save($queue);
                    } catch (\Exception $e) {
                        $this->logger->error($e);
                    }
                }

                if ($sentSuccess) {
                    $this->markSent($queue);
                }
            } else {
                $this->markSent($queue);
            }

            $lastState = $queue->getState();
        }
    }

    /**
     * Mark an order queue entry as sent.
     *
     * @param OrderQueueInterface $queue
     * @return void
     */
    public function markSent($queue)
    {
        try {
            $queue->setSent(1);
            $this->queueRepository->save($queue);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Trigger the order export service for a queue entry.
     *
     * Calls remarketing.saveOrder to sync full order data, then
     * remarketing.setPurchaseStatus to update the order state.
     * saveOrder errors are logged but do not prevent setPurchaseStatus
     * from running. setPurchaseStatus errors propagate to trigger retry logic.
     *
     * @param OrderQueueInterface $queue
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function exportOrder($queue)
    {
        try {
            $order = $this->orderRepository->get($queue->getOrderId());
            /** @var SaveOrderContext $saveOrderContext */
            $saveOrderContext = $this->saveOrderContextFactory->create()
                ->setStore($this->storeManager->getStore($queue->getStoreId()))
                ->setOrderDetails($this->orderMapper->getOrderDetails($order))
                ->setOrderProducts($this->orderMapper->getOrderProducts($order));
            $this->saveOrder->execute($saveOrderContext);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $this->setPurchaseStatus->execute($this->getOrderContext($queue));
    }

    /**
     * Build context for order status update API call.
     *
     * @param OrderQueueInterface $queue
     * @return SetPurchaseStatusContext
     * @throws NoSuchEntityException
     */
    public function getOrderContext($queue)
    {
        /** @var SetPurchaseStatusContext $context */
        return $this->contextFactory->create()
            ->setStore($this->storeManager->getStore($queue->getStoreId()))
            ->setState($queue->getState())
            ->setOrderId($queue->getIncrementId());
    }

    /**
     * Retrieve the configured maximum number of retry attempts.
     *
     * @return int
     */
    public function getMaxAttempts()
    {
        return self::MAX_ATTEMPTS;
    }
}

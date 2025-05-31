<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Export\Order\State;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Export order state to Newsman API publisher
 */
class Publish
{
    /**
     * Export order state queue topic name.
     */
    public const TOPIC_EXPORT_ORDER_STATE = 'dazoot_newsman_marketing.export.order.state';

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param PublisherInterface $publisher
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        Config $config,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Publish order state export message to the message queue
     *
     * @param int $orderId
     * @param int $storeId
     * @return void
     */
    public function execute($orderId, $storeId)
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return;
        }

        if (!$this->config->isActive($storeId)) {
            return;
        }

        $this->publisher->publish(
            self::TOPIC_EXPORT_ORDER_STATE,
            $orderId
        );

        $this->logger->info(__('Published order state change for order ID %1 to the queue.', $orderId));
    }
}

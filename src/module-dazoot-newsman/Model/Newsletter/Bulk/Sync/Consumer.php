<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Sync;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\User\HostIpAddress;
use Dazoot\Newsman\Observer\Newsletter\SubscribeUnsubscribeObserver;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter Bulk Sync Consumer
 *
 * Sends the current status of subscribers changed by an admin customer grid mass action (subscribe or unsubscribe)
 * to Newsman, with the same rules as the subscribe / unsubscribe observer. Nothing is changed in Magento.
 */
class Consumer extends \Dazoot\Newsman\Model\Newsletter\Bulk\Unsubscribe\Consumer
{
    /**
     * @var string
     */
    protected $name = 'Bulk Sync Subscriber Consumer';

    /**
     * @var SubscribeUnsubscribeObserver
     */
    protected $subscribeUnsubscribeObserver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param OperationManagementInterface $operationManagement
     * @param EntityManager $entityManager
     * @param SubscriberFactory $subscriberFactory
     * @param UnsubscribeEmail $unsubscribeEmail
     * @param UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory
     * @param HostIpAddress $ipAddress
     * @param Logger $logger
     * @param SubscribeUnsubscribeObserver $subscribeUnsubscribeObserver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        OperationManagementInterface $operationManagement,
        EntityManager $entityManager,
        SubscriberFactory $subscriberFactory,
        UnsubscribeEmail $unsubscribeEmail,
        UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory,
        HostIpAddress $ipAddress,
        Logger $logger,
        SubscribeUnsubscribeObserver $subscribeUnsubscribeObserver
    ) {
        parent::__construct(
            $storeManager,
            $serializer,
            $operationManagement,
            $entityManager,
            $subscriberFactory,
            $unsubscribeEmail,
            $unsubscribeEmailContextFactory,
            $ipAddress,
            $logger
        );
        $this->subscribeUnsubscribeObserver = $subscribeUnsubscribeObserver;
    }

    /**
     * Send the current status of each subscriber to Newsman
     *
     * @param array $data
     * @return int
     */
    public function execute($data)
    {
        $subscriberIds = $data['subscriber_ids'] ?? [];
        if (!(is_array($subscriberIds) && !empty($subscriberIds))) {
            return 0;
        }

        $countDone = 0;
        foreach ($subscriberIds as $subscriberId) {
            $subscriber = $this->subscriberFactory->create()->load($subscriberId);
            if ($subscriber->getId() <= 0) {
                continue;
            }

            // The observer compares the status as an integer, as set by Magento when the subscriber is saved.
            $subscriber->setSubscriberStatus((int) $subscriber->getSubscriberStatus());

            $this->logger->info(__('%1 | Send status of email %2', $this->name, $subscriber->getSubscriberEmail()));
            try {
                $this->subscribeUnsubscribeObserver->sendSubscriber($subscriber);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
            $countDone++;
        }

        return $countDone;
    }
}

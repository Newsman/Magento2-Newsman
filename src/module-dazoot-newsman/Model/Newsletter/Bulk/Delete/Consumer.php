<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Delete;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Newsletter\Anonymiser;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\User\HostIpAddress;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter Bulk Delete Consumer
 *
 * Anonymises the emails in the Newsman list of the subscriber's store view, whatever the subscriber status, then
 * deletes the subscribers in Magento. When "Anonymise in Newsman when customers are deleted" is No, the emails are
 * unsubscribed instead (decided per Newsman list by the Anonymiser).
 */
class Consumer extends \Dazoot\Newsman\Model\Newsletter\Bulk\Unsubscribe\Consumer
{
    /**
     * @var string
     */
    protected $name = 'Bulk Delete Subscriber Consumer';

    /**
     * @var Anonymiser
     */
    protected $anonymiser;

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
     * @param Anonymiser $anonymiser
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        Anonymiser $anonymiser
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
        $this->anonymiser = $anonymiser;
    }

    /**
     * Anonymise (or unsubscribe) the emails in Newsman and delete the subscribers
     *
     * @param array $data
     * @return int
     */
    public function execute($data)
    {
        $subscriberIds = $data['subscriber_ids'];
        if (!(is_array($subscriberIds) && !empty($subscriberIds))) {
            return 0;
        }

        // Each queue message is a new unit of work in a long running consumer process.
        $this->anonymiser->reset();

        $countDone = 0;
        foreach ($subscriberIds as $subscriberId) {
            $subscriber = $this->subscriberFactory->create()->load($subscriberId);
            if ($subscriber->getId() <= 0) {
                continue;
            }

            $email = (string) $subscriber->getSubscriberEmail();
            $this->anonymiser->anonymise(
                $email,
                [(int) $subscriber->getStoreId()],
                __('%1 | Try to anonymise email %2', $this->name, $email),
                __('%1 | Try to unsubscribe email %2', $this->name, $email)
            );

            $this->executeSubscriberAction($subscriber);
            $countDone++;
        }

        return $countDone;
    }

    /**
     * Trigger the deletion action for a subscriber.
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function executeSubscriberAction($subscriber)
    {
        if ($subscriber->getSubscriberId() > 0) {
            /* The email was anonymised (or unsubscribed) in Newsman above, no second call on delete. */
            /* @see \Dazoot\Newsman\Observer\Newsletter\DeleteAnonymiseObserver */
            $subscriber->setNewsmanSkipUnsubscribeFlag(true);
            $subscriber->delete();
            $this->logger->info(
                __('Executed Magento delete subscriber with email %1', $subscriber->getSubscriberEmail())
            );
        }
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types = 1);

namespace Dazoot\Newsman\Observer\Newsletter;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Api\ErrorCode\Unsubscribe as UnsubscribeError;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\User\HostIpAddress;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Unsubscribe the email from the Newsman list after a newsletter subscriber is deleted in Magento.
 *
 * Magento deletes the newsletter subscribers of a customer when the customer is deleted (admin customer
 * page, admin customer grid mass delete, REST API, customer repository), for example on a GDPR request
 * to erase the customer data. The delete never saves the subscriber, so the subscribe / unsubscribe
 * observer does not run.
 *
 * Runs after the delete is committed, so the email is unsubscribed only when the row is really gone.
 * A Newsman error is logged and never stops the delete.
 */
class DeleteUnsubscribeObserver implements ObserverInterface
{
    /**
     * Subscriber statuses that may have been sent to the Newsman list.
     *
     * STATUS_NOT_ACTIVE is included because a subscription waiting for confirmation can be pending in Newsman.
     * STATUS_UNSUBSCRIBED was already unsubscribed and STATUS_UNCONFIRMED (customer account not confirmed yet)
     * is never sent to Newsman.
     */
    public const UNSUBSCRIBE_STATUSES = [
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_NOT_ACTIVE,
    ];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UnsubscribeEmail
     */
    protected $unsubscribeEmail;

    /**
     * @var UnsubscribeEmailContextFactory
     */
    protected $unsubscribeEmailContextFactory;

    /**
     * @var HostIpAddress
     */
    protected $ipAddress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param UnsubscribeEmail $unsubscribeEmail
     * @param UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory
     * @param HostIpAddress $ipAddress
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UnsubscribeEmail $unsubscribeEmail,
        UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory,
        HostIpAddress $ipAddress,
        Config $config,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->unsubscribeEmail = $unsubscribeEmail;
        $this->unsubscribeEmailContextFactory = $unsubscribeEmailContextFactory;
        $this->ipAddress = $ipAddress;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Unsubscribe the email of the deleted subscriber from the Newsman list of its store
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) : void
    {
        $email = '';
        try {
            /** @var Subscriber $subscriber */
            $subscriber = $observer->getEvent()->getSubscriber();
            if (!($subscriber instanceof Subscriber)) {
                return;
            }

            /* @see \Dazoot\Newsman\Model\Newsletter\Bulk\Delete\Consumer::executeSubscriberAction() */
            if ($subscriber->getNewsmanSkipUnsubscribeFlag()) {
                return;
            }

            $email = (string) $subscriber->getSubscriberEmail();
            if ($email === '') {
                return;
            }

            if (!in_array((int) $subscriber->getSubscriberStatus(), self::UNSUBSCRIBE_STATUSES, true)) {
                return;
            }

            $storeId = (int) $subscriber->getStoreId();
            if (!$this->config->isEnabled($storeId)) {
                return;
            }

            $this->logger->info(__('Subscriber deleted in Magento, unsubscribe email %1', $email));

            $this->unsubscribeEmail->execute(
                $this->unsubscribeEmailContextFactory->create()
                    ->setEmail($email)
                    ->setStore($this->storeManager->getStore($storeId))
                    ->setIp($this->ipAddress->getIp())
            );
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === UnsubscribeError::NO_SUCH_SUBSCRIBER) {
                $this->logInfo(__('Email %1 is not on the Newsman list, nothing to unsubscribe', $email));
                return;
            }
            $this->logError($email, $e);
        }
    }

    /**
     * Log an info message without letting a logger error reach the subscriber delete
     *
     * @param \Magento\Framework\Phrase|string $message
     * @return void
     */
    protected function logInfo($message)
    {
        try {
            $this->logger->info($message);
        } catch (\Throwable $e) {
            return;
        }
    }

    /**
     * Log a failed unsubscribe without letting a logger error reach the subscriber delete
     *
     * @param string $email
     * @param \Throwable $exception
     * @return void
     */
    protected function logError($email, $exception)
    {
        try {
            $this->logger->error(
                __(
                    'Could not unsubscribe email %1 from Newsman after the subscriber was deleted: %2',
                    $email,
                    $exception->getMessage()
                )
            );
        } catch (\Throwable $e) {
            return;
        }
    }
}

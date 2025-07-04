<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Validator\EmailAddress as EmailAddressValidator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Webhook for Newsman
 */
class Webhooks
{
    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var EmailAddressValidator
     */
    protected $emailAddressValidator;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CustomerAccountManagement
     */
    protected $customerAccountManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @param SubscriberFactory $subscriberFactory
     * @param EmailAddressValidator $emailAddressValidator
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Json $serializer
     * @param Logger $logger
     * @param CustomerAccountManagement $customerAccountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        EmailAddressValidator $emailAddressValidator,
        StoreManagerInterface $storeManager,
        Config $config,
        Json $serializer,
        Logger $logger,
        CustomerAccountManagement $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        EventManagerInterface $eventManager
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->emailAddressValidator = $emailAddressValidator;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * @param array $data
     * @param int|null $storeId
     * @return array
     */
    public function process($data, $storeId = null)
    {
        if (!is_array($data)) {
            return [];
        }
        $store = $this->storeManager->getStore($storeId);

        $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        $this->logger->info(__('Processing webhook data for store %1', $store->getName() . ' (' . $storeId . ')'));
        $this->logger->info($this->serializer->serialize($data));

        $result = [];
        foreach ($data as $event) {
            switch ($event['type']) {
                case 'unsub':
                    $result[] = $this->processUnsubscribe($event, $websiteId);
                    break;

                case 'subscribe_confirm':
                case 'subscribe':
                    $result[] = $this->processSubscribe($event, $storeId);
                    break;

                case 'import':
                    $result[] = $this->processImportNotifier($event, $storeId);
                    break;

                default:
                    $this->logger->error(__('Unknown webhook type %1', $event['type']));
                    $result[] = ['error' => __('Unknown webhook type %1', $event['type'])];
            }
        }

        return $result;
    }

    /**
     * @param array $event
     * @param int $websiteId
     * @return array
     */
    public function processUnsubscribe($event, $websiteId)
    {
        $data = $this->preProcessSubscription($event);
        if (empty($data['success'])) {
            return $data;
        }
        $email = $data['email'];

        try {
            /** @var Subscriber $subscriber */
            $subscriber = $this->subscriberFactory->create()
                ->loadBySubscriberEmail($email, $websiteId);
            if (!$subscriber->getId()) {
                $this->logger->info(__('Subscriber not found for email %1', $email));
                return ['success' => true, 'email' => $email];
            }

            $subscriber->setNewsmanSkipUnsubscribeFlag(true);

            /* @see \Magento\Newsletter\Model\SubscriptionManager::unsubscribe() */
            $subscriber->setCheckCode($subscriber->getSubscriberConfirmCode());

            $this->eventManager->dispatch('newsman_webhook_before_unsubscribe', ['subscriber' => $subscriber]);
            $subscriber->unsubscribe();
            $this->eventManager->dispatch('newsman_webhook_after_unsubscribe', ['subscriber' => $subscriber]);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return [
                'error' => __('Something went wrong unsubscribing email %1. Please try again later.', $email),
                'email' => $email
            ];
        }

        $this->logger->info(__('Unsubscribed email %1', $email));
        return ['success' => true, 'email' => $email];
    }

    /**
     * @param array $event
     * @param int|null $storeId
     * @return array
     */
    public function processSubscribe($event, $storeId = null)
    {
        $data = $this->preProcessSubscription($event);
        if (empty($data['success'])) {
            return $data;
        }
        $email = $data['email'];
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        $customer = $this->getCustomer($email, $websiteId);
        $isCreatedSubscriber = false;

        try {
            /** @var Subscriber $subscriber */
            $subscriber = $this->subscriberFactory->create()
                ->loadBySubscriberEmail($email, $websiteId);
            if (!$subscriber->getId()) {
                /* @see \Magento\Newsletter\Model\SubscriptionManager::subscribe() */
                $status = $this->config->isDoubleOptIn($storeId) ? Subscriber::STATUS_NOT_ACTIVE :
                    Subscriber::STATUS_SUBSCRIBED;
                $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
                $subscriber->setSubscriberEmail($email);
                $subscriber->setNewsmanSkipSubscribeFlag(true);
                $subscriber->setStatus($status)
                    ->setStoreId($storeId);

                if ((($customer instanceof Customer) || ($customer instanceof CustomerData)) &&
                    $customer->getId() > 0) {
                    $subscriber->setCustomerId($customer->getId());
                }

                $this->eventManager->dispatch(
                    'newsman_webhook_before_create',
                    ['subscriber' => $subscriber, 'customer' => $customer]
                );
                $subscriber->save();
                $this->eventManager->dispatch(
                    'newsman_webhook_after_create',
                    ['subscriber' => $subscriber, 'customer' => $customer]
                );

                $isCreatedSubscriber = true;
                $this->sendEmailAfterChangeStatus($subscriber);

                $this->logger->info(__('Created subscriber with email %1', $email));
            }

            $this->eventManager->dispatch(
                'newsman_webhook_before_confirm',
                ['subscriber' => $subscriber, 'customer' => $customer, 'created' => $isCreatedSubscriber]
            );
            $subscriber->confirm($subscriber->getSubscriberConfirmCode());
            $this->eventManager->dispatch(
                'newsman_webhook_after_confirm',
                ['subscriber' => $subscriber, 'customer' => $customer, 'created' => $isCreatedSubscriber]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return [
                'error' => __('Something went wrong subscribing email %1. Please try again later.', $email),
                'email' => $email
            ];
        }

        $this->logger->info(__('Subscribed email %1', $email));
        return ['success' => true, 'email' => $email];
    }

    /**
     * @param array $event
     * @return array
     */
    public function preProcessSubscription($event)
    {
        if (isset($event['data']['email'])) {
            $email = $event['data']['email'];
        } else {
            $this->logger->error(__('Email not found in webhook data'));
            return ['error' => __('Email not found in webhook data')];
        }

        if ($this->emailAddressValidator->isValid($email) === false) {
            $this->logger->error(__('Invalid email address %1', $email . ''));
            return ['error' => __('Invalid email address %1', $email . ''), 'email' => $email];
        }

        return ['success' => true, 'email' => $email];
    }

    /**
     * Sends out email to customer after change subscription status
     * @see \Magento\Newsletter\Model\SubscriptionManager::sendEmailAfterChangeStatus()
     *
     * @param Subscriber $subscriber
     * @return void
     */
    private function sendEmailAfterChangeStatus(Subscriber $subscriber): void
    {
        $status = (int) $subscriber->getStatus();
        if ($status === Subscriber::STATUS_UNCONFIRMED) {
            return;
        }

        try {
            switch ($status) {
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $subscriber->sendUnsubscriptionEmail();
                    break;
                case Subscriber::STATUS_SUBSCRIBED:
                    $subscriber->sendConfirmationSuccessEmail();
                    break;
                case Subscriber::STATUS_NOT_ACTIVE:
                    $subscriber->sendConfirmationRequestEmail();
                    break;
            }
        } catch (MailException $e) {
            // If we are not able to send a new account email, this should be ignored
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $email
     * @param int $websiteId
     * @return CustomerInterface|null
     * @throws LocalizedException
     */
    public function getCustomer($email, $websiteId)
    {
        if (!$this->customerAccountManagement->isEmailAvailable($email, $websiteId)) {
            return null;
        }

        try {
            return $this->customerRepository->get($email, $websiteId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array $event
     * @param int|null $storeId
     * @return array
     */
    public function processImportNotifier($event, $storeId = null)
    {
        $this->logger->info(__('Processing import notifier:' . $this->serializer->serialize($event)));
        return [];
    }
}

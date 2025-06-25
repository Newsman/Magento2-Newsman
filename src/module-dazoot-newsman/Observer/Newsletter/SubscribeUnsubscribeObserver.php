<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types = 1);

namespace Dazoot\Newsman\Observer\Newsletter;

use Dazoot\Newsman\Helper\Customer\AttributesMap;
use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Api\ErrorCode\InitSubscribe as InitSubscribeError;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Service\SubscribeEmail;
use Dazoot\Newsman\Model\Service\Context\SubscribeEmailContext;
use Dazoot\Newsman\Model\Service\Context\SubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContext;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\InitSubscribeEmail;
use Dazoot\Newsman\Model\Service\Context\InitSubscribeEmailContext;
use Dazoot\Newsman\Model\Service\Context\InitSubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\InitUnsubscribeEmail;
use Dazoot\Newsman\Model\Service\Context\InitUnsubscribeEmailContext;
use Dazoot\Newsman\Model\Service\Context\InitUnsubscribeEmailContextFactory;
use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Dazoot\Newsman\Model\User\IpAddressInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Subscribe or unsubscribe email from Newsman newsletter list
 */
class SubscribeUnsubscribeObserver implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SubscribeEmail
     */
    protected $subscribeEmail;

    /**
     * @var SubscribeEmailContextFactory
     */
    protected $subscribeEmailContextFactory;

    /**
     * @var UnsubscribeEmail
     */
    protected $unsubscribeEmail;

    /**
     * @var UnsubscribeEmailContextFactory
     */
    protected $unsubscribeEmailContextFactory;

    /**
     * @var InitSubscribeEmail
     */
    protected $initSubscribeEmail;

    /**
     * @var InitSubscribeEmailContextFactory
     */
    protected $initSubscribeEmailContextFactory;

    /**
     * @var InitUnsubscribeEmail
     */
    protected $initUnsubscribeEmail;

    /**
     * @var InitUnsubscribeEmailContextFactory
     */
    protected $initUnsubscribeEmailContextFactory;

    /**
     * @var IpAddressInterface
     */
    protected $ipAddress;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var AttributesMap
     */
    protected $attributesMap;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SubscribeEmail $subscribeEmail
     * @param SubscribeEmailContextFactory $subscribeEmailContextFactory
     * @param UnsubscribeEmail $unsubscribeEmail
     * @param UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory
     * @param InitSubscribeEmail $initSubscribeEmail
     * @param InitSubscribeEmailContextFactory $initSubscribeEmailContextFactory
     * @param InitUnsubscribeEmail $initUnsubscribeEmail
     * @param InitUnsubscribeEmailContextFactory $initUnsubscribeEmailContextFactory
     * @param IpAddressInterface $ipAddress
     * @param CustomerSession $customerSession
     * @param Config $config
     * @param Logger $logger
     * @param AttributesMap $attributesMap
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SubscribeEmail $subscribeEmail,
        SubscribeEmailContextFactory $subscribeEmailContextFactory,
        UnsubscribeEmail $unsubscribeEmail,
        UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory,
        InitSubscribeEmail $initSubscribeEmail,
        InitSubscribeEmailContextFactory $initSubscribeEmailContextFactory,
        InitUnsubscribeEmail $initUnsubscribeEmail,
        InitUnsubscribeEmailContextFactory $initUnsubscribeEmailContextFactory,
        IpAddressInterface $ipAddress,
        CustomerSession $customerSession,
        Config $config,
        Logger $logger,
        AttributesMap $attributesMap
    ) {
        $this->storeManager = $storeManager;
        $this->subscribeEmail = $subscribeEmail;
        $this->subscribeEmailContextFactory = $subscribeEmailContextFactory;
        $this->unsubscribeEmail = $unsubscribeEmail;
        $this->unsubscribeEmailContextFactory = $unsubscribeEmailContextFactory;
        $this->initSubscribeEmail = $initSubscribeEmail;
        $this->initSubscribeEmailContextFactory = $initSubscribeEmailContextFactory;
        $this->initUnsubscribeEmail = $initUnsubscribeEmail;
        $this->initUnsubscribeEmailContextFactory = $initUnsubscribeEmailContextFactory;
        $this->ipAddress = $ipAddress;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->logger = $logger;
        $this->attributesMap = $attributesMap;
    }

    /**
     * Subscribe or unsubscribe email address to/from Newsman
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer) : void
    {
        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $storeId = $subscriber->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        /* @see \Dazoot\Newsman\Model\Webhooks::processSubscribe() */
        if ($subscriber->getNewsmanSkipSubscribeFlag()) {
            return;
        }

        /* @see \Dazoot\Newsman\Model\Webhooks::processUnsubscribe() */
        if ($subscriber->getNewsmanSkipUnsubscribeFlag()) {
            return;
        }

        $store = $this->storeManager->getStore($storeId);
        $ip = $this->ipAddress->getIp();
        $customer = $this->getCustomer($subscriber->getEmail(), $store);
        $isInitSubscribe = false;

        try {
            if ($this->config->isNewsletterNewsmanSendSub($store)) {
                if ($this->isInitSubscribeAction($subscriber, $storeId)) {
                    $isInitSubscribe = true;
                    $this->initSubscribeEmail->execute(
                        $this->getInitSubscribeEmailContext($subscriber, $store, $ip, $customer)
                    );
                } elseif ($subscriber->getStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                    /*$this->initUnsubscribeEmail->execute(
                        $this->getInitUnsubscribeEmailContext($subscriber, $store, $ip)
                    );*/
                    $this->unsubscribeEmail->execute(
                        $this->getUnsubscribeEmailContext($subscriber, $store, $ip)
                    );
                }
            } else {
                if ($subscriber->getStatus() === Subscriber::STATUS_SUBSCRIBED) {
                    $this->subscribeEmail->execute(
                        $this->getSubscribeEmailContext($subscriber, $store, $ip, $customer)
                    );
                } elseif ($subscriber->getStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                    $this->unsubscribeEmail->execute(
                        $this->getUnsubscribeEmailContext($subscriber, $store, $ip)
                    );
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e);
            if ($isInitSubscribe && $e->getCode() === InitSubscribeError::TOO_MANY_REQUESTS) {
                throw $e;
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param Subscriber $subscriber
     * @param int $storeId
     * @return bool
     */
    public function isInitSubscribeAction($subscriber, $storeId)
    {
        if ($this->config->isDoubleOptIn($storeId)) {
            return $subscriber->getStatus() === Subscriber::STATUS_NOT_ACTIVE;
        } else {
            return $subscriber->getStatus() === Subscriber::STATUS_SUBSCRIBED;
        }
    }

    /**
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param string $ip
     * @param Customer|CustomerData|null $customer
     * @return SubscribeEmailContext
     */
    public function getSubscribeEmailContext($subscriber, $store, $ip, $customer = null)
    {
        /** @var SubscribeEmailContext $context */
        $context = $this->subscribeEmailContextFactory->create()
            ->setEmail($subscriber->getEmail())
            ->setStore($store)
            ->setIp($ip);

        if (($customer instanceof Customer) || ($customer instanceof CustomerData)) {
            $context->setFirstname($customer->getFirstname());
            $context->setLastname($customer->getLastname());
        }
        $context->setProperties($this->getProperties($subscriber, $store, $customer));

        return $context;
    }

    /**
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param string $ip
     * @return UnsubscribeEmailContext
     */
    public function getUnsubscribeEmailContext($subscriber, $store, $ip)
    {
        return $this->unsubscribeEmailContextFactory->create()
            ->setEmail($subscriber->getEmail())
            ->setStore($store)
            ->setIp($ip);
    }

    /**
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param string $ip
     * @param Customer|CustomerData|null $customer
     * @return InitSubscribeEmailContext
     */
    public function getInitSubscribeEmailContext($subscriber, $store, $ip, $customer = null)
    {
        /** @var InitSubscribeEmailContext $context */
        $context = $this->initSubscribeEmailContextFactory->create()
            ->setEmail($subscriber->getEmail())
            ->setStore($store)
            ->setIp($ip);

        if (($customer instanceof Customer) || ($customer instanceof CustomerData)) {
            $context->setFirstname($customer->getFirstname());
            $context->setLastname($customer->getLastname());
        }
        $context->setProperties($this->getProperties($subscriber, $store, $customer));

        return $context;
    }

    /**
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param string $ip
     * @return InitUnsubscribeEmailContext
     */
    public function getInitUnsubscribeEmailContext($subscriber, $store, $ip)
    {
        return $this->initUnsubscribeEmailContextFactory->create()
            ->setEmail($subscriber->getEmail())
            ->setStore($store)
            ->setIp($ip);
    }

    /**
     * @param string $email
     * @param StoreInterface $store
     * @return Customer|null
     */
    public function getCustomer($email, $store)
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            if ($customer->getEmail() === $email) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param Customer|CustomerData|null $customer
     * @return array
     */
    public function getProperties($subscriber, $store, $customer = null)
    {
        if ($customer === null) {
            return [];
        }

        if (!(($customer instanceof Customer) || ($customer instanceof CustomerData))) {
            return [];
        }

        $properties = [];
        $attributesFields = $this->attributesMap->getConfigValuebyStoreId($store);
        foreach ($attributesFields as $row) {
            if (empty($row['a']) || empty($row['f'])) {
                continue;
            }

            $attributeCode = $row['a'];
            $fieldName = $row['f'];
            $properties[$fieldName] = $customer->getResource()
                ->getAttribute($attributeCode)
                ->getFrontend()
                ->getValue($customer);
        }

        return $properties;
    }
}

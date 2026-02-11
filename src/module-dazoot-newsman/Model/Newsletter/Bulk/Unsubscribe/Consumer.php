<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Unsubscribe;

use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContext;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\User\HostIpAddress;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dazoot\Newsman\Logger\Logger;

/**
 * Newsletter Bulk Unsubscribe Consumer
 */
class Consumer
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var OperationManagementInterface
     */
    protected $operationManagement;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

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
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $name = 'Bulk Unsubscribe Subscriber Consumer';

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
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->operationManagement = $operationManagement;
        $this->entityManager = $entityManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->unsubscribeEmail = $unsubscribeEmail;
        $this->unsubscribeEmailContextFactory = $unsubscribeEmailContextFactory;
        $this->ipAddress = $ipAddress;
        $this->logger = $logger;
    }

    /**
     * Process
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws \Exception
     * @return void
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);
            $this->execute($data);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during unsubscribe from newsletter. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __(
                'Sorry, something went wrong during unsubscribe from newsletter. Please see log for details.'
            );
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Execute
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
        $ip = $this->ipAddress->getIp();

        $countDone = 0;
        foreach ($subscriberIds as $subscriberId) {
            $subscriber = $this->subscriberFactory->create()->load($subscriberId);
            if ($subscriber->getId() <= 0) {
                continue;
            }

            $this->logger->info(__('%1 | Try to unsubscribe email %2', $this->name, $subscriber->getSubscriberEmail()));

            $store = $this->storeManager->getStore($subscriber->getStoreId());
            try {
                $this->unsubscribeEmail->execute(
                    $this->getUnsubscribeEmailContext($subscriber, $store, $ip)
                );

                $this->logger->info(__('%1 | Unsubscribed email %2', $this->name, $subscriber->getSubscriberEmail()));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $this->executeSubscriberAction($subscriber);
            $countDone++;
        }

        return $countDone;
    }

    /**
     * Build context for email unsubscription API call.
     *
     * @param Subscriber $subscriber
     * @param StoreInterface $store
     * @param string $ip
     * @return UnsubscribeEmailContext
     */
    public function getUnsubscribeEmailContext($subscriber, $store, $ip)
    {
        return $this->unsubscribeEmailContextFactory->create()
            ->setEmail($subscriber->getSubscriberEmail())
            ->setStore($store)
            ->setIp($ip);
    }

    /**
     * Trigger the unsubscription action for a subscriber.
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function executeSubscriberAction($subscriber)
    {
        if ($subscriber->getSubscriberId() > 0) {
            $subscriber->unsubscribe();
            $this->logger->info(__('Executed Magento unsubscribe email %1', $subscriber->getSubscriberEmail()));
        }
    }
}

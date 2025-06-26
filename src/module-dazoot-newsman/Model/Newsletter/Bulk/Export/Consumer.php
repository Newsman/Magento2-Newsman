<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Export;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Config\Customer\GetAdditionalAttributes;
use Dazoot\Newsman\Model\Service\Context\ExportCsvSubscribersContext;
use Dazoot\Newsman\Model\Service\Context\ExportCsvSubscribersContextFactory;
use Dazoot\Newsman\Model\Service\ExportCsvSubscribers;
use Magento\Customer\Model\Customer;
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
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Dazoot\Newsman\Logger\Logger;

/**
 * Newsletter Bulk Export all newsletter subscribers by List ID Consumer
 * @see \Dazoot\Newsman\Model\Newsletter\Bulk\Export\Scheduler
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
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ExportCsvSubscribers
     */
    protected $exportCsvSubscribers;

    /**
     * @var ExportCsvSubscribersContextFactory
     */
    protected $exportContextFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GetAdditionalAttributes
     */
    protected $getAdditionalAttributes;

    /**
     * @var string
     */
    protected $name = 'Bulk Export Subscribers Consumer';

    /**
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param OperationManagementInterface $operationManagement
     * @param EntityManager $entityManager
     * @param CollectionFactory $collectionFactory
     * @param ExportCsvSubscribers $exportCsvSubscribers
     * @param ExportCsvSubscribersContextFactory $exportContextFactory
     * @param Config $config
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Logger $logger
     * @param GetAdditionalAttributes $getAdditionalAttributes
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        OperationManagementInterface $operationManagement,
        EntityManager $entityManager,
        CollectionFactory $collectionFactory,
        ExportCsvSubscribers $exportCsvSubscribers,
        ExportCsvSubscribersContextFactory $exportContextFactory,
        Config $config,
        CustomerCollectionFactory $customerCollectionFactory,
        Logger $logger,
        GetAdditionalAttributes $getAdditionalAttributes
    ) {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->operationManagement = $operationManagement;
        $this->entityManager = $entityManager;
        $this->collectionFactory = $collectionFactory;
        $this->exportCsvSubscribers = $exportCsvSubscribers;
        $this->exportContextFactory = $exportContextFactory;
        $this->config = $config;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->logger = $logger;
        $this->getAdditionalAttributes = $getAdditionalAttributes;
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
                    'Sorry, something went wrong during subscribers export. Please see log for details.'
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
                'Sorry, something went wrong during subscribers export. Please see log for details.'
            );
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Execute export subscribers CSV
     *
     * @param array $data
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($data)
    {
        $this->validateData($data);

        $storeIds = $data['store_ids'];
        $chunkSize = $data['chunk_size'];
        $step = $data['step'];

        $collection = $this->createSubscriberCollection($storeIds, $chunkSize, $step);
        $emails = $collection->getColumnValues('subscriber_email');
        if (empty($emails)) {
            return 0;
        }

        $customerCollection = $this->createCustomerCollection($storeIds, $emails);
        $customersData = $this->getCustomerData($customerCollection, $storeIds);

        $csvData = [];
        $iter = 0;
        /** @var Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            $firstname = '';
            $lastname = '';
            if (isset($customersData[$subscriber->getSubscriberEmail()])) {
                $firstname = $customersData[$subscriber->getSubscriberEmail()]['firstname'];
                $lastname = $customersData[$subscriber->getSubscriberEmail()]['lastname'];
            }
            $csvData[$iter] = $this->getRowData($subscriber, $firstname, $lastname, $customersData, $storeIds, $iter);
            $iter++;
        }
        $count = $iter;

        $this->logger->info(__(
            '%1 | Exporting %2 subscribers to Newsman, step %3, chunk size %4',
            $this->name,
            count($csvData),
            $step,
            $chunkSize
        ));

        // Assumes all in $storeIds have same API configuration
        $store = $this->storeManager->getStore(current($storeIds));
        $this->exportCsvSubscribers->execute(
            $this->getExportContext($csvData, $store, $storeIds)
        );

        $this->logger->info(__(
            '%1 | Exported %2 subscribers to Newsman, step %3, chunk size %4',
            $this->name,
            count($csvData),
            $step,
            $chunkSize
        ));

        return $count;
    }

    /**
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function validateData($data)
    {
        $listId = $data['list_id'];
        $storeIds = $data['store_ids'];
        $chunkSize = $data['chunk_size'];
        $step = $data['step'];

        if (empty($listId)) {
            throw new LocalizedException(__('List ID is empty.'));
        }
        if (empty($storeIds)) {
            throw new LocalizedException(__('No Store IDs found for List ID %1.', $listId));
        }
        if ($chunkSize <= 1) {
            throw new LocalizedException(__('Empty chunk size %1.', $chunkSize));
        }
        if ($step <= 0) {
            throw new LocalizedException(__('Empty step %1.', $step));
        }

        $userIds = $this->config->getUserIdsByStoreIds($storeIds);
        if (count($userIds) != 1) {
            throw new LocalizedException(
                __('Too many user IDs %1 in stores: %2.', implode(', ', $userIds), implode(', ', $storeIds))
            );
        }
    }

    /**
     * @param array $data
     * @param StoreInterface $store
     * @param array $storeIds
     * @return ExportCsvSubscribersContext
     * @throws LocalizedException
     */
    public function getExportContext($data, $store, $storeIds)
    {
        return $this->exportContextFactory->create()
            ->setCsvData($data)
            ->setStore($store)
            ->setAdditionalFields($this->getAdditionalAttributes($storeIds));
    }

    /**
     * @param Subscriber $subscriber
     * @param string $firstname
     * @param string $lastname
     * @param array $customersData
     * @param array $storeIds
     * @param int $iteration
     * @return array
     * @throws LocalizedException
     */
    public function getRowData($subscriber, $firstname, $lastname, $customersData, $storeIds, $iteration)
    {
        $row = [
            'email' => $subscriber->getSubscriberEmail(),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'additional' => [],
        ];

        foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $field) {
            $row['additional'][$attributeCode] = '';
        }

        if (isset($customersData[$subscriber->getSubscriberEmail()])) {
            foreach ($this->getAdditionalAttributes($storeIds) as $attributeCode => $field) {
                $value = $customersData[$subscriber->getSubscriberEmail()][$attributeCode];
                if ($value === null) {
                    $value = '';
                }
                $row['additional'][$attributeCode] = $value;
            }
        }

        return $row;
    }

    /**
     * Create subscriber collection
     *
     * @param array $storeIds
     * @param int $chunkSize
     * @param int $step
     * @return Collection
     */
    public function createSubscriberCollection($storeIds, $chunkSize, $step)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED)
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->setPageSize($chunkSize)
            ->setCurPage($step);

        return $this->processSubscriberCollection($collection, $storeIds, $chunkSize, $step);
    }

    /**
     * Process subscriber collection for 3rd party plugins
     *
     * @param Collection $collection
     * @param array $storeIds
     * @param int $chunkSize
     * @param int $step
     * @return Collection
     */
    public function processSubscriberCollection($collection, $storeIds, $chunkSize, $step)
    {
        return $collection;
    }

    /**
     * Create customer collection
     *
     * @param array $storeIds
     * @param array $emails
     * @return CustomerCollection
     * @throws LocalizedException
     */
    public function createCustomerCollection($storeIds, $emails)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        /** @var CustomerCollection $collection */
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname'])
            ->addAttributeToSelect('email', ['in' => $emails])
            ->addAttributeToFilter('store_id', ['in' => $storeIds]);

        if (!empty($additionalAttributes)) {
            $collection->addAttributeToSelect(array_keys($additionalAttributes));
        }

        return $this->processCustomerCollection($collection, $storeIds, $emails);
    }

    /**
     * Process customer collection for 3rd party plugins
     *
     * @param CustomerCollection $collection
     * @param array $storeIds
     * @param array $emails
     * @return CustomerCollection
     */
    public function processCustomerCollection($collection, $storeIds, $emails)
    {
        return $collection;
    }

    /**
     * @param CustomerCollection $collection
     * @return array
     * @throws LocalizedException
     */
    public function getCustomerData($collection, $storeIds)
    {
        $additionalAttributes = $this->getAdditionalAttributes($storeIds);

        $customersData = [];
        /** @var Customer $customer */
        foreach ($collection as $customer) {
            $customersData[$customer->getEmail()] = [
                'entity_id' => $customer->getEmail(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
            ];

            foreach ($additionalAttributes as $attributeCode => $field) {
                $customersData[$customer->getEmail()][$attributeCode] = $customer->getResource()
                    ->getAttribute($attributeCode)
                    ->getFrontend()
                    ->getValue($customer);

                if ($customersData[$customer->getEmail()][$attributeCode] === false) {
                    $customersData[$customer->getEmail()][$attributeCode] = '';
                }
            }
        }

        return $customersData;
    }

    /**
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalAttributes($storeIds)
    {
        return $this->getAdditionalAttributes->get($storeIds);
    }
}

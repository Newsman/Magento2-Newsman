<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Export;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Config\Source\Lists as ListsSource;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter subscribers export bulk operations scheduler by List ID
 */
class Scheduler
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * @var BulkManagementInterface
     */
    protected $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    protected $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    protected $identityService;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ListsSource
     */
    protected $listsSource;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param UserContextInterface $userContext
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param SerializerInterface $serializer
     * @param ListsSource $listsSource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Config $config,
        UserContextInterface $userContext,
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        SerializerInterface $serializer,
        ListsSource $listsSource,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->userContext = $userContext;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->listsSource = $listsSource;
        $this->storeManager = $storeManager;
    }

    /**
     * Schedule the asynchronous bulk export of newsletter subscribers.
     *
     * @param int $listId
     * @return void
     */
    public function execute($listId)
    {
        $this->count = 0;
        if (empty($listId)) {
            return;
        }
        $listName = $this->listsSource->getLabelByValue($listId);
        $storeIds = $this->config->getStoreIdsByListId($listId);
        if (empty($storeIds)) {
            return;
        }
        $userIds = $this->config->getUserIdsByStoreIds($storeIds);
        if (count($userIds) != 1) {
            $storeNames = [];
            foreach ($storeIds as $storeId) {
                $storeNames[] = $this->storeManager->getStore($storeId)->getName();
            }
            throw new LocalizedException(
                __(
                    'Newsman API is not configured correctly for the stores %1. Found %2 configured user IDs: %3. ' .
                        'There should be only one user ID.',
                    implode(', ', $storeNames),
                    count($userIds),
                    implode(', ', $userIds),
                )
            );
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('subscriber_status', Subscriber::STATUS_SUBSCRIBED)
            ->addFieldToFilter('store_id', ['in' => $storeIds]);
        $this->count = $collection->getSize();
        if (!$this->count) {
            return;
        }
        $batchSize = $this->config->getApiExportSubscribersBatchSize();

        $bulkUuid = $this->identityService->generateId();

        /** @var OperationInterface[] $operations */
        $operations = [];
        $chunks = array_fill(0, ceil($this->count / $batchSize), $batchSize);
        $step = 0;
        foreach ($chunks as $chunkSize) {
            $step++;
            $dataToEncode = [
                'meta_information' => __(
                    'Export newsletter subscribers to list "%1", step %2.',
                    $listName,
                    $step
                ),
                'list_id' => $listId,
                'store_ids' => $storeIds,
                'chunk_size' => $chunkSize,
                'step' => $step,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $data = [
                'data' => [
                    'bulk_uuid' => $bulkUuid,
                    'topic_name' => 'dazoot_newsman.newsletter.bulk.export.list',
                    'serialized_data' => $this->serializer->serialize($dataToEncode),
                    'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN
                ]
            ];

            $operations[] = $this->operationFactory->create($data);
        }

        if (empty($operations)) {
            $this->count = 0;
            return;
        }

        $result = $this->bulkManagement->scheduleBulk(
            $bulkUuid,
            $operations,
            __(
                'Export %1 newsletter subscribers to list "%2"',
                $this->count,
                $listName
            ),
            $this->userContext->getUserId()
        );
        if (!$result) {
            throw new LocalizedException(__('Something went wrong while processing the request.'));
        }
    }

    /**
     * Retrieve the total number of subscribers identified for export.
     *
     * @return int
     */
    public function getCountSubscribers()
    {
        return $this->count;
    }
}

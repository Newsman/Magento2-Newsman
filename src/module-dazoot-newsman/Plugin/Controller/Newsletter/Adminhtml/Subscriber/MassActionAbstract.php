<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Controller\Newsletter\Adminhtml\Subscriber;

use Closure;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Dazoot\Newsman\Model\Config;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassActionAbstract
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

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
     * @param CollectionFactory $collectionFactory
     * @param SubscriberFactory $subscriberFactory
     * @param Config $config
     * @param MessageManagerInterface $messageManager
     * @param UserContextInterface $userContext
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        SubscriberFactory $subscriberFactory,
        Config $config,
        MessageManagerInterface $messageManager,
        UserContextInterface $userContext,
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        SerializerInterface $serializer
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->userContext = $userContext;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
    }

    /**
     * Publish bulk operations for subscriber mass actions.
     *
     * @param array $subscriberIds
     * @param string $queue
     * @param string $meta
     * @param string $bulkDescription
     * @return void
     * @throws LocalizedException
     */
    protected function publish($subscriberIds, $queue, $meta, $bulkDescription)
    {
        $subscriberIdsChunks = array_chunk($subscriberIds, $this->config->getApiMassUnsubscribeLimit());
        $bulkUuid = $this->identityService->generateId();

        $step = 0;
        $operations = [];
        foreach ($subscriberIdsChunks as $subscriberIdsChunk) {
            $step++;
            $operations[] = $this->makeOperation(
                __($meta, $step),
                $queue,
                $subscriberIdsChunk,
                $bulkUuid
            );
        }

        if (empty($operations)) {
            return;
        }

        $result = $this->bulkManagement->scheduleBulk(
            $bulkUuid,
            $operations,
            $bulkDescription,
            $this->userContext->getUserId()
        );
        if (!$result) {
            throw new LocalizedException(__('Something went wrong while processing the request.'));
        }
    }

    /**
     * Make asynchronous operation
     *
     * @param string $meta
     * @param string $queue
     * @param array $subscriberIds
     * @param int $bulkUuid
     * @return OperationInterface
     */
    protected function makeOperation(
        $meta,
        $queue,
        $subscriberIds,
        $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'meta_information' => $meta,
            'subscriber_ids' => $subscriberIds,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $queue,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }

    /**
     * Build redirect response for mass actions.
     *
     * @param \Magento\Newsletter\Controller\Adminhtml\Subscriber $subject
     * @param string $path
     * @param array $arguments
     * @return ResponseInterface
     */
    protected function redirect($subject, $path, $arguments = [])
    {
        $subject->getResponse()->setRedirect($subject->getUrl($path, $arguments));
        return $subject->getResponse();
    }
}

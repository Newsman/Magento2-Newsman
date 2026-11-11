<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Controller\Customer\Adminhtml\Index;

use Closure;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Newsletter\Bulk\Sync\Collector as SyncCollector;
use Dazoot\Newsman\Plugin\Controller\Newsletter\Adminhtml\Subscriber\MassActionAbstract;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Controller\Adminhtml\Index\AbstractMassAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Admin customer grid mass "Subscribe to Newsletter" / "Unsubscribe from Newsletter": Magento updates the
 * subscriptions in the request as usual, the Newsman calls are sent by the queue consumer, one per subscriber.
 *
 * Falls back to the synchronous calls of the subscribe / unsubscribe observer when the queue does not exist yet
 * (MySQL queue of an existing installation before setup:upgrade).
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassSubscription extends MassActionAbstract
{
    /**
     * Topic and queue name
     */
    public const TOPIC = 'dazoot_newsman.newsletter.bulk.sync';

    /**
     * @var SyncCollector
     */
    protected $syncCollector;

    /**
     * @var PublisherConfig
     */
    protected $publisherConfig;

    /**
     * @var ConnectionTypeResolver
     */
    protected $connectionTypeResolver;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

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
     * @param SyncCollector $syncCollector
     * @param PublisherConfig $publisherConfig
     * @param ConnectionTypeResolver $connectionTypeResolver
     * @param ResourceConnection $resourceConnection
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
        SerializerInterface $serializer,
        SyncCollector $syncCollector,
        PublisherConfig $publisherConfig,
        ConnectionTypeResolver $connectionTypeResolver,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct(
            $collectionFactory,
            $subscriberFactory,
            $config,
            $messageManager,
            $userContext,
            $bulkManagement,
            $operationFactory,
            $identityService,
            $serializer
        );
        $this->syncCollector = $syncCollector;
        $this->publisherConfig = $publisherConfig;
        $this->connectionTypeResolver = $connectionTypeResolver;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Run the mass action with the Newsman calls collected, then queue them
     *
     * @param AbstractMassAction $subject
     * @param Closure $proceed
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AbstractMassAction $subject, Closure $proceed)
    {
        if (!$this->config->isEnabledInAny() || !$this->isQueueReady()) {
            return $proceed();
        }

        $this->syncCollector->start();
        try {
            $result = $proceed();
        } finally {
            $subscribers = $this->syncCollector->stop();
        }

        $subscriberIds = [];
        foreach ($subscribers as $subscriber) {
            if ($subscriber->getId() > 0) {
                $subscriberIds[(int) $subscriber->getId()] = (int) $subscriber->getId();
            }
        }
        if (empty($subscriberIds)) {
            return $result;
        }

        try {
            $this->publish(
                array_values($subscriberIds),
                self::TOPIC,
                'Send newsletter subscription changes to Newsman chunk %1',
                __('Send newsletter subscription changes to Newsman for %1 emails', count($subscriberIds))
            );
            $this->messageManager->addSuccessMessage(__('Message is added to queue'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('The Newsman update could not be added to the queue: %1', $e->getMessage())
            );
        }

        return $result;
    }

    /**
     * Is the queue ready: always for a broker, for the MySQL queue when setup:upgrade has created it
     *
     * @return bool
     */
    protected function isQueueReady()
    {
        try {
            $connectionName = $this->publisherConfig->getPublisher(self::TOPIC)->getConnection()->getName();
            if ($this->connectionTypeResolver->getConnectionType($connectionName) !== 'db') {
                return true;
            }

            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName('queue'), ['id'])
                ->where('name = ?', self::TOPIC);

            return (bool) $connection->fetchOne($select);
        } catch (\Exception $e) {
            return false;
        }
    }
}

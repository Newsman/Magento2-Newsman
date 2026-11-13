<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Controller\Newsletter\Adminhtml\Subscriber;

use Closure;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Newsletter\Anonymiser;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Anonymise the emails in Newsman and delete the newsletter subscribers using bulk async operations.
 *
 * When "Anonymise in Newsman when customers are deleted" is No, the emails are unsubscribed instead.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDelete extends MassActionAbstract
{
    /**
     * @var Anonymiser
     */
    protected $anonymiser;

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
     * @param Anonymiser $anonymiser
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        Anonymiser $anonymiser
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
        $this->anonymiser = $anonymiser;
    }

    /**
     * Create queue message to anonymise emails in Newsman and delete subscribers
     *
     * @param \Magento\Newsletter\Controller\Adminhtml\Subscriber\MassDelete $subject
     * @param Closure $proceed
     * @return void
     * @throws LocalizedException
     */
    public function aroundExecute(
        \Magento\Newsletter\Controller\Adminhtml\Subscriber\MassDelete $subject,
        Closure $proceed
    ) {
        if (!$this->config->isEnabledInAny()) {
            return $proceed();
        }

        $subscribersIds = $subject->getRequest()->getParam('subscriber');
        if (!(is_array($subscribersIds) && !empty($subscribersIds))) {
            return $proceed();
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('subscriber_id', ['in' => $subscribersIds]);
        $storeIds = $collection->getColumnValues('store_id');
        $newsmanStoreIds = [];
        foreach ($storeIds as $storeId) {
            if ($this->config->isEnabled($storeId)) {
                $newsmanStoreIds[] = $storeId;
            }
        }
        if (empty($newsmanStoreIds)) {
            return $proceed();
        }

        $subscriberIdsNewsman = [];
        $countExecuted = 0;
        try {
            /** @var Subscriber $aSubscriber */
            foreach ($collection as $aSubscriber) {
                if (in_array($aSubscriber->getStoreId(), $newsmanStoreIds)) {
                    $subscriberIdsNewsman[] = $aSubscriber->getSubscriberId();
                } else {
                    $subscriber = $this->subscriberFactory->create()->load(
                        $aSubscriber->getSubscriberId()
                    );
                    $subscriber->delete();
                    $countExecuted++;
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        if ($countExecuted > 0) {
            $this->messageManager->addSuccessMessage(
                __('Total of %1 record(s) were deleted.', $countExecuted)
            );
        }

        if (empty($subscriberIdsNewsman)) {
            $this->redirect($subject, '*/*/index');
            return;
        }

        try {
            $actions = [];
            foreach (array_unique($newsmanStoreIds) as $storeId) {
                $actions[$this->anonymiser->getDeleteAction($storeId)] = true;
            }
            if (!isset($actions[Anonymiser::ACTION_UNSUBSCRIBE])) {
                $meta = 'Anonymise emails in Newsman and delete subscriber chunk %1';
                $description = __(
                    'Anonymise %1 emails in Newsman and delete the subscribers in Magento',
                    count($subscriberIdsNewsman)
                );
            } elseif (!isset($actions[Anonymiser::ACTION_ANONYMISE])) {
                $meta = 'Unsubscribe emails from Newsman and delete subscriber chunk %1';
                $description = __(
                    'Unsubscribe %1 emails from Newsman and delete the subscribers in Magento',
                    count($subscriberIdsNewsman)
                );
            } else {
                $meta = 'Anonymise or unsubscribe emails in Newsman and delete subscriber chunk %1';
                $description = __(
                    'Anonymise or unsubscribe %1 emails in Newsman and delete the subscribers in Magento',
                    count($subscriberIdsNewsman)
                );
            }

            $this->publish(
                $subscriberIdsNewsman,
                'dazoot_newsman.newsletter.bulk.delete',
                $meta,
                $description
            );
            $this->messageManager->addSuccessMessage(__('Message is added to queue'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong during unsubscribe.'));
        }

        $this->redirect($subject, '*/*/index');
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Controller\Newsletter\Adminhtml\Subscriber;

use Closure;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class send to API unsubscribe from Newsman newsletter using bulk async operations.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassUnsubscribe extends MassActionAbstract
{
    /**
     * Create queue message to unsubscribe emails from Newsman or unsubscribe subscribers
     *
     * @param \Magento\Newsletter\Controller\Adminhtml\Subscriber\MassUnsubscribe $subject
     * @param Closure $proceed
     * @return void
     * @throws LocalizedException
     */
    public function aroundExecute(
        \Magento\Newsletter\Controller\Adminhtml\Subscriber\MassUnsubscribe $subject,
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
                if ($aSubscriber->getStatus() == Subscriber::STATUS_UNSUBSCRIBED) {
                    continue;
                }

                if (in_array($aSubscriber->getStoreId(), $newsmanStoreIds)) {
                    $subscriberIdsNewsman[] = $aSubscriber->getSubscriberId();
                } else {
                    $subscriber = $this->subscriberFactory->create()->load(
                        $aSubscriber->getSubscriberId()
                    );
                    $subscriber->unsubscribe();
                    $countExecuted++;
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        if ($countExecuted > 0) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) were updated.', count($countExecuted))
            );
        }

        if (empty($subscriberIdsNewsman)) {
            $this->redirect($subject, '*/*/index');
            return;
        }

        try {
            $this->publish(
                $subscriberIdsNewsman,
                'dazoot_newsman.newsletter.bulk.unsubscribe',
                'Unsubscribe emails from Newsman newsletter and unsubscribe in Magento chunk %1',
                __(
                    'Newsletter unsubscribe from Newsman for %1 emails and unsubscribe in Magento',
                    count($subscriberIdsNewsman)
                )
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

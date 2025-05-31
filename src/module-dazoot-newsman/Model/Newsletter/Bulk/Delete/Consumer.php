<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Delete;

use Magento\Newsletter\Model\Subscriber;

/**
 * Newsletter Bulk Delete Consumer
 */
class Consumer extends \Dazoot\Newsman\Model\Newsletter\Bulk\Unsubscribe\Consumer
{
    /**
     * @var string
     */
    protected $name = 'Bulk Delete Subscriber Consumer';

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function executeSubscriberAction($subscriber)
    {
        if ($subscriber->getSubscriberId() > 0) {
            $subscriber->delete();
            $this->logger->info(
                __('Executed Magento delete subscriber with email %1', $subscriber->getSubscriberEmail())
            );
        }
    }
}

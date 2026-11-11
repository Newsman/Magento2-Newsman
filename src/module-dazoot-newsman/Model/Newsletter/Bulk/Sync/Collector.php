<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter\Bulk\Sync;

use Magento\Newsletter\Model\Subscriber;

/**
 * Collects the newsletter subscribers saved during an admin mass action, so they are sent to Newsman from the queue
 * instead of one API call per subscriber in the admin request.
 *
 * @see \Dazoot\Newsman\Plugin\Controller\Customer\Adminhtml\Index\MassSubscription
 * @see \Dazoot\Newsman\Observer\Newsletter\SubscribeUnsubscribeObserver::execute()
 */
class Collector
{
    /**
     * @var bool
     */
    protected $collecting = false;

    /**
     * @var Subscriber[]
     */
    protected $subscribers = [];

    /**
     * Start collecting subscribers
     *
     * @return void
     */
    public function start()
    {
        $this->collecting = true;
        $this->subscribers = [];
    }

    /**
     * Is collecting subscribers
     *
     * @return bool
     */
    public function isCollecting()
    {
        return $this->collecting;
    }

    /**
     * Add a subscriber that is about to be saved
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function add(Subscriber $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * Stop collecting and return the collected subscribers
     *
     * @return Subscriber[]
     */
    public function stop()
    {
        $subscribers = $this->subscribers;
        $this->collecting = false;
        $this->subscribers = [];

        return $subscribers;
    }
}

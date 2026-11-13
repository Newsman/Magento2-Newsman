<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types = 1);

namespace Dazoot\Newsman\Observer\Newsletter;

use Dazoot\Newsman\Model\Newsletter\Anonymiser;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Anonymise the email in the Newsman list after a newsletter subscriber is deleted in Magento.
 *
 * Magento deletes the newsletter subscribers of a customer when the customer is deleted (admin customer page, admin
 * customer grid mass delete, REST and GraphQL API, customer repository), for example on a GDPR request to erase the
 * customer data. A subscriber can also be deleted on its own. The delete never saves the subscriber, so the
 * subscribe / unsubscribe observer does not run.
 *
 * The email is anonymised whatever the subscriber status: an unsubscribed or not confirmed email can still be in
 * Newsman with personal data. Runs after the delete is committed, so only when the row is really gone.
 * A Newsman error is logged and never stops the delete.
 *
 * @see \Dazoot\Newsman\Observer\Customer\DeleteAnonymiseObserver
 */
class DeleteAnonymiseObserver implements ObserverInterface
{
    /**
     * @var Anonymiser
     */
    protected $anonymiser;

    /**
     * @param Anonymiser $anonymiser
     */
    public function __construct(Anonymiser $anonymiser)
    {
        $this->anonymiser = $anonymiser;
    }

    /**
     * Anonymise the email of the deleted subscriber in the Newsman list of its store view
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) : void
    {
        try {
            /** @var Subscriber $subscriber */
            $subscriber = $observer->getEvent()->getSubscriber();
            if (!($subscriber instanceof Subscriber)) {
                return;
            }

            /* @see \Dazoot\Newsman\Model\Newsletter\Bulk\Delete\Consumer::executeSubscriberAction() */
            if ($subscriber->getNewsmanSkipUnsubscribeFlag()) {
                return;
            }

            $email = (string) $subscriber->getSubscriberEmail();
            $this->anonymiser->anonymise(
                $email,
                [(int) $subscriber->getStoreId()],
                __('Subscriber deleted in Magento, anonymise email %1', $email)
            );
        } catch (\Throwable $e) {
            return;
        }
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types = 1);

namespace Dazoot\Newsman\Observer\Customer;

use Dazoot\Newsman\Model\Newsletter\Anonymiser;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Anonymise the email in the Newsman lists after a customer is deleted in Magento.
 *
 * When "Anonymise in Newsman when customers are deleted" is No, the email is unsubscribed instead (decided per
 * Newsman list by the Anonymiser).
 *
 * Covers every customer delete: admin customer page, admin customer grid mass delete, REST and GraphQL API, customer
 * repository in a CLI or cron process. The customer can be in Newsman without a newsletter subscriber row (customer or
 * order exports, an older subscription), so the email is anonymised in the distinct lists of the store views of the
 * customer's website (all store views when customer accounts are shared globally) where the extension is enabled.
 * The newsletter subscribers deleted with the customer do not send a second call for the same list.
 *
 * Runs after the delete is committed. A Newsman error is logged and never stops the delete.
 *
 * @see \Dazoot\Newsman\Observer\Newsletter\DeleteAnonymiseObserver
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
     * Anonymise the email of the deleted customer in the Newsman lists of its website
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) : void
    {
        try {
            /** @var Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            if (!($customer instanceof Customer)) {
                return;
            }

            $email = (string) $customer->getEmail();
            $this->anonymiser->anonymise(
                $email,
                $this->anonymiser->getCustomerStoreIds($customer->getWebsiteId()),
                __('Customer deleted in Magento, anonymise email %1', $email),
                __('Customer deleted in Magento, unsubscribe email %1', $email)
            );
        } catch (\Throwable $e) {
            return;
        }
    }
}

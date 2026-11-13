<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Model\Customer\AccountManagement;

use Dazoot\Newsman\Model\Newsletter\Anonymiser;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Do not anonymise in Newsman when Magento deletes the customer it was creating.
 *
 * When an address of a new account cannot be saved, Magento deletes the customer it has just saved (and its
 * newsletter subscriber) and shows the error. The person did not ask for a delete and usually registers again
 * right away, so nothing is sent to Newsman for that delete.
 *
 * @see \Magento\Customer\Model\AccountManagement::createAccountWithPasswordHash()
 */
class SkipAnonymiseOnCreateRollback
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
     * Suspend the anonymise on delete while the account is created
     *
     * @param AccountManagementInterface $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @param string $hash
     * @param string $redirectUrl
     * @return CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $hash,
        $redirectUrl = ''
    ) {
        $this->anonymiser->suspend();
        try {
            return $proceed($customer, $hash, $redirectUrl);
        } finally {
            $this->anonymiser->resume();
        }
    }
}

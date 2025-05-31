<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Plugin\CustomerData\Customer;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Customer\Helper\Session\CurrentCustomer;

/**
 * Add lastname and email to customer section data
 */
class AddCustomerData
{
    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param CurrentCustomer $currentCustomer
     * @param Config $config
     */
    public function __construct(
        CurrentCustomer $currentCustomer,
        Config $config
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->config = $config;
    }

    /**
     * @param \Magento\Customer\CustomerData\Customer $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(
        \Magento\Customer\CustomerData\Customer $subject,
        $result
    ) {
        if (!$this->config->isActive()) {
            return $result;
        }

        try {
            if (!$this->currentCustomer->getCustomerId()) {
                return $result;
            }
            $customer = $this->currentCustomer->getCustomer();
            $result['lastname'] = $customer->getLastname();
            $result['email'] = $customer->getEmail();
        } catch (\Exception $e) {
            return $result;
        }

        return $result;
    }
}

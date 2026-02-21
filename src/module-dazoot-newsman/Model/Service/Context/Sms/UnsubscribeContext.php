<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Sms;

use Dazoot\Newsman\Model\Service\Context\StoreContext;
use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Data transfer context for sms.saveUnsubscribe — unsubscribes a telephone number from an SMS list.
 */
class UnsubscribeContext extends StoreContext
{
    /**
     * Telephone number.
     *
     * @var string
     */
    protected $telephone = '';

    /**
     * Subscriber IP address.
     *
     * @var string
     */
    protected $ip = '';

    /**
     * Set telephone number.
     *
     * @param string $telephone
     * @return ContextInterface
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * Get telephone number.
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set subscriber IP address.
     *
     * @param string $ip
     * @return ContextInterface
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Get subscriber IP address.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}

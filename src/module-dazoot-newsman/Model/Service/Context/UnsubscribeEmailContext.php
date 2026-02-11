<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context;

use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Unsubscribe email address data transfer context
 */
class UnsubscribeEmailContext extends StoreContext
{
    /**
     * Email address.
     *
     * @var string
     */
    protected $email;

    /**
     * IP address.
     *
     * @var string
     */
    protected $ip;

    /**
     * Set email address.
     *
     * @param string $email
     * @return ContextInterface
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Retrieve email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set IP address.
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
     * Retrieve IP address.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}

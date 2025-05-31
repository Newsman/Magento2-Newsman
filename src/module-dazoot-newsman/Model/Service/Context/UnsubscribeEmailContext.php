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
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @param string $email
     * @return ContextInterface
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $ip
     * @return ContextInterface
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}

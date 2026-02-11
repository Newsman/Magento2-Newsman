<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\User;

/**
 * Get user IP address interface
 */
interface IpAddressInterface
{
    /**
     * Get user IP address.
     *
     * @return string
     */
    public function getIp();
}

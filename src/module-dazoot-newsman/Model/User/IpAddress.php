<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\User;

use Dazoot\Newsman\Model\Config;
use Magento\Framework\App\RequestInterface;

/**
 * User IP Address
 */
class IpAddress implements IpAddressInterface
{
    /**
     * Request object
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HostIpAddress
     */
    protected $hostIpAddress;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @param RequestInterface $request
     * @param Config $config
     * @param HostIpAddress $hostIpAddress
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        RequestInterface $request,
        Config $config,
        HostIpAddress $hostIpAddress,
        RemoteAddress $remoteAddress
    ) {
        $this->request = $request;
        $this->config = $config;
        $this->hostIpAddress = $hostIpAddress;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritdoc
     */
    public function getIp()
    {
        if ($this->config->getDeveloperUserIp()) {
            return $this->config->getDeveloperUserIp();
        }

        if (!$this->config->isSendUserIp()) {
            return $this->hostIpAddress->getIp();
        }

        $ip = $this->remoteAddress->getRemoteAddress();

        if ($ip === '127.0.0.1' || empty($ip)) {
            return $this->hostIpAddress->getIp();
        }

        return $ip;
    }
}

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
     * @param RequestInterface $request
     * @param Config $config
     * @param HostIpAddress $hostIpAddress
     */
    public function __construct(
        RequestInterface $request,
        Config $config,
        HostIpAddress $hostIpAddress
    ) {
        $this->request = $request;
        $this->config = $config;
        $this->hostIpAddress = $hostIpAddress;
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

        if ($this->request->getServer('HTTP_X_REAL_IP')) {
            $ip = $this->request->getServer('HTTP_X_REAL_IP');
        } elseif ($this->request->getServer('HTTP_CLIENT_IP')) {
            $ip = $this->request->getServer('HTTP_CLIENT_IP');
        } elseif ($this->request->getServer('HTTP_X_FORWARDED_FOR')) {
            $ip = $this->request->getServer('HTTP_X_FORWARDED_FOR');
        } elseif ($this->request->getServer('HTTP_X_FORWARDED')) {
            $ip = $this->request->getServer('HTTP_X_FORWARDED');
        } elseif ($this->request->getServer('HTTP_FORWARDED_FOR')) {
            $ip = $this->request->getServer('HTTP_FORWARDED_FOR');
        } elseif ($this->request->getServer('HTTP_FORWARDED')) {
            $ip = $this->request->getServer('HTTP_FORWARDED');
        } elseif ($this->request->getServer('REMOTE_ADDR')) {
            $ip = $this->request->getServer('REMOTE_ADDR');
        } else {
            $ip = false;
        }

        if ($ip === '127.0.0.1' || $ip === false) {
            return $this->hostIpAddress->getIp();
        }

        if (!empty($ip)) {
            $ip = $this->getOneIp($ip);
        }

        return $ip;
    }

    /**
     * @param string $ip
     * @return string
     */
    public function getOneIp($ip)
    {
        if (empty($ip) || !is_string($ip)) {
            return $ip;
        }

        $ip = trim(trim(trim($ip), ','));
        $arr = explode(',', $ip);
        if (!(is_array($ip) && count($arr) > 1)) {
            return $ip;
        }

        reset($arr);
        $return = current($arr);
        return trim($return);
    }
}

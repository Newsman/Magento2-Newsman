<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dazoot\Newsman\Model\User;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Changes:
 * - Get last proxy from HTTP_X_FORWARDED_FOR as it may be a real IP address.
 * - Added FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE on filter_var.
  */
class RemoteAddress implements ResetAfterRequestInterface
{
    /**
     * Request object.
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Remote address cache.
     *
     * @var string|null|bool|number
     */
    protected $remoteAddress;

    /**
     * @var array
     */
    protected $alternativeHeaders;

    /**
     * @var string[]|null
     */
    protected $trustedProxies;


    /**
     * Constructor
     *
     * @param RequestInterface $httpRequest
     * @param array $alternativeHeaders
     * @param string[]|null $trustedProxies
     */
    public function __construct(
        RequestInterface $httpRequest,
        $alternativeHeaders = [],
        $trustedProxies = null
    ) {
        $this->request = $httpRequest;
        $this->alternativeHeaders = $alternativeHeaders;
        $this->trustedProxies = $trustedProxies;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->remoteAddress = null;
    }

    /**
     * Read address based on settings.
     *
     * @return string|null
     */
    public function readAddress()
    {
        $remoteAddress = null;
        foreach ($this->getAlternativeHeaders() as $var) {
            if ($this->request->getServer($var, false)) {
                $remoteAddress = $this->request->getServer($var);
                break;
            }
        }

        if (!$remoteAddress) {
            $remoteAddress = $this->request->getServer('REMOTE_ADDR');
        }

        return $remoteAddress;
    }

    /**
     * Filter addresses by trusted proxies list.
     *
     * @param string $remoteAddress
     * @return string|null
     */
    public function filterAddress($remoteAddress)
    {
        if (strpos($remoteAddress, ',') !== false) {
            $ipList = explode(',', $remoteAddress);
        } else {
            $ipList = [$remoteAddress];
        }
        $ipList = array_filter(
            $ipList,
            function (string $ip) {
                return filter_var(
                    trim($ip),
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                );
            }
        );
        if ($this->trustedProxies !== null) {
            $ipList = array_filter(
                $ipList,
                function (string $ip) {
                    return !in_array(trim($ip), $this->trustedProxies, true);
                }
            );
            $remoteAddress = empty($ipList) ? '' : trim(array_pop($ipList));
        } else {
            // Change: Get last proxy from HTTP_X_FORWARDED_FOR as it may be a real IP address.
            reset($ipList);
            $remoteAddress = trim(end($ipList));
        }

        return $remoteAddress ? $remoteAddress : null;
    }

    /**
     * Retrieve Client Remote Address.
     * If alternative headers are used and said headers allow multiple IPs
     * it is suggested that trusted proxies is also used
     * for more accurate IP recognition.
     *
     * @param bool $ipToLong converting IP to long format
     *
     * @return string IPv4|long
     */
    public function getRemoteAddress($ipToLong = false)
    {
        if ($this->remoteAddress !== null) {
            return $ipToLong ? ip2long($this->remoteAddress) : $this->remoteAddress;
        }

        $remoteAddress = $this->readAddress();
        if (!$remoteAddress) {
            $this->remoteAddress = false;

            return false;
        }
        $remoteAddress = $this->filterAddress($remoteAddress);

        if (!$remoteAddress) {
            $this->remoteAddress = false;

            return false;
        }

        $this->remoteAddress = $remoteAddress;

        return $ipToLong ? ip2long($this->remoteAddress) : $this->remoteAddress;
    }

    /**
     * Returns internet host name corresponding to remote server
     *
     * @return string|null
     */
    public function getRemoteHost()
    {
        return $this->getRemoteAddress()
            ? gethostbyaddr($this->getRemoteAddress())
            : null;
    }

    /**
     * @return array
     */
    public function getAlternativeHeaders()
    {
        return $this->alternativeHeaders;
    }
}

<?php

namespace Dazoot\Newsman\Model\User;

/**
 * Changes:
 * - Get last proxy from HTTP_X_FORWARDED_FOR as it may be a real IP address.
 * - Added FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE on filter_var.
 * @note A preference can be created on this class and $trustedProxies can be initialized in __construct().
 */
class RemoteAddress extends \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
{
    /**
     * @var array
     */
    protected $trustedProxies;

    /**
     * @inheritdoc
     */
    protected function readAddress()
    {
        $remoteAddress = null;
        foreach ($this->alternativeHeaders as $var) {
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
     * @inheritdoc
     */
    protected function filterAddress(string $remoteAddress)
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

        return $remoteAddress ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getRemoteAddress(bool $ipToLong = false)
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
}

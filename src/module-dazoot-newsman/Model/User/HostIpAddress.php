<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\User;

use Dazoot\Newsman\Model\Config;
use Magento\Config\Model\Config\Backend\Image\Logo;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Get server IP address
 */
class HostIpAddress implements IpAddressInterface
{
    /**
     * Not found value
     */
    public const NOT_FOUND = 'not found';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var string|null
     */
    protected $ip;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        Config $config,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @inheritdoc
     */
    public function getIp()
    {
        if ($this->ip !== null) {
            return $this->ip;
        }

        $ip = $this->config->getServerIp();
        if (!empty($ip)) {
            if ($ip === self::NOT_FOUND) {
                $this->ip = '';
            } else {
                $this->ip = $ip;
            }
            return $this->ip;
        }

        $ip = $this->lookupIp($this->getUrl());
        if (empty($ip)) {
            $ip = self::NOT_FOUND;
        }
        $this->configWriter->save(
            Config::XML_PATH_SERVER_IP,
            $ip,
            \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT
        );
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

        if ($ip === self::NOT_FOUND) {
            $this->ip = '';
        } else {
            $this->ip = $ip;
        }

        return $this->ip;
    }

    /**
     * Look up the IP address by making a HEAD request to the given URL.
     *
     * @param string $url
     * @return string
     */
    protected function lookupIp($url)
    {
        // @codingStandardsIgnoreStart
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_exec($ch);
        $ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        curl_close($ch);
        // @codingStandardsIgnoreEnd

        if (empty($ip) || $ip === '127.0.0.1') {
            return '';
        }

        return $ip;
    }

    /**
     * Retrieve the URL used for IP lookup (usually the store logo URL).
     *
     * @return string
     */
    public function getUrl()
    {
        $storeLogoPath = $this->scopeConfig->getValue('design/header/logo_src', ScopeInterface::SCOPE_STORE);
        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) .
            Logo::UPLOAD_DIR . '/' . $storeLogoPath;
    }
}

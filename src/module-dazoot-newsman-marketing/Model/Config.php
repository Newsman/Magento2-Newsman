<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config for Newsman Remarketing
 */
class Config
{
    /**
     * Active path
     */
    public const XML_PATH_ACTIVE = 'newsmanmarketing/general/enable';

    /**
     *  Remarketing ID path
     */
    public const XML_PATH_UA_ID = 'newsmanmarketing/general/ua_id';

    /**
     *  Use tunnel / proxy
     */
    public const XML_PATH_USE_TUNNEL = 'newsmanmarketing/general/use_tunnel';

    /**
     *  Anonymize IP address path
     */
    public const XML_PATH_ANONYMIZE_IP = 'newsmanmarketing/general/anonymize_ip';

    /**
     *  Get brand attribute path
     */
    public const XML_PATH_BRAND_ATTRIBUTE = 'newsmanmarketing/general/brand_attribute';

    /**
     *  Get brand attribute path
     */
    public const XML_PATH_SCRIPT_JS = 'newsmanmarketing/general/script_js';

    /**
     * Tracking script URL path
     */
    public const XML_PATH_TRACKING_SCRIPT_URL = 'newsmanmarketing/tracking/script_url';

    /**
     * HTTP resources URL path
     */
    public const XML_PATH_HTTP_RESOURCES_URL = 'newsmanmarketing/http/resources_url';

    /**
     * HTTP resources URL path
     */
    public const XML_PATH_HTTP_TRACKING_URL = 'newsmanmarketing/http/tracking_url';

    /**
     * Required files patterns path
     */
    public const XML_PATH_HTTP_REQUIRED_FILES_PATTERNS = 'newsmanmarketing/http/required_file_patterns';

    /**
     * Developer log tunnel
     */
    public const XML_PATH_DEVELOPER_LOG_TUNNEL = 'newsmanmarketing/developer/log_tunnel';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Dazoot\Newsman\Model\Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Dazoot\Newsman\Model\Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Dazoot\Newsman\Model\Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Is active
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isActive($store = null)
    {
        return $this->config->isActive($store) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store
        ) && !empty($this->getUaId($store));
    }

    /**
     * @return bool
     */
    public function isAnyActive()
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isActive($store)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Remarketing ID
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getUaId($store = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UA_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Use tunnel / proxy
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function useTunnel($store = null)
    {
        return $this->config->isActive($store) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_TUNNEL,
            ScopeInterface::SCOPE_STORE,
            $store
        ) && !empty($this->getUaId($store));
    }

    /**
     * Get anonymize IP address
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function getAnynymizeIp($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ANONYMIZE_IP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get brand attribute code
     *
     * @param null|string|bool|int|Store $store
     * @return string|null
     */
    public function getBrandAttribute($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BRAND_ATTRIBUTE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Newsman Remarketing JavaScript to include in pages
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getScriptJs($store = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SCRIPT_JS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get tracking script URL
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getScriptUrl($store = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_SCRIPT_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get HTTP resources URL
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getResourcesUrl($store = null)
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_HTTP_RESOURCES_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return rtrim($value, '/');
    }

    /**
     * Get HTTP tracking URL
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getTrackingUrl($store = null)
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_HTTP_TRACKING_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return rtrim($value, '/');
    }

    /**
     * Get required files patterns
     *
     * @param null|string|bool|int|Store $store
     * @return array
     */
    public function getRequiredFilePatterns($store = null)
    {
        $str = (string) $this->scopeConfig->getValue(
            self::XML_PATH_HTTP_REQUIRED_FILES_PATTERNS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        if (empty($str)) {
            return [];
        }
        $str = str_replace("\r", "\n", $str);
        $str = preg_replace('/\n{2,}/', "\n", $str);
        $arr = explode("\n", $str);
        if (empty($arr)) {
            return [];
        }
        $return = [];
        foreach ($arr as $pattern) {
            if (!empty($pattern)) {
                $return[] = trim($pattern);
            }
        }
        return $return;
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getScriptRequestUri($store = null)
    {
        $url = $this->getScriptUrl($store);
        if (empty($url)) {
            return '';
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlInfo = parse_url($url);
        if (isset($urlInfo['path']) && !empty($urlInfo['path'])) {
            $urlInfo['path'] = ltrim($urlInfo['path'], '/');
            if (empty($urlInfo['path'])) {
                return '';
            }
            return $urlInfo['path'];
        }
        return '';
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isLogTunnel($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEVELOPER_LOG_TUNNEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}

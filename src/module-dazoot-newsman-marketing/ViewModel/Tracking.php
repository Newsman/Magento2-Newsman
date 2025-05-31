<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel;

use Dazoot\Newsmanmarketing\Controller\Router;
use Dazoot\Newsmanmarketing\Model\Asset\Cache as AssetCache;
use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Track view model
 */
class Tracking implements ArgumentInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var AssetCache
     */
    protected $assetCache;

    /**
     * @var bool|null
     */
    protected $isTrackingJsCached;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param AssetCache $assetCache
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        AssetCache $assetCache
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->assetCache = $assetCache;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->config->isActive();
    }

    /**
     * @return string
     */
    public function getUaId()
    {
        return $this->config->getUaId();
    }

    /**
     * @return bool
     */
    public function useTunnel()
    {
        return $this->config->useTunnel();
    }

    /**
     * @return bool
     */
    public function getAnynymizeIp()
    {
        return $this->config->getAnynymizeIp();
    }

    /**
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function getResourcesUrl()
    {
        if ($this->isTrackingJsCached()) {
            return $this ->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $this->assetCache->getRelativeWorkPath();
        }

        return rtrim($this->urlBuilder->getUrl(Router::FRONT_NAME . '/' . Router::RESOURCES_IDENTIFIER), '/');
    }

    /**
     * @return bool
     * @throws FileSystemException
     */
    public function isTrackingJsCached()
    {
        if ($this->isTrackingJsCached === null) {
            $this->isTrackingJsCached = false;
            if ($this->assetCache->isAllRequiredFilesCached()) {
                $this->isTrackingJsCached = true;
            }
        }
        return $this->isTrackingJsCached;
    }

    /**
     * @return string
     */
    public function getTrackingUrl()
    {
        return rtrim($this->urlBuilder->getUrl(Router::FRONT_NAME . '/' . Router::TRACKING_IDENTIFIER), '/');
    }

    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->config->getScriptUrl();
    }

    /**
     * @return string
     */
    public function getScriptFinalUrl()
    {
        if ($this->config->useTunnel()) {
            return $this->getResourcesUrl() . '/' . $this->getScriptRequestUri();
        } else {
            return $this->getScriptUrl();
        }
    }

    /**
     * @return string
     */
    public function getScriptRequestUri()
    {
        return $this->config->getScriptRequestUri();
    }

    /**
     * JS with newsman config.
     * Example:
     *   _nzm_config['option_1'] = 1;
     *   _nzm_config['option_2'] = 'value';
     *
     * @return string
     */
    public function getNewsmanConfigJs()
    {
        return '';
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @return string
     */
    public function getScriptJs()
    {
        return $this->config->getScriptJs();
    }
}

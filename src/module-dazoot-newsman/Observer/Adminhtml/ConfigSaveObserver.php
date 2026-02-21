<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsman\Observer\Adminhtml;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsman\Model\Service\Configuration\Integration\SaveListIntegrationSetup;
use Dazoot\Newsman\Model\Service\Context\Configuration\SaveListIntegrationSetupContext;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Observer for Newsman admin system config save.
 *
 * Calls saveListIntegrationSetup when the list ID is changed via system configuration.
 */
class ConfigSaveObserver implements ObserverInterface
{
    /**
     * @var NewsmanConfig
     */
    protected $newsmanConfig;

    /**
     * @var SaveListIntegrationSetup
     */
    protected $saveIntegrationService;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ComposerInformation
     */
    protected $composerInformation;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param NewsmanConfig $newsmanConfig
     * @param SaveListIntegrationSetup $saveIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param ProductMetadataInterface $productMetadata
     * @param ComposerInformation $composerInformation
     * @param Logger $logger
     */
    public function __construct(
        NewsmanConfig $newsmanConfig,
        SaveListIntegrationSetup $saveIntegrationService,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        ProductMetadataInterface $productMetadata,
        ComposerInformation $composerInformation,
        Logger $logger
    ) {
        $this->newsmanConfig = $newsmanConfig;
        $this->saveIntegrationService = $saveIntegrationService;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->productMetadata = $productMetadata;
        $this->composerInformation = $composerInformation;
        $this->logger = $logger;
    }

    /**
     * Handle Newsman config section save event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $changedPaths = (array) $observer->getEvent()->getData('changed_paths');

        if (!in_array(NewsmanConfig::XML_PATH_CREDENTIALS_LIST_ID, $changedPaths, true)) {
            return;
        }

        $website = (string) $observer->getEvent()->getData('website');
        $store = (string) $observer->getEvent()->getData('store');

        try {
            $storeModel = $this->resolveStore($website, $store);
            $listId = $this->newsmanConfig->getListId($storeModel);

            if (empty($listId)) {
                return;
            }

            $userId = $this->newsmanConfig->getUserId($storeModel);
            $apiKey = $this->newsmanConfig->getApiKey($storeModel);

            if (empty($userId) || empty($apiKey)) {
                $this->logger->warning('ConfigSaveObserver: missing credentials, skipping integration setup.');
                return;
            }

            $authenticateToken = $this->newsmanConfig->getExportAuthenticateToken($storeModel);
            if (empty($authenticateToken)) {
                $authenticateToken = $this->generateRandomToken(32);
                [$scope, $scopeId] = $this->resolveScope($website, $store);
                $this->configWriter->save(
                    NewsmanConfig::XML_PATH_EXPORT_AUTHENTICATE_TOKEN,
                    $authenticateToken,
                    $scope,
                    $scopeId
                );
                $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            }

            $this->callSaveListIntegrationSetup($listId, $userId, $apiKey, $storeModel, $authenticateToken);
        } catch (\Exception $e) {
            $this->logger->error('ConfigSaveObserver: saveListIntegrationSetup failed: ' . $e->getMessage());
        }
    }

    /**
     * Call the saveListIntegrationSetup API endpoint.
     *
     * @param int $listId
     * @param int $userId
     * @param string $apiKey
     * @param \Magento\Store\Api\Data\StoreInterface|null $storeModel
     * @param string $authenticateToken
     * @return void
     * @throws \Exception
     */
    protected function callSaveListIntegrationSetup($listId, $userId, $apiKey, $storeModel, $authenticateToken)
    {
        $store = $storeModel ?: $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
        $apiUrl = rtrim($baseUrl, '/') . '/newsman/index/index';

        $pluginVersion = 'unknown';
        $packages = $this->composerInformation->getInstalledMagentoPackages();
        if (isset($packages[NewsmanConfig::COMPOSER_PACKAGE_NAME])) {
            $pluginVersion = $packages[NewsmanConfig::COMPOSER_PACKAGE_NAME]['version'];
        }

        $payload = [
            'api_url'                  => $apiUrl,
            'api_key'                  => $authenticateToken,
            'plugin_version'           => $pluginVersion,
            'platform_version'         => $this->productMetadata->getVersion(),
            'platform_language'        => 'PHP',
            'platform_language_version' => phpversion(),
        ];

        $context = new SaveListIntegrationSetupContext();
        $context->setUserId($userId)
            ->setApiKey($apiKey)
            ->setListId($listId)
            ->setIntegration('magento2')
            ->setPayload($payload);

        $this->saveIntegrationService->execute($context);
    }

    /**
     * Resolve the store model for reading config values.
     *
     * @param string $website
     * @param string $store
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    protected function resolveStore($website, $store)
    {
        try {
            if (!empty($store)) {
                return $this->storeManager->getStore($store);
            }
            if (!empty($website)) {
                $websiteModel = $this->storeManager->getWebsite($website);
                return $this->storeManager->getStore($websiteModel->getDefaultStore()->getId());
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    /**
     * Resolve saved configuration scope.
     *
     * @param string $website
     * @param string $store
     * @return array
     */
    protected function resolveScope($website, $store)
    {
        try {
            if (!empty($store)) {
                $storeModel = $this->storeManager->getStore($store);
                return [ScopeInterface::SCOPE_STORES, (int)$storeModel->getId()];
            }
            if (!empty($website)) {
                $websiteModel = $this->storeManager->getWebsite($website);
                return [ScopeInterface::SCOPE_WEBSITES, (int)$websiteModel->getId()];
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0];
    }

    /**
     * Generate a random alphanumeric token.
     *
     * @param int $length
     * @return string
     */
    protected function generateRandomToken($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($chars);
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $chars[random_int(0, $len - 1)];
        }
        return $token;
    }
}

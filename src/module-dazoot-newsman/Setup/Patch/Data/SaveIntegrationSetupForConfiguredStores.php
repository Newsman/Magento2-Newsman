<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Setup\Patch\Data;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsman\Model\Service\Configuration\Integration\SaveListIntegrationSetup;
use Dazoot\Newsman\Model\Service\Context\Configuration\SaveListIntegrationSetupContext;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Send integration setup to Newsman API for all stores that have Newsman credentials configured.
 */
class SaveIntegrationSetupForConfiguredStores implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var NewsmanConfig
     */
    protected $newsmanConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SaveListIntegrationSetup
     */
    protected $saveIntegrationService;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ComposerInformation
     */
    protected $composerInformation;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param NewsmanConfig $newsmanConfig
     * @param StoreManagerInterface $storeManager
     * @param SaveListIntegrationSetup $saveIntegrationService
     * @param ProductMetadataInterface $productMetadata
     * @param ComposerInformation $composerInformation
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        NewsmanConfig $newsmanConfig,
        StoreManagerInterface $storeManager,
        SaveListIntegrationSetup $saveIntegrationService,
        ProductMetadataInterface $productMetadata,
        ComposerInformation $composerInformation,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->newsmanConfig = $newsmanConfig;
        $this->storeManager = $storeManager;
        $this->saveIntegrationService = $saveIntegrationService;
        $this->productMetadata = $productMetadata;
        $this->composerInformation = $composerInformation;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $pluginVersion = 'unknown';
        $packages = $this->composerInformation->getInstalledMagentoPackages();
        if (isset($packages[NewsmanConfig::COMPOSER_PACKAGE_NAME])) {
            $pluginVersion = $packages[NewsmanConfig::COMPOSER_PACKAGE_NAME]['version'];
        }

        $tokenSaved = false;

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $userId = $this->newsmanConfig->getUserId($store);
            $apiKey = $this->newsmanConfig->getApiKey($store);
            $listId = $this->newsmanConfig->getListId($store);

            if (empty($userId) || empty($apiKey) || empty($listId)) {
                continue;
            }

            $authenticateToken = $this->newsmanConfig->getExportAuthenticateToken($store);
            if (empty($authenticateToken)) {
                $authenticateToken = $this->generateRandomToken(32);
                $this->configWriter->save(
                    NewsmanConfig::XML_PATH_EXPORT_AUTHENTICATE_TOKEN,
                    $authenticateToken,
                    ScopeInterface::SCOPE_STORES,
                    (int) $store->getId()
                );
                $tokenSaved = true;
            }

            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
            $apiUrl = rtrim($baseUrl, '/') . '/newsman/index/index';

            $serverAddr = (new \Dazoot\Newsman\Model\Util\ServerIpResolver())->resolve();
            $payload = [
                'api_url'                   => $apiUrl,
                'api_key'                   => $authenticateToken,
                'plugin_version'            => $pluginVersion,
                'platform_name'             => $this->productMetadata->getName(),
                'platform_version'          => $this->productMetadata->getVersion(),
                'platform_language'         => 'PHP',
                'platform_language_version' => phpversion(),
                'platform_server_ip'        => $serverAddr,
            ];

            $context = new SaveListIntegrationSetupContext();
            $context->setUserId($userId)
                ->setApiKey($apiKey)
                ->setListId($listId)
                ->setIntegration('magento2')
                ->setPayload($payload);

            try {
                $this->saveIntegrationService->execute($context);
            } catch (\Exception $e) {
                $this->logger->error(
                    'SaveIntegrationSetupForConfiguredStores patch: store ' . $store->getId() . ': ' . $e->getMessage()
                );
            }
        }

        if ($tokenSaved) {
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
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

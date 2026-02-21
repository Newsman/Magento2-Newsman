<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsman\Model\Service\Configuration\Integration\SaveListIntegrationSetup;
use Dazoot\Newsman\Model\Service\Context\Configuration\SaveListIntegrationSetupContext;
use Dazoot\Newsman\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class save list ID configuration action
 */
class SaveConfigureList extends Action
{
    /**
     * Authorization level of a basic admin session.
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * Interface for writing configuration data.
     *
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;

    /**
     * Cache type list (for cleaning config cache).
     *
     * @var TypeListInterface
     */
    protected TypeListInterface $cacheTypeList;

    /**
     * Store manager instance.
     *
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * Newsman config reader.
     *
     * @var NewsmanConfig
     */
    protected NewsmanConfig $newsmanConfig;

    /**
     * Save integration setup service.
     *
     * @var SaveListIntegrationSetup
     */
    protected SaveListIntegrationSetup $saveIntegrationService;

    /**
     * Magento product metadata.
     *
     * @var ProductMetadataInterface
     */
    protected ProductMetadataInterface $productMetadata;

    /**
     * Composer information for reading installed package versions.
     *
     * @var ComposerInformation
     */
    protected ComposerInformation $composerInformation;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * SaveConfigureList constructor.
     *
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param NewsmanConfig $newsmanConfig
     * @param SaveListIntegrationSetup $saveIntegrationService
     * @param ProductMetadataInterface $productMetadata
     * @param ComposerInformation $composerInformation
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        NewsmanConfig $newsmanConfig,
        SaveListIntegrationSetup $saveIntegrationService,
        ProductMetadataInterface $productMetadata,
        ComposerInformation $composerInformation,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
        $this->newsmanConfig = $newsmanConfig;
        $this->saveIntegrationService = $saveIntegrationService;
        $this->productMetadata = $productMetadata;
        $this->composerInformation = $composerInformation;
        $this->logger = $logger;
    }

    /**
     * Persist selected list ID for the current scope and redirect back.
     *
     * @return \Magento\Framework\App\ResponseInterface|Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $listId = (int)$this->getRequest()->getParam('list_id');
        $website = (string)$this->getRequest()->getParam('website');
        $store = (string)$this->getRequest()->getParam('store');

        if (empty($listId)) {
            $this->messageManager->addErrorMessage(__('List ID is required.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'newsman/system_config/configureList',
                array_filter(['website' => $website, 'store' => $store])
            );
        }

        [$scope, $scopeId] = $this->resolveScope($website, $store);

        try {
            $this->configWriter->save(NewsmanConfig::XML_PATH_CREDENTIALS_LIST_ID, (string)$listId, $scope, $scopeId);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save list: %1', $e->getMessage()));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'newsman/system_config/configureList',
                array_filter(['website' => $website, 'store' => $store])
            );
        }

        // Ensure authenticate token exists.
        $storeModel = $this->resolveStore($website, $store);
        $authenticateToken = $this->newsmanConfig->getExportAuthenticateToken($storeModel);
        if (empty($authenticateToken)) {
            $authenticateToken = $this->generateRandomToken(32);
            $this->configWriter->save(
                NewsmanConfig::XML_PATH_EXPORT_AUTHENTICATE_TOKEN,
                $authenticateToken,
                $scope,
                $scopeId
            );
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }

        // Call saveListIntegrationSetup API.
        $integrationResult = $this->callSaveListIntegrationSetup(
            $listId,
            $storeModel,
            $authenticateToken
        );

        if ($integrationResult === false) {
            $this->messageManager->addErrorMessage(
                __('Could not save integration setup. The list was not changed.')
            );
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'newsman/system_config/configureList',
                array_filter(['website' => $website, 'store' => $store])
            );
        }

        $this->messageManager->addSuccessMessage(__('Newsman list saved successfully.'));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            ['section' => 'newsman'] + array_filter(['website' => $website, 'store' => $store])
        );
    }

    /**
     * Call the saveListIntegrationSetup API endpoint.
     *
     * @param int $listId
     * @param \Magento\Store\Api\Data\StoreInterface|null $storeModel
     * @param string $authenticateToken
     * @return bool
     */
    protected function callSaveListIntegrationSetup($listId, $storeModel, $authenticateToken)
    {
        try {
            $userId = $this->newsmanConfig->getUserId($storeModel);
            $apiKey = $this->newsmanConfig->getApiKey($storeModel);

            if (empty($userId) || empty($apiKey)) {
                $this->logger->warning('Cannot call saveListIntegrationSetup: missing credentials.');
                return false;
            }

            $store = $storeModel ?: $this->storeManager->getStore();
            $baseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
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

            return true;
        } catch (\Exception $e) {
            $this->logger->error('saveListIntegrationSetup failed: ' . $e->getMessage());
            return false;
        }
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

    /**
     * Resolve saved configuration scope (store/website/default).
     *
     * @param string $website
     * @param string $store
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function resolveScope($website, $store)
    {
        if (!empty($store)) {
            $storeModel = $this->storeManager->getStore($store);
            return [ScopeInterface::SCOPE_STORES, (int)$storeModel->getId()];
        }
        if (!empty($website)) {
            $websiteModel = $this->storeManager->getWebsite($website);
            return [ScopeInterface::SCOPE_WEBSITES, (int)$websiteModel->getId()];
        }
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0];
    }
}

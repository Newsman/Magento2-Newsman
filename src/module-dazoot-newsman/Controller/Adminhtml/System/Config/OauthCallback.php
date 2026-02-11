<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class Oauth callback after Newsman Login
 */
class OauthCallback extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;

    /**
     * @var TypeListInterface
     */
    protected TypeListInterface $cacheTypeList;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;

    /**
     * @param Context $context
     * @param Curl $curl
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Curl $curl,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $params = [];
        $website = (string)$this->getRequest()->getParam('website');
        $store = (string)$this->getRequest()->getParam('store');
        if (!empty($website)) {
            $params['website'] = $website;
        }
        if (!empty($store)) {
            $params['store'] = $store;
        }

        $error = (string)$this->getRequest()->getParam('error');
        if ($error) {
            $this->messageManager->addErrorMessage(__('Authorization error: %1', $error));
            return $this->redirectBackToConfig($params);
        }

        $code = (string)$this->getRequest()->getParam('code');
        if (empty($code)) {
            $this->messageManager->addErrorMessage(__('Missing authorization code.'));
            return $this->redirectBackToConfig($params);
        }

        try {
            $postData = [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'client_id'    => 'nzmplugin',
                'redirect_uri' => ''
            ];
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->post('https://newsman.app/admin/oauth/token', $postData);
            $status = $this->curl->getStatus();
            $body = $this->curl->getBody();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not complete request to Newsman: %1', $e->getMessage()));
            return $this->redirectBackToConfig($params);
        }

        if ($status < 200 || $status >= 300 || empty($body)) {
            $this->messageManager->addErrorMessage(__('Invalid response from Newsman (HTTP %1).', $status));
            return $this->redirectBackToConfig($params);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || empty($decoded['user_id']) || empty($decoded['access_token'])) {
            $this->messageManager->addErrorMessage(__('Unexpected response from Newsman.'));
            return $this->redirectBackToConfig($params);
        }

        // Save credentials according to scope
        [$scope, $scopeId] = $this->resolveScope($website, $store);
        try {
            $this->configWriter->save(
                NewsmanConfig::XML_PATH_CREDENTIALS_USER_ID,
                (string )$decoded['user_id'],
                $scope,
                $scopeId
            );
            $this->configWriter->save(
                NewsmanConfig::XML_PATH_CREDENTIALS_API_KEY,
                $this->encryptor->encrypt((string) $decoded['access_token']),
                $scope,
                $scopeId
            );

            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $len = strlen($chars);
            $authenticateToken = '';
            for ($i = 0; $i < 32; $i++) {
                $authenticateToken .= $chars[random_int(0, $len - 1)];
            }
            $this->configWriter->save(
                NewsmanConfig::XML_PATH_EXPORT_AUTHENTICATE_TOKEN,
                $authenticateToken,
                $scope,
                $scopeId
            );

            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Failed to save credentials: %1', $e->getMessage()));
            return $this->redirectBackToConfig($params);
        }

        $this->messageManager->addSuccessMessage(__('Connected to Newsman. User ID and API Key have been saved.'));
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('newsman/system_config/configureList', $params);
        return $resultRedirect;
    }

    /**
     * @param string $website
     * @param string $store
     * @return array
     */
    protected function resolveScope(string $website, string $store): array
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

    /**
     * @param array $params
     * @return Redirect
     */
    protected function redirectBackToConfig(array $params)
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'newsman'] + $params);
        return $resultRedirect;
    }
}

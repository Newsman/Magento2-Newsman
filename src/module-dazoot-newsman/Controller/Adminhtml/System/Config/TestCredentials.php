<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsman\Model\Service\Configuration\GetListAll;
use Dazoot\Newsman\Model\Service\Context\Configuration\UserContextFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Controller to test Newsman credentials by attempting to fetch lists
 */
class TestCredentials extends Action
{
    /**
     * Authorization level of a basic admin session.
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * Service used to validate credentials by fetching lists.
     *
     * @var GetListAll
     */
    protected $getListAll;

    /**
     * Factory for creating user context for API calls.
     *
     * @var UserContextFactory
     */
    protected $userContextFactory;

    /**
     * Newsman configuration model.
     *
     * @var NewsmanConfig
     */
    protected $config;

    /**
     * Store manager instance.
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * TestCredentials constructor.
     *
     * @param Context $context
     * @param GetListAll $getListAll
     * @param UserContextFactory $userContextFactory
     * @param NewsmanConfig $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        GetListAll $getListAll,
        UserContextFactory $userContextFactory,
        NewsmanConfig $config,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->getListAll = $getListAll;
        $this->userContextFactory = $userContextFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Attempt to fetch lists with stored credentials and display a notice.
     *
     * @return Redirect
     */
    public function execute()
    {
        $params = [];
        $storeParam = (string)$this->getRequest()->getParam('store');
        $websiteParam = (string)$this->getRequest()->getParam('website');
        if (!empty($websiteParam)) {
            $params['website'] = $websiteParam;
        }
        if (!empty($storeParam)) {
            $params['store'] = $storeParam;
        }

        // Resolve an effective store scope for reading values
        $effectiveStore = null;
        try {
            if (!empty($storeParam)) {
                $effectiveStore = $this->storeManager->getStore($storeParam)->getId();
            } elseif (!empty($websiteParam)) {
                $effectiveStore = $this->storeManager->getWebsite($websiteParam)->getDefaultStore()->getId();
            }
        } catch (\Exception $e) {
            // Keep default scope when resolving scope fails
            $effectiveStore = null;
        }

        $userId = (int)$this->config->getUserId($effectiveStore);
        $apiKey = (string)$this->config->getApiKey($effectiveStore);

        $success = false;
        if ($userId && $apiKey) {
            try {
                $this->getListAll->execute(
                    $this->userContextFactory->create()
                        ->setUserId($userId)
                        ->setApiKey($apiKey)
                );
                $success = true;
            } catch (LocalizedException $e) {
                $success = false;
            } catch (\Throwable $t) {
                $success = false;
            }
        }

        if ($success) {
            $this->messageManager->addSuccessMessage(__('Credentials are valid'));
        } else {
            $this->messageManager->addErrorMessage(__('Credentials are invalid or there is temporarily API error!'));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'newsman'] + $params);
        return $resultRedirect;
    }
}

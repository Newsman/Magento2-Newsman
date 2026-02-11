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
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * Config writer.
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
     * SaveConfigureList constructor.
     *
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
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
            return $resultRedirect->setPath('newsman/system_config/configureList', array_filter(['website' => $website, 'store' => $store]));
        }

        [$scope, $scopeId] = $this->resolveScope($website, $store);

        try {
            $this->configWriter->save(NewsmanConfig::XML_PATH_CREDENTIALS_LIST_ID, (string)$listId, $scope, $scopeId);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $this->messageManager->addSuccessMessage(__('Newsman list saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save list: %1', $e->getMessage()));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('newsman/system_config/configureList', array_filter(['website' => $website, 'store' => $store]));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'newsman'] + array_filter(['website' => $website, 'store' => $store]));
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

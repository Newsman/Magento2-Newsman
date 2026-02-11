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
use Dazoot\Newsman\Model\Service\Configuration\GetSegments;
use Dazoot\Newsman\Model\Service\Context\Configuration\ListContextFactory;
use Dazoot\Newsman\Model\Service\Context\Configuration\UserContextFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as ResultPage;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class configure list view page
 */
class ConfigureList extends Action
{
    /**
     * Authorization level of a basic admin session.
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var GetListAll
     */
    protected GetListAll $getListAll;

    /**
     * @var GetSegments
     */
    protected GetSegments $getSegments;

    /**
     * @var UserContextFactory
     */
    protected UserContextFactory $userContextFactory;

    /**
     * @var ListContextFactory
     */
    protected ListContextFactory $listContextFactory;

    /**
     * @var NewsmanConfig
     */
    protected NewsmanConfig $config;

    /**
     * @var TypeListInterface
     */
    protected TypeListInterface $cacheTypeList;

    /**
     * @var StripTags
     */
    protected StripTags $tagFilter;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param GetListAll $getListAll
     * @param GetSegments $getSegments
     * @param UserContextFactory $userContextFactory
     * @param ListContextFactory $listContextFactory
     * @param NewsmanConfig $config
     * @param TypeListInterface $cacheTypeList
     * @param StripTags $tagFilter
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        GetListAll $getListAll,
        GetSegments $getSegments,
        UserContextFactory $userContextFactory,
        ListContextFactory $listContextFactory,
        NewsmanConfig $config,
        TypeListInterface $cacheTypeList,
        StripTags $tagFilter
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->getListAll = $getListAll;
        $this->getSegments = $getSegments;
        $this->userContextFactory = $userContextFactory;
        $this->listContextFactory = $listContextFactory;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->tagFilter = $tagFilter;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $website = (string)$this->getRequest()->getParam('website');
        $store = (string)$this->getRequest()->getParam('store');

        // Resolve credentials by scope where they were saved
        [$scope, $scopeId] = $this->resolveScope($website, $store);
        [$userId, $apiKey] = $this->getCredentials($scope, $scopeId, $website, $store);

        if (empty($userId) || empty($apiKey)) {
            $this->messageManager->addErrorMessage(__('Missing Newsman credentials.'));
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath(
                'adminhtml/system_config/edit',
                ['section' => 'newsman'] + array_filter(['website' => $website, 'store' => $store])
            );
        }

        try {
            $listsData = $this->getListAll->execute(
                $this->userContextFactory->create()
                    ->setUserId($userId)
                    ->setApiKey($apiKey)
            );
            $segmentsData = [$userId => []];
            if (!empty($listsData)) {
                foreach ($listsData as $listItem) {
                    $segmentsData[$userId][$listItem['list_id']] = $this->getSegments->execute(
                        $this->listContextFactory->create()
                            ->setUserId($userId)
                            ->setApiKey($apiKey)
                            ->setListId($listItem['list_id'])
                    );
                }
            }

            $this->config->saveStoredLists($userId, $listsData);
            $this->config->saveStoredSegments($userId, $segmentsData);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($this->tagFilter->filter($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong. Please try again.'));
        }

        /** @var ResultPage $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Backend::stores_settings');
        $resultPage->getConfig()->getTitle()->prepend(__('Newsman'));
        $resultPage->getConfig()->getTitle()->append(__('Select Email List'));

        return $resultPage;
    }

    /**
     * @param string $website
     * @param string $store
     * @return array
     * @throws LocalizedException
     */
    protected function resolveScope($website, $store)
    {
        if (!empty($store)) {
            $storeModel = $this->storeManager->getStore($store);
            return [ScopeInterface::SCOPE_STORES, $storeModel->getId()];
        }
        if (!empty($website)) {
            $websiteModel = $this->storeManager->getWebsite($website);
            return [ScopeInterface::SCOPE_WEBSITES, $websiteModel->getId()];
        }
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null];
    }

    /**
     * @param string $scope
     * @param int|null $scopeId
     * @param string $website
     * @param string $store
     * @return array
     */
    protected function getCredentials($scope, $scopeId, $website, $store): array
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        if ($scope === ScopeInterface::SCOPE_STORES) {
            $scopeType = ScopeInterface::SCOPE_STORES;
        }
        if ($scope === ScopeInterface::SCOPE_WEBSITES) {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
        }
        $userId = (int) $this->scopeConfig->getValue(
            NewsmanConfig::XML_PATH_CREDENTIALS_USER_ID,
            $scopeType,
            $scopeId
        );
        $apiKey = (string) $this->scopeConfig->getValue(
            NewsmanConfig::XML_PATH_CREDENTIALS_API_KEY,
            $scopeType,
            $scopeId
        );

        return [$userId, $apiKey];
    }
}

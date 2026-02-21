<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Newsletter\Bulk\Export\Scheduler;
use Magento\Backend\App\Action\Context;
use Magento\Config\Controller\Adminhtml\System\AbstractConfig;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Store\Model\StoreManagerInterface;
use Dazoot\Newsman\Model\Config\Source\Lists as ListsSource;

/**
 * @deprecated
 */
class ExportSubscribers extends AbstractConfig
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ListsSource
     */
    protected $listsSource;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @param Context $context
     * @param Structure $configStructure
     * @param ConfigSectionChecker $sectionChecker
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param ListsSource $listsSource
     * @param Scheduler $scheduler
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        StoreManagerInterface $storeManager,
        Config $config,
        ListsSource $listsSource,
        Scheduler $scheduler
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->listsSource = $listsSource;
        $this->scheduler = $scheduler;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Export newsletter subscribers to Newsman
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $storeId = $this->getRequest()->getParam('store');

        $params = ['section' => 'newsman'];
        if (!empty($storeId)) {
            $params['store'] = $storeId;
        } elseif (!empty($websiteId)) {
            $params['website'] = $websiteId;
        }

        if (!empty($storeId)) {
            $listId = $this->config->getListId($storeId);
        } elseif (!empty($websiteId)) {
            $listId = $this->config->getListId(null, $websiteId);
        } else {
            $listId = $this->config->getListId(null, null, true);
        }
        $listName = $this->listsSource->getLabelByValue($listId);

        if (empty($this->config->getStoreIdsByListId($listId))) {
            $this->messageManager->addErrorMessage(
                __('No store found for list ID "%1" with API configured or active.', $listName)
            );
            return $this->_redirect('adminhtml/system_config/edit', $params);
        }

        $this->scheduler->execute($listId);

        $this->messageManager->addSuccessMessage(
            __(
                '%1 newsletter subscribers from list "%2" are being exported to Newsman.',
                $this->scheduler->getCountSubscribers(),
                $listName
            )
        );
        if ($this->scheduler->getCountSubscribers()) {
            $this->messageManager->addComplexSuccessMessage(
                'addNewsmanBulkActionsLinkMessage',
                [
                    'bulk_actions_log' => $this->_backendUrl->getUrl('bulk/index/index')
                ]
            );
        }

        return $this->_redirect('adminhtml/system_config/edit', $params);
    }
}

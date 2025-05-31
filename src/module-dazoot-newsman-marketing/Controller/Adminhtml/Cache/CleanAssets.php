<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Controller\Adminhtml\Cache;

use Exception;
use Magento\Backend\Controller\Adminhtml\Cache;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Dazoot\Newsmanmarketing\Model\Asset\Cache as AssetCache;

/**
 * Clean Newsman Remarketing assets from media folder
 */
class CleanAssets extends Cache implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Backend::flush_js_css';

    /**
     * Clean JS/css files cache
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $this->_objectManager->get(AssetCache::class)->clean();
            $this->_eventManager->dispatch('dazoot_newsmanmarketing_clean_media_assets_after');
            $this->messageManager->addSuccessMessage(__('Tracking JavaScript cache has been cleaned.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager
                ->addExceptionMessage($e, __('An error occurred while clearing the tracking JavaScript cache.'));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('adminhtml/*');
    }
}

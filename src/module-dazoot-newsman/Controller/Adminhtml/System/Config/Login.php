<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

class Login extends Action
{
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * Start OAuth flow by redirecting to Newsman authorize endpoint.
     *
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
        $callbackUrl = $this->getUrl('newsman/system_config/oauthCallback', $params + ['_secure' => true]);
        $authUrl = 'https://newsman.app/admin/oauth/authorize?response_type=code&client_id=nzmplugin' .
            '&nzmplugin=Magento&scope=api&redirect_uri=' . urlencode($callbackUrl);

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($authUrl);
        return $resultRedirect;
    }
}

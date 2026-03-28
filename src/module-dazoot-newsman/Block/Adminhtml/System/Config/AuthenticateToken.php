<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class AuthenticateToken extends Field
{
    /**
     * @var NewsmanConfig
     */
    protected $newsmanConfig;

    /**
     * @param Context $context
     * @param NewsmanConfig $newsmanConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        NewsmanConfig $newsmanConfig,
        array $data = []
    ) {
        $this->newsmanConfig = $newsmanConfig;
        parent::__construct($context, $data);
    }

    /**
     * Render element HTML.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $store = null;
        $storeCode = $this->getRequest()->getParam('store');
        $websiteCode = $this->getRequest()->getParam('website');
        if (!empty($storeCode)) {
            $store = $this->_storeManager->getStore($storeCode);
        } elseif (!empty($websiteCode)) {
            $website = $this->_storeManager->getWebsite($websiteCode);
            $store = $website->getDefaultStore();
        }

        $token = $this->newsmanConfig->getExportAuthenticateToken($store);
        if (empty($token)) {
            return '<span style="color:#999;">' . __('Not set') . '</span>';
        }

        $masked = $this->maskToken($token);
        return '<span style="font-family:monospace;font-size:14px;letter-spacing:0.5px;">'
            . $this->escapeHtml($masked) . '</span>';
    }

    /**
     * Mask a token showing first 3 and last 4 characters.
     *
     * @param string $token
     * @return string
     */
    protected function maskToken($token)
    {
        $len = strlen($token);
        if ($len <= 7) {
            return str_repeat('*', $len);
        }
        return substr($token, 0, 3) . '****' . substr($token, -4);
    }
}

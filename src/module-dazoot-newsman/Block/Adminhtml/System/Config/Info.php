<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Magento\Backend\Block\AbstractBlock;
use Magento\Backend\Block\Context;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Info extends AbstractBlock implements
    RendererInterface
{
    /**
     * Backend request instance.
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var ComposerInformation
     */
    protected $composerInformation;

    /**
     * @param Context $context
     * @param ComposerInformation $composerInformation
     * @param array $data
     */
    public function __construct(
        Context $context,
        ComposerInformation $composerInformation,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_request = $context->getRequest();
        $this->composerInformation = $composerInformation;
    }

    /**
     * Get extension version from composer
     *
     * @return string
     */
    protected function getExtensionVersion()
    {
        $packages = $this->composerInformation->getInstalledMagentoPackages();
        if (isset($packages[NewsmanConfig::COMPOSER_PACKAGE_NAME])) {
            return $packages[NewsmanConfig::COMPOSER_PACKAGE_NAME]['version'];
        }
        return 'unknown';
    }

    /**
     * Render form element as HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $logoUrl = $this->getViewFileUrl('Dazoot_Newsman::images/logo.png');
        $extensionVersion = $this->getExtensionVersion();
        $versionLabel = __('Newsman Extension Version');
        $contactText = __('If you need support or have any questions, please contact us at');
        $buttonLabel = __('Configure with Newsman Login');

        $params = [];
        if ($website = (string)$this->_request->getParam('website')) {
            $params['website'] = $website;
        }
        if ($store = (string)$this->_request->getParam('store')) {
            $params['store'] = $store;
        }
        $loginUrl = $this->getUrl('newsman/system_config/login', $params);

        $html = <<<HTML
<div style="width: 100%; padding: 15px; display: none;" id="infoPanel">
<span style="display: inline-block; color: #49e249; padding: 5px;" id="msgType"></span>
<button id="closeInfoPanel" style="display: inline-block; background: #a04747; color: #fff;" type="button">X</button>
</div>
<div style="border:1px solid #e3e3e3; min-height:100px; display: block; padding:15px;
    background-color: #f8f8f8; border-radius: 5px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <div style="margin-right: 15px;">
            <a href="https://www.newsman.com/" target="_blank"><img src="$logoUrl" style="display: block; height: 30px;" /></a>
        </div>
        <div>
            <p style="margin: 0; color: #888; font-size: 12px;">{$versionLabel}: {$extensionVersion}</p>
        </div>
    </div>
    <div style="margin-bottom: 15px;">
        <p>Like and follow us on
            <a href="http://www.facebook.com/NewsmanApp" target="_blank" style="color: #a04747;">Facebook</a>,
            <a href="https://www.linkedin.com/company/newsman-app"
                target="_blank" style="color: #a04747;">LinkedIn</a> and
            <a href="http://twitter.com/NewsmanApp" target="_blank" style="color: #a04747;">Twitter</a>.
        </p>
        <p>
            {$contactText} <a href="mailto:info@newsman.ro"
                style="color: #a04747; font-weight: bold;">info@newsman.ro</a>.
        </p>
    </div>
    <div style="margin-top: 20px;">
        <a href="{$loginUrl}" class="action-default scalable primary">
            <span>{$buttonLabel}</span>
        </a>
    </div>
</div>
HTML;
        return $html;
    }
}

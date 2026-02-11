<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Block\Adminhtml\Cache\Additional;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Framework\App\ObjectManager;

/**
 * Clean assets (JS files) cache block
 */
class Assets extends Template
{
    /**
     * Retrieve URL for cleaning assets cache.
     *
     * @return string
     */
    public function getCleanAssetsUrl()
    {
        return $this->getUrl('newsmanmarketing/cache/cleanAssets');
    }

    /**
     * @inheritdoc
     */
    public function _toHtml()
    {
        if (!ObjectManager::getInstance()->get(Config::class)->isAnyActive()) {
            return '';
        }
        return parent::_toHtml();
    }
}

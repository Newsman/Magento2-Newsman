<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Block\Adminhtml\Cache;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Permissions implements ArgumentInterface
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Permissions constructor.
     *
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Check if user has access to flush assets.
     *
     * @return bool
     */
    public function hasAccessToFlushAssets()
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_js_css');
    }
}

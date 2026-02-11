<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Configuration view model
 */
class Configuration implements ArgumentInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Check if Newsman Marketing is active for the current store.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->config->isActive();
    }

    /**
     * Check if Newsman Marketing is active for any store.
     *
     * @return bool
     */
    public function isAnyActive()
    {
        return $this->config->isAnyActive();
    }
}

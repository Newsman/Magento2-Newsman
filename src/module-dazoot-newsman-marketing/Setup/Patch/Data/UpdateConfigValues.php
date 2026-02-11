<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsmanmarketing\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Update config values in core_config_data
 */
class UpdateConfigValues implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $configData = [
            'newsmanmarketing/tracking/script_url' => 'https://t.newsmanapp.com/jt/t.js',
            'newsmanmarketing/http/resources_url' => 'https://t.newsmanapp.com/',
            'newsmanmarketing/http/tracking_url' => 'https://rtrack.newsmanapp.com/',
            'newsmanmarketing/http/required_file_patterns' =>
                "jt/t.js\njt/nzm_custom_{{api_key}}.js\njt/ecommerce.js\njt/modal_{{api_key}}.js",
        ];

        $tableName = $this->moduleDataSetup->getTable('core_config_data');
        foreach ($configData as $path => $value) {
            $this->moduleDataSetup->getConnection()->update(
                $tableName,
                ['value' => $value],
                ['path = ?' => $path]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

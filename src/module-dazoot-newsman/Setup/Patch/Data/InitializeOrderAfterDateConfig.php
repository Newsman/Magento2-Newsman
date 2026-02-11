<?php

namespace Dazoot\Newsman\Setup\Patch\Data;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Setup\ConfigWriter;
use Dazoot\Newsman\Setup\ConfigWriterFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class InitializeOrderAfterDateConfig patch data
 */
class InitializeOrderAfterDateConfig implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var ConfigWriterFactory
     */
    protected $configWriterFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ConfigWriterFactory $configWriterFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConfigWriterFactory $configWriterFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriterFactory = $configWriterFactory;
    }

    /**
     * Apply data patch.
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var ConfigWriter $configWriter */
        $configWriter = $this->configWriterFactory->create();
        $date = new \DateTime();
        $date->modify('-2 years');
        $configWriter->saveConfigValue(Config::XML_PATH_ORDER_AFTER_DATE, $date->format('Y-m-d'));

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

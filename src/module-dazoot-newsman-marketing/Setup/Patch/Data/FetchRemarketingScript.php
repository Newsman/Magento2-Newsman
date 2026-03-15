<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsmanmarketing\Setup\Patch\Data;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Service\Configuration\GetSettings;
use Dazoot\Newsmanmarketing\Model\Service\Context\GetSettingsContext;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Fetch remarketing script from Newsman API via remarketing.getSettings
 * and store it in newsmanmarketing/general/script_js.
 */
class FetchRemarketingScript implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var NewsmanConfig
     */
    private $newsmanConfig;

    /**
     * @var GetSettings
     */
    private $getSettingsService;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param NewsmanConfig $newsmanConfig
     * @param GetSettings $getSettingsService
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        NewsmanConfig $newsmanConfig,
        GetSettings $getSettingsService,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->newsmanConfig = $newsmanConfig;
        $this->getSettingsService = $getSettingsService;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
    }

    /**
     * Fetch the remarketing JavaScript snippet from the Newsman API once per
     * unique list ID and save it to every store that shares that list.
     *
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach ($this->newsmanConfig->getAllListIds() as $listId) {
            $storeIds = $this->newsmanConfig->getStoreIdsByListId($listId);
            if (empty($storeIds)) {
                continue;
            }

            $firstStoreId = reset($storeIds);
            $userId = $this->newsmanConfig->getUserId($firstStoreId);
            $apiKey = $this->newsmanConfig->getApiKey($firstStoreId);

            if (empty($userId) || empty($apiKey)) {
                continue;
            }

            try {
                $context = new GetSettingsContext();
                $context->setUserId($userId)
                    ->setApiKey($apiKey)
                    ->setListId($listId);

                $settings = $this->getSettingsService->execute($context);

                if (!empty($settings) && is_array($settings) && !empty($settings['javascript'])) {
                    foreach ($storeIds as $storeId) {
                        $this->configWriter->save(
                            Config::XML_PATH_SCRIPT_JS,
                            $settings['javascript'],
                            ScopeInterface::SCOPE_STORES,
                            $storeId
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('FetchRemarketingScript patch: ' . $e->getMessage());
            }
        }

        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            UpdateConfigValues::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

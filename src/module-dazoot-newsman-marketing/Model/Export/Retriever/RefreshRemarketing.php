<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsman\Model\Export\Retriever\AbstractRetriever;
use Dazoot\Newsman\Model\Export\Retriever\RetrieverInterface;
use Dazoot\Newsman\Model\Export\Retriever\V1\ApiV1Exception;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Service\Configuration\GetSettings;
use Dazoot\Newsmanmarketing\Model\Service\Context\GetSettingsContext;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Handle inbound refresh.remarketing API v1 request.
 *
 * Fetches the remarketing script from the Newsman API via
 * remarketing.getSettings and stores it in core_config_data.
 */
class RefreshRemarketing extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var NewsmanConfig
     */
    protected $newsmanConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var GetSettings
     */
    protected $getSettingsService;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param NewsmanConfig $newsmanConfig
     * @param Config $config
     * @param GetSettings $getSettingsService
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        NewsmanConfig $newsmanConfig,
        Config $config,
        GetSettings $getSettingsService,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->newsmanConfig = $newsmanConfig;
        $this->config = $config;
        $this->getSettingsService = $getSettingsService;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Process refresh remarketing.
     *
     * @param array $data
     * @param array $storeIds
     * @return array
     * @throws ApiV1Exception On validation or execution errors.
     */
    public function process($data = [], $storeIds = [])
    {
        $refresh = isset($data['refresh']) ? (int) $data['refresh'] : 0;
        if (1 !== $refresh) {
            throw new ApiV1Exception(9001, 'Missing or invalid "refresh" parameter: must be 1', 400);
        }

        $firstStoreId = !empty($storeIds) ? reset($storeIds) : null;
        $firstStore = $firstStoreId !== null ? $this->storeManager->getStore($firstStoreId) : null;

        $userId = $this->newsmanConfig->getUserId($firstStore);
        $apiKey = $this->newsmanConfig->getApiKey($firstStore);
        $listId = $this->newsmanConfig->getListId($firstStore);

        if (empty($userId) || empty($apiKey) || empty($listId)) {
            throw new ApiV1Exception(9002, 'Plugin is not configured: missing user ID, API key, or list ID', 400);
        }

        try {
            $context = new GetSettingsContext();
            $context->setUserId($userId)
                ->setApiKey($apiKey)
                ->setListId($listId);

            $settings = $this->getSettingsService->execute($context);
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new ApiV1Exception(9003, 'Failed to retrieve remarketing settings from Newsman API', 500);
        }

        if (empty($settings) || !is_array($settings) || empty($settings['javascript'])) {
            throw new ApiV1Exception(9004, 'Newsman API returned empty remarketing script', 500);
        }

        $oldRemarketingJs = $this->config->getScriptJs($firstStore);
        $newRemarketingJs = $settings['javascript'];

        foreach ($storeIds as $storeId) {
            $this->configWriter->save(
                Config::XML_PATH_SCRIPT_JS,
                $newRemarketingJs,
                'stores',
                (int) $storeId
            );
        }
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

        $this->logger->info('refresh.remarketing: updated script_js for stores ' . implode(',', $storeIds));

        return [
            'status' => 1,
            'old_remarketing_js' => !empty($oldRemarketingJs) ? $oldRemarketingJs : '',
            'new_remarketing_js' => $newRemarketingJs,
        ];
    }
}

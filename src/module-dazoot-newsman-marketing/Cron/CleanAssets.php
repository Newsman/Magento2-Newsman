<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Cron;

use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Asset\Cache as AssetCache;
use Dazoot\Newsman\Logger\Logger;

/**
 * Clean Newsman Remarketing cron clean assets
 */
class CleanAssets
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AssetCache
     */
    protected $assetCache;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param AssetCache $assetCache
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        AssetCache $assetCache,
        Logger $logger
    ) {
        $this->config = $config;
        $this->assetCache = $assetCache;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->config->isAnyActive()) {
            return;
        }

        try {
            $this->assetCache->clean();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

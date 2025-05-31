<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Cron;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Newsletter\Bulk\Export\Scheduler;
use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsman\Logger\Logger;

/**
 * Export newsletter subscribers to newsman
 */
class ExportSubscribers
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param Scheduler $scheduler
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Scheduler $scheduler,
        Logger $logger
    ) {
        $this->config = $config;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!$this->config->isEnabledInAny()) {
            return;
        }

        $listIds = $this->config->getAllListIds();
        foreach ($listIds as $listId) {
            $this->logger->info(__('Exporting in cron newsletter subscribers to Newsman for list ID %1.', $listId));
            $this->scheduler->execute($listId);
            $this->logger->info(__('Exported in cron newsletter subscribers to Newsman for list ID %1.', $listId));
        }
    }
}

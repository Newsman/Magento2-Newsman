<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Cron;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

/**
 * Delete old log files
 */
class LogRotate
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param Filesystem $filesystem
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Filesystem $filesystem,
        Logger $logger
    ) {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    public function execute()
    {
        $read = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $paths = $read->search('newsman_*.log', 'log/newsman/');
        if (empty($paths)) {
            return;
        }

        $cdate = new \DateTime();
        $cdate->setTime(0, 0);
        $count = 0;
        foreach ($paths as $filepath) {
            if (preg_match('/log\/newsman\/newsman_(\d{4}-\d{2}-\d{2})\.log$/', $filepath, $matches)) {
                try {
                    if (!$read->isFile($filepath)) {
                        continue;
                    }
                    $fdate = new \DateTime($matches[1]);
                    $fdate->setTime(0, 0);

                    $interval = $cdate->diff($fdate);
                    if ($interval->days > $this->config->getLogClean()) {
                        $write->delete($filepath);
                        $count++;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if ($count > 0) {
            $this->logger->info(__('Deleted %1 log files', $count));
        }
    }
}

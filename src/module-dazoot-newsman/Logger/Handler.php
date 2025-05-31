<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Newsman Handler
 */
class Handler extends Base
{
    /**
     * @var string
     */
    public $fileName = '/var/log/newsman/newsman.log';

    /**
     * @var int
     */
    public $loggerType = \Monolog\Logger::DEBUG;

    /**
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     */
    public function __construct(
        DriverInterface $filesystem,
        ?string $filePath = null,
        ?string $fileName = null
    ) {
        if (empty($fileName)) {
            $this->fileName = '/var/log/newsman/newsman_' . date('Y-m-d') . '.log';
        }

        parent::__construct($filesystem, $filePath, $fileName);
    }
}

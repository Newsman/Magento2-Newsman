<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Logger;

use DateTimeZone;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Config\Source\Logging;
use Dazoot\Newsman\Model\ConfigFactory;

//use Monolog\DateTimeImmutable;

/**
 * Class Newsman Logger
 */
class Logger extends \Monolog\Logger
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        Config $config,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);

        $this->config = $config;
    }

    /**
     * @inheridoc
     */
    public function log($level, $message, array $context = []): void
    {
        if ($this->config->getLogMode() <= Logging::TYPE_NONE) {
            return;
        }

        parent::log($level, $message, $context);
    }

    /**
     * @inheridoc
     */
    public function debug($message, array $context = []): void
    {
        if ($this->config->getLogMode() > self::DEBUG) {
            return;
        }
        parent::debug($message, $context);
    }

    /**
     * @inheridoc
     */
    public function info($message, array $context = []): void
    {
        if ($this->config->getLogMode() > self::INFO) {
            return;
        }
        parent::info($message, $context);
    }

    /**
     * @inheridoc
     */
    public function notice($message, array $context = []): void
    {
        if ($this->config->getLogMode() > self::NOTICE) {
            return;
        }
        parent::notice($message, $context);
    }

    /**
     * @inheridoc
     */
    public function warning($message, array $context = []): void
    {
        if ($this->config->getLogMode() > self::WARNING) {
            return;
        }
        parent::warning($message, $context);
    }

    /**
     * @inheridoc
     */
    public function error($message, array $context = []): void
    {
        if ($this->config->getLogMode() <= Logging::TYPE_NONE) {
            return;
        }
        parent::error($message, $context);
    }

    /**
     * @inheridoc
     */
    public function critical($message, array $context = []): void
    {
        if ($this->config->getLogMode() <= Logging::TYPE_NONE) {
            return;
        }
        parent::critical($message, $context);
    }

    /**
     * @inheridoc
     */
    public function alert($message, array $context = []): void
    {
        if ($this->config->getLogMode() <= Logging::TYPE_NONE) {
            return;
        }
        parent::alert($message, $context);
    }

    /**
     * @inheridoc
     */
    public function emergency($message, array $context = []): void
    {
        if ($this->config->getLogMode() <= Logging::TYPE_NONE) {
            return;
        }
        parent::emergency($message, $context);
    }
}

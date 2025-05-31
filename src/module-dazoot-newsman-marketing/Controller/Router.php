<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsmanmarketing\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

/**
 * Match nz-mark/res or nz-mark/req and capture everything after it
 */
class Router implements RouterInterface
{
    /**
     * Resources identifier
     */
    public const RESOURCES_IDENTIFIER = 'res';

    /**
     * Tracking identifier
     */
    public const TRACKING_IDENTIFIER = 'req';

    /**
     * Router front name
     */
    public const FRONT_NAME = 'nz-mark';

    /**
     * Resource controller name
     */
    public const CONTROLLER = 'nzfw';

    /**
     * Resource action name
     */
    public const ACTION = 'index';

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ActionList
     */
    private $actionList;

    /**
     * @var ConfigInterface
     */
    private $routeConfig;

    /**
     * @param ActionFactory $actionFactory
     * @param ActionList $actionList
     * @param ConfigInterface $routeConfig
     */
    public function __construct(
        ActionFactory $actionFactory,
        ActionList $actionList,
        ConfigInterface $routeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionList = $actionList;
        $this->routeConfig = $routeConfig;
    }

    /**
     * Match domain-association route
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request)
    {
        $path = ltrim($request->getPathInfo(), '/');

        $isResources = false;
        $resourcesPart = self::FRONT_NAME . '/' . self::RESOURCES_IDENTIFIER;
        if (stripos($path, $resourcesPart) === 0) {
            $isResources = true;
        }

        $isTracking = false;
        $trackingPart = self::FRONT_NAME . '/' . self::TRACKING_IDENTIFIER;
        if (stripos($path, $trackingPart) === 0) {
            $isTracking = true;
        }

        if (!$isResources && !$isTracking) {
            return null;
        }

        $nzmPath = '';
        if ($isResources) {
            $nzmPath = substr($path, strlen($resourcesPart));
        } elseif ($isTracking) {
            $nzmPath = substr($path, strlen($trackingPart));
        }
        if (!empty($nzmPath) && $nzmPath !== '/') {
            $nzmPath = ltrim($nzmPath, '/');
        } else {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName(self::FRONT_NAME);
        if (empty($modules)) {
            return null;
        }

        $actionClassName = $this->actionList->get($modules[0], null, self::CONTROLLER, self::ACTION);
        /** @var \Dazoot\Newsmanmarketing\Controller\Nzfw\Index $action */
        $action = $this->actionFactory->create($actionClassName);
        $action->setNzmPath($nzmPath);
        if ($isResources) {
            $action->setNzmType(self::RESOURCES_IDENTIFIER);
        } elseif ($isTracking) {
            $action->setNzmType(self::TRACKING_IDENTIFIER);
        }
        return $action;
    }
}

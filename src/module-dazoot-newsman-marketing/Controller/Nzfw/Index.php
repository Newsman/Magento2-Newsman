<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Controller\Nzfw;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsmanmarketing\Model\Asset\Cache as AssetCache;
use Dazoot\Newsmanmarketing\Controller\Router;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Http\Tunnel;
use Laminas\Http\Request;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\CsrfAwareActionInterface;

/**
 * Class Newsman Marketing resource index action
 * @note HttpHeadActionInterface is deprecated. HEAD and GET requests use HttpGetActionInterface
 */
class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Tunnel
     */
    protected $httpTunnel;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var AssetCache
     */
    protected $assetCache;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $nzmPath = '';

    /**
     * @var string
     */
    protected $nzmType = '';

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Tunnel $httpTunnel
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param AssetCache $assetCache
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        Tunnel $httpTunnel,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        AssetCache $assetCache,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->httpTunnel = $httpTunnel;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->assetCache = $assetCache;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Build the marketing resource response.
     *
     * @return ResultInterface|void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function execute()
    {
        if (!$this->config->isActive()) {
            $this->sendNotFound();
            return;
        }

        $path = $this->getNzmPath();
        if (!(!empty($path) && $this->validatePath($path) && $this->validateRequestMethod($path))) {
            $this->sendNotFound();
            return;
        }

        if ($this->getNzmType() == Router::RESOURCES_IDENTIFIER) {
            $tunnelUrl = $this->config->getResourcesUrl();
        } elseif ($this->getNzmType() == Router::TRACKING_IDENTIFIER) {
            $tunnelUrl = $this->config->getTrackingUrl();
        } else {
            $this->sendNotFound();
            return;
        }

        try {
            if ($this->getNzmType() == Router::RESOURCES_IDENTIFIER && $this->assetCache->isValidPath($path)) {
                $cachedFile = $this->assetCache->load($path);
                if ($cachedFile !== false && $cachedFile->getContent() && $cachedFile->getHeaders()) {
                    foreach ($cachedFile->getHeaders() as $name => $value) {
                        $this->getResponse()->setHeader($name, $value);
                    }
                    $this->getResponse()->setBody($cachedFile->getContent());
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $requestHeaders = $this->getHeaders();
        $requestMethod = $this->getRequest()->getMethod();
        if ($requestMethod != Request::METHOD_HEAD) {
            if ($this->getNzmType() == Router::RESOURCES_IDENTIFIER && $this->assetCache->isValidPath($path)) {
                // Force 200 response to save later in cache versus getting 304 from Newsman server and empty body.
                $requestHeaders['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
                $requestHeaders['Pragma'] = 'no-cache';
            }
        }

        $response = $this->httpTunnel->request(
            $tunnelUrl,
            $requestMethod,
            $path,
            $this->getQueryParams(),
            $this->getPostParams(),
            $requestHeaders
        );

        if ($response === false) {
            $this->sendNotFound();
            return;
        }

        if ($response['status'] < 400) {
            $cacheHeaders = [];
            foreach ($response['headers'] as $name => $value) {
                if ($name != 'Set-Cookie') {
                    $cacheHeaders[$name] = $value;
                    $this->getResponse()->setHttpResponseCode($response['status'])
                        ->setHeader($name, $value);
                }
            }

            $this->addReceivedCookies($response['cookies'] ? $response['cookies'] : []);

            if ($requestMethod != Request::METHOD_HEAD) {
                if ($this->getNzmType() == Router::RESOURCES_IDENTIFIER && $this->assetCache->isValidPath($path)) {
                    $this->assetCache->save($path, $response['body'], $cacheHeaders);
                } elseif ($this->getNzmType() == Router::TRACKING_IDENTIFIER &&
                    ($requestMethod == Request::METHOD_GET || $requestMethod == Request::METHOD_HEAD)) {
                    // No full page cache or Varnish cache page
                    $this->getResponse()->setNoCacheHeaders();
                }
                $this->getResponse()->setBody($response['body']);
            }
        } else {
            $this->sendNotFound();
        }
    }

    /**
     * Retrieve query parameters from the current request.
     *
     * @return array
     */
    public function getQueryParams()
    {
        $getParams = $this->getRequest()->getQuery();
        if (!empty($getParams)) {
            $getParams = $getParams->toArray();
        } else {
            $getParams = [];
        }
        return $getParams;
    }

    /**
     * Retrieve POST parameters from the current request.
     *
     * @return array
     */
    public function getPostParams()
    {
        $postParams = [];
        if ($this->getRequest()->getMethod() === Request::METHOD_POST) {
            $postParams = $this->getRequest()->getPost();
            if (!empty($postParams) && $postParams instanceof \Laminas\Stdlib\Parameters && $postParams->count() > 0) {
                $postParams = $postParams->toArray();
            } elseif (empty($postParams) || !is_array($postParams)) {
                $content = $this->getRequest()->getContent();
                if (!empty($content)) {
                    $postParams = $content;
                } else {
                    $postParams = [];
                }
            }
        }

        return $postParams;
    }

    /**
     * Retrieve all request headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = $this->getRequest()->getHeaders();
        if (!empty($headers)) {
            $headers = $headers->toArray();
        } else {
            $headers = [];
        }
        return $headers;
    }

    /**
     * Check if the provided path is relative and valid.
     *
     * @param string $path
     * @return bool
     */
    public function validatePath($path)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlInfo = parse_url($path);
        if (isset($urlInfo['host']) && !empty($urlInfo['host'])) {
            $this->logger->error(__('Invalid absolute URL provided in path %1', $path));
            return false;
        }
        return true;
    }

    /**
     * Check if the request method is allowed for the given path.
     *
     * @param string $path
     * @return bool
     */
    public function validateRequestMethod($path)
    {
        $allowedMethods = $this->getAllowedMethods();
        if (!in_array($this->getRequest()->getMethod(), $allowedMethods)) {
            $this->logger->error(__(
                'Invalid request method %1 on requested path %2',
                $this->getRequest()->getMethod(),
                $path
            ));
            return false;
        }
        return true;
    }

    /**
     * Retrieve a list of supported HTTP methods for the tunnel.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_HEAD
        ];
    }

    /**
     * Batch process and add cookies from Newsman response.
     *
     * @param array $cookies
     * @return void
     */
    public function addReceivedCookies($cookies)
    {
        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $name => $cookieData) {
            try {
                $this->addReceivedCookie($name, $cookieData);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }
    }

    /**
     * Add a received cookie to the response.
     *
     * @param string $name
     * @param array $cookieData
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function addReceivedCookie($name, $cookieData)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata();

        if (isset($cookieData['Max-Age']) && !empty($cookieData['Max-Age'])) {
            $metadata->setDuration((int) $cookieData['Max-Age']);
        }

        if (isset($cookieData['path']) && !empty($cookieData['path'])) {
            $metadata->setPath($cookieData['path']);
        }

        if (isset($cookieData['HttpOnly']) && !empty($cookieData['HttpOnly'])) {
            $metadata->setHttpOnly(true);
        }

        if (isset($cookieData['Secure']) && !empty($cookieData['Secure'])) {
            $metadata->setSecure(true);
        }

        if (isset($cookieData['SameSite']) && !empty($cookieData['SameSite'])) {
            $metadata->setSameSite($cookieData['SameSite']);
        }

        $this->cookieManager->setPublicCookie(
            $name,
            $cookieData['value'],
            $metadata
        );
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Send a 404 Not Found response.
     *
     * @return void
     */
    public function sendNotFound()
    {
        $this->getResponse()->clearBody()
            ->setStatusCode(Http::STATUS_CODE_404);
        $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
    }

    /**
     * Set the Newsman path.
     *
     * @param string $path
     * @return $this
     */
    public function setNzmPath($path)
    {
        $this->nzmPath = $path;
        return $this;
    }

    /**
     * Get the Newsman path.
     *
     * @return string
     */
    public function getNzmPath()
    {
        return $this->nzmPath;
    }

    /**
     * Set the Newsman request type.
     *
     * @param string $type
     * @return $this
     */
    public function setNzmType($type)
    {
        $this->nzmType = $type;
        return $this;
    }

    /**
     * Get the Newsman request type.
     *
     * @return string
     */
    public function getNzmType()
    {
        return $this->nzmType;
    }
}

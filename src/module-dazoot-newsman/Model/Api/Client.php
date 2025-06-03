<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Api;

use Dazoot\Newsman\Model\Config;
use Magento\Framework\HTTP\ClientInterface as HttpClientInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory as HttpClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Dazoot\Newsman\Logger\Logger;

/**
 * Newsman API client
 */
class Client implements ClientInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HttpClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var int|string|null
     */
    protected $status;

    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @param Config $config
     * @param HttpClientFactory $httpClientFactory
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        HttpClientFactory $httpClientFactory,
        Json $json,
        Logger $logger
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function get($context, $params = [])
    {
        return $this->request($context, 'GET', $params);
    }

    /**
     * @inheritdoc
     */
    public function post($context, $getParams = [], $postParams = [])
    {
        return $this->request($context, 'POST', $getParams, $postParams);
    }

    /**
     * @inheritdoc
     */
    public function request($context, $method, $getParams = [], $postParams = [])
    {
        $this->status = $this->errorMessage = $this->errorCode = null;
        $result = [];

        $url = $this->config->getApiUrl();
        $url .= sprintf(
            '%s/rest/%s/%s/%s.json',
            $this->config->getApiVersion(),
            $context->getUserId(),
            $context->getApiKey(),
            $context->getEndpoint()
        );
        if (is_array($getParams) && !empty($getParams)) {
            $url .= '?' . http_build_query($getParams);
        }
        $this->logger->debug(str_replace($context->getApiKey(), '****', $url));

        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setTimeout($this->config->getApiTimeout());
        $httpClient->addHeader('Content-Type', 'application/json');

        try {
            if ($method == 'POST') {
                $httpClient->post($url, $postParams);
                $this->logger->debug($this->json->serialize($postParams));
            } else {
                $httpClient->get($url);
            }

            $this->status = $httpClient->getStatus();
            if ($this->status == 200) {
                try {
                    $result = $this->json->unserialize($httpClient->getBody());
                    if (($apiError = $this->parseApiError($result)) !== false) {
                        $this->errorCode = $apiError['code'];
                        $this->errorMessage = $apiError['message'];
                        $this->logger->warning($this->errorCode . ' | ' . $this->errorMessage);
                    } else {
                        $this->logger->notice($this->json->serialize($result));
                    }
                } catch (\Exception $e) {
                    $this->errorCode = 1;
                    $this->errorMessage = $e->getMessage();
                    $this->logger->critical($e);
                    return [];
                }
            } else {
                $this->errorCode = $httpClient->getStatus();
                try {
                    if (stripos($httpClient->getBody(), '{') !== false) {
                        $body = $this->json->unserialize($httpClient->getBody());
                        if (($apiError = $this->parseApiError($body)) !== false) {
                            $this->errorCode = $apiError['code'];
                            $this->errorMessage = $apiError['message'];
                        } else {
                            $this->errorMessage = 'Error: ' . $this->errorCode;
                        }
                    }
                } catch (\Exception $e) {
                    $this->errorMessage = 'Error: ' . $this->errorCode;
                }
                $this->logger->error($httpClient->getStatus() . ' | ' . $httpClient->getBody());
            }
        } catch (\Exception $e) {
            /** @see \Magento\Framework\HTTP\Client\Curl::doError() */
            $this->errorCode = $httpClient->getStatus();
            $this->errorMessage = $e->getMessage();
            $this->logger->critical($e);
        }
        unset($httpClient);

        return $result;
    }

    /**
     * @param array $result
     * @return array|false
     */
    protected function parseApiError($result)
    {
        if (!(is_array($result) && isset($result['err']))) {
            return false;
        }

        return [
            'code' => isset($result['code']) ? $result['code'] : 0,
            'message' => $result['message'] ?? ''
        ];
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @inheritdoc
     */
    public function hasError()
    {
        return $this->errorCode > 0;
    }
}

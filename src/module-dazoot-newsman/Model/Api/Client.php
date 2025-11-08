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
     * @var int
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
        $this->status = $this->errorMessage = null;
        $this->errorCode = 0;
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
        $logHash = uniqid();
        $this->logger->debug('[' . $logHash . '] ' . str_replace($context->getApiKey(), '****', $url));

        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setTimeout($this->config->getApiTimeout());
        $httpClient->addHeader('Content-Type', 'application/json');

        try {
            $startTime = microtime(true);
            if ($method == 'POST') {
                $httpClient->post($url, is_array($postParams) ? $this->json->serialize($postParams) : $postParams);
                $this->logger->debug($this->json->serialize($postParams));
            } else {
                $httpClient->get($url);
            }

            $duration = round((microtime(true) - $startTime) * 1000);
            $this->logger->debug(__('[%1] Requested in %2', $logHash, $this->formatTimeDuration($duration)));

            $this->status = $httpClient->getStatus();
            if ($this->status == 200) {
                try {
                    $result = $this->json->unserialize($httpClient->getBody());
                    if (($apiError = $this->parseApiError($result)) !== false) {
                        $this->errorCode = (int) $apiError['code'];
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
                $this->errorCode = (int) $httpClient->getStatus();
                try {
                    if (stripos($httpClient->getBody(), '{') !== false) {
                        $body = $this->json->unserialize($httpClient->getBody());
                        if (($apiError = $this->parseApiError($body)) !== false) {
                            $this->errorCode = (int) $apiError['code'];
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
            $this->errorCode = (int) $httpClient->getStatus();
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

    /**
     * Format time duration based on thresholds
     *
     * @param int $milliSeconds The number of milliseconds to format.
     * @return string Formatted time.
     */
    public function formatTimeDuration($milliSeconds)
    {
        if ($milliSeconds < 1000) {
            return sprintf('%d ms', $milliSeconds);
        }

        $totalSeconds = $milliSeconds / 1000;

        if ($totalSeconds < 60) {
            return sprintf('%.1f s', $totalSeconds);
        }

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%d min %.3f s', $minutes, $seconds);
    }
}

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
     * Error code when Newsman cannot be reached (connection refused, timeout, DNS error) and there is no HTTP status
     */
    public const ERROR_CODE_CONNECTION = 599;

    /**
     * Error code at HTTP 200 when the answer is not valid JSON, or is a Newsman error ("err") without a code
     */
    public const ERROR_CODE_RESPONSE = 1;

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
     * @param ContextInterface $context
     * @param array $params
     * @return array
     */
    public function get($context, $params = [])
    {
        return $this->request($context, 'GET', $params);
    }

    /**
     * @inheritdoc
     * @param ContextInterface $context
     * @param array $getParams
     * @param array $postParams
     * @return array
     */
    public function post($context, $getParams = [], $postParams = [])
    {
        return $this->request($context, 'POST', $getParams, $postParams);
    }

    /**
     * @inheritdoc
     * @param ContextInterface $context
     * @param string $method
     * @param array $getParams
     * @param array $postParams
     * @return array
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
                        // A true "err" without a code is still an error. A false "err" without a code is not.
                        $this->setApiError($apiError, empty($apiError['err']) ? 0 : self::ERROR_CODE_RESPONSE);
                        $this->logger->warning($this->errorCode . ' | ' . $this->errorMessage);
                    } else {
                        $this->logger->notice($this->json->serialize($result));
                    }
                } catch (\Exception $e) {
                    $this->errorCode = self::ERROR_CODE_RESPONSE;
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
                            // Without a Newsman code the HTTP status stays the error code.
                            $this->setApiError($apiError, $this->errorCode);
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
            if ($this->errorCode <= 0) {
                $this->errorCode = self::ERROR_CODE_CONNECTION;
            }
            $this->errorMessage = $e->getMessage();
            $this->logger->critical($e);
        }
        unset($httpClient);

        return $result;
    }

    /**
     * Set the error code and message from a parsed Newsman error answer.
     *
     * The code of the answer is kept as it is. Without a code the fallback code is used (0 = not an error).
     *
     * @param array $apiError Result of parseApiError()
     * @param int $fallbackCode
     * @return void
     */
    protected function setApiError(array $apiError, $fallbackCode)
    {
        $this->errorMessage = $apiError['message'];
        if ($apiError['has_code']) {
            $this->errorCode = (int) $apiError['code'];
            return;
        }

        $this->errorCode = (int) $fallbackCode;
        if ($this->errorCode > 0 && $this->errorMessage === '') {
            $this->errorMessage = 'Error: ' . $this->errorCode;
        }
    }

    /**
     * Parse API error from the result.
     *
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
            'message' => $result['message'] ?? '',
            'has_code' => isset($result['code']),
            'err' => $result['err'],
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

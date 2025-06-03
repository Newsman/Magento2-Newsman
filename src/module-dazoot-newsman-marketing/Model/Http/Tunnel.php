<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsmanmarketing\Model\Http;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Http\Client\Curl;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Header\CookieFactory;
use Laminas\Http\Request;
use Magento\Framework\App\Response\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface as HttpClientInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory as HttpClientFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Remarketing tunnel request
 */
class Tunnel
{
    /**
     * Timeout on a request
     */
    public const TIMEOUT = 5;

    /**
     * @var CookieFactory
     */
    protected $cookieFactory;

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
     * @var GetMimeByFileExtension
     */
    protected $getMimeByFileExtension;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param CookieFactory $cookieFactory
     * @param Config $config
     * @param HttpClientFactory $httpClientFactory
     * @param Json $json
     * @param GetMimeByFileExtension $getMimeByFileExtension
     * @param Logger $logger
     */
    public function __construct(
        CookieFactory $cookieFactory,
        Config $config,
        HttpClientFactory $httpClientFactory,
        Json $json,
        GetMimeByFileExtension $getMimeByFileExtension,
        Logger $logger
    ) {
        $this->cookieFactory = $cookieFactory;
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->getMimeByFileExtension = $getMimeByFileExtension;
        $this->logger = $logger;
    }

    /**
     * @param string $tunnelUrl
     * @param string $method
     * @param string $requestUri
     * @param array $getParams
     * @param array $postParams
     * @param array $headers
     * @return DataObject|false
     */
    public function request($tunnelUrl, $method, $requestUri, $getParams = [], $postParams = [], $headers = [])
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlInfo = parse_url($requestUri);
        if (!(isset($urlInfo['host']) && !empty($urlInfo['host']))) {
            $url = $tunnelUrl . '/' . $requestUri;
        } else {
            $url = $tunnelUrl . '/' . $urlInfo['path'];
        }

        $headers = $this->filterSentHeaders($headers);

        if (is_array($getParams) && !empty($getParams)) {
            $url .= '?' . http_build_query($getParams);
        }

        /** @var Curl $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setTimeout($this->getTimeout());
        $httpClient->setOption(CURLOPT_FOLLOWLOCATION, true);
        foreach ($headers as $name => $value) {
            $httpClient->addHeader($name, $value);
        }

        try {
            if ($method == Request::METHOD_POST) {
                $httpClient->post($url, $postParams);
            } elseif ($method == Request::METHOD_GET) {
                $httpClient->get($url);
            } elseif ($method == Request::METHOD_HEAD) {
                $httpClient->makeCustomRequest($method, $url);
            } else {
                throw new LocalizedException(__('Invalid request method'));
            }

            if ($this->config->isLogTunnel()) {
                $this->logger->debug(
                    $url . ' ' . $method . ' ' . $this->json->serialize($postParams) . ' ' .
                    $this->json->serialize($getParams) . ' ' . $this->json->serialize($headers)
                );
            }

            $status = $httpClient->getStatus();
            $body = $httpClient->getBody();
            if ($status < 400) {
                if ($body === false) {
                    return new DataObject([
                        'status' => Http::STATUS_CODE_404
                    ]);
                }

                $return = new DataObject([
                    'body' => ($method == Request::METHOD_HEAD) ? '' : $httpClient->getBody(),
                    'cookies' => $this->extractReceivedCookies($httpClient->getHeaders()),
                    'status' => $status,
                    'headers' => $this->filterReceivedHeaders($httpClient->getHeaders(), $url)
                ]);

                if ($this->config->isLogTunnel()) {
                    $this->logger->debug(
                        $url . ' ' . $status . ' ' . $this->json->serialize($return->getData('headers')) . ' ' .
                        $this->json->serialize($return->getData('cookies'))
                    );
                }

                return $return;
            } else {
                return new DataObject([
                    'status' => $status
                ]);
            }
        } catch (\Exception $e) {
            if ($this->config->isLogTunnel()) {
                $this->logger->debug(
                    $e->getMessage() . ' ' . $url . ' ' . $method . ' ' . $this->json->serialize($postParams) . ' ' .
                    $this->json->serialize($getParams) . ' ' . $this->json->serialize($headers)
                );
            }
            return false;
        }
    }

    /**
     * TODO Set-Cookie
     *
     * @param array $headers
     * @return array
     */
    public function filterSentHeaders($headers)
    {
        $return = [];
        foreach ($headers as $name => $value) {
            if (!in_array($name, $this->getDisallowedSentHeaders())) {
                $return[$name] = $headers[$name];
            }
        }

        if (isset($return['Cookie'])) {
            $cookie = $this->filterSentCookies($return['Cookie']);
            if (empty($cookie)) {
                unset($return['Cookie']);
            } else {
                $return['Cookie'] = $cookie;
            }
        }
        return $return;
    }

    /**
     * @return string[]
     */
    public function getDisallowedSentHeaders()
    {
        return [
            //'Upgrade-Insecure-Requests',
            'Cache-Control',
            'Connection',
            'Host',
            'Accept-Encoding',
            'Content-Encoding',
        ];
    }

    /**
     * @see Cookie
     *
     * @param string $cookies
     * @return string
     */
    public function filterSentCookies($cookies)
    {
        if (empty($cookies)) {
            return '';
        }
        $filtered = [];
        /** @var Cookie $cookieHandler */
        $cookieHandler = $this->cookieFactory->create();
        $cookiesList = $cookieHandler->fromString('Cookie: ' . $cookies);
        foreach ($cookiesList as $name => $value) {
            if (!in_array($name, $this->getDisallowedSentCookies())) {
                $filtered[$name] = $value;
            }
        }
        if (empty($filtered)) {
            return '';
        }

        $nvPairs = [];
        foreach ($filtered as $name => $value) {
            $nvPairs[] = $name . '=' . urlencode($value);
        }

        return implode('; ', $nvPairs);
    }

    /**
     * @see https://experienceleague.adobe.com/en/docs/commerce-admin/start/compliance/privacy/compliance-cookie-law
     * @return string[]
     */
    public function getDisallowedSentCookies()
    {
        return [
            'PHPSESSID',
            'form_key',
            'add_to_cart',
            'guest-view',
            'login_redirect',
            'mage-banners-cache-storage',
            'remove_from_cart',
            'stf',
            'X-Magento-Vary',
            'form_key',
            'mage-cache-sessid',
            'persistent_shopping_cart',
            'private_content_version',
            'store',
            'admin',
            'loggedOutReasonCode',
            'section_data_clean',
            'lang',
            's_fid',
            's_cc',
            'apt.sid',
            'apt.uid',
            's_sq',
            'pagebuilder_modal_dismissed',
            'pagebuilder_template_apply_confirm',
            'mage-cache-storage-section-invalidation',
            'mage-cache-storage',
            'mage-messages',
            'recently_viewed_product',
            'recently_viewed_product_previous',
            'recently_compared_product',
            'recently_compared_product_previous',
            'product_data_storage',
            'section_data_ids',
            'mage-banners-cache-storage',
        ];
    }

    /**
     * @param array $headers
     * @param string $url
     * @return array
     */
    public function filterReceivedHeaders($headers, $url)
    {
        $return = [];
        foreach ($headers as $name => $value) {
            if (!in_array($name, $this->getDissallowedReceivedHeaders())) {
                $return[$name] = $headers[$name];
            }
        }

        if (!isset($return['content-type'])) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $fileExtension = pathinfo($url, PATHINFO_EXTENSION);
            if (!empty($fileExtension) && ($pos = stripos($fileExtension, '?')) !== false) {
                $fileExtension = substr($fileExtension, 0, $pos);
            }

            if (empty($fileExtension)) {
                $return['content-type'] = 'text/html';
            } else {
                $mimeType = $this->getMimeByFileExtension->execute($fileExtension);
                if (empty($mimeType)) {
                    $return['content-type'] = 'text/html';
                } else {
                    if ($mimeType == 'text/javascript') {
                        $mimeType = 'application/javascript';
                    }
                    $return['content-type'] = $mimeType;
                }
            }
        }

        return $return;
    }

    /**
     * Fixes \Magento\Framework\HTTP\Client\Curl::getCookiesFull for HttpOnly
     * @see \Magento\Framework\HTTP\Client\Curl
     *
     * @param array $headers
     * @return array
     */
    public function extractReceivedCookies($headers)
    {
        if (empty($headers['Set-Cookie'])) {
            return [];
        }
        $out = [];
        foreach ($headers['Set-Cookie'] as $row) {
            $values = explode("; ", $row ?? '');
            $c = count($values);
            if (!$c) {
                continue;
            }
            list($key, $val) = explode("=", $values[0]);
            if ($val === null) {
                continue;
            }
            $out[trim($key)] = ['value' => trim($val)];
            array_shift($values);
            $c--;
            if (!$c) {
                continue;
            }
            for ($i = 0; $i < $c; $i++) {
                if (stripos($values[$i], '=') === false) {
                    $out[trim($key)][trim($values[$i])] = true;
                    continue;
                }
                list($subkey, $val) = explode("=", $values[$i]);
                $out[trim($key)][trim($subkey)] = $val !== null ? trim($val) : '';
            }
        }
        return $out;
    }

    /**
     * @return string[]
     */
    public function getDissallowedReceivedHeaders()
    {
        return [
            'Server',
            'server',
            //'X-Server',
            //'x-server',
            'Vary',
            'vary'
        ];
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return self::TIMEOUT;
    }
}

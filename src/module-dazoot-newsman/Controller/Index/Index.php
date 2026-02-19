<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Index;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Export\Retriever\Processor;
use Dazoot\Newsman\Model\Export\Retriever\V1\ApiV1Exception;
use Dazoot\Newsman\Model\Export\Retriever\V1\PayloadParser;
use Dazoot\Newsman\Model\WebhooksFactory;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Dazoot\Newsman\Logger\Logger;
use Magento\Framework\App\CsrfAwareActionInterface;
use Dazoot\Newsman\Model\Export\Retriever\AuthenticatorException;
use Dazoot\Newsman\Model\Export\Retriever\Authenticator;

/**
 * Class Newsman index action
 */
class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var WebhooksFactory
     */
    protected $webhooksFactory;

    /**
     * @var Processor
     */
    protected $retrieverProcessor;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PayloadParser
     */
    protected $v1PayloadParser;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Json $serializer
     * @param WebhooksFactory $webhooksFactory
     * @param Processor $retrieverProcessor
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Config $config
     * @param PayloadParser $v1PayloadParser
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Json $serializer,
        WebhooksFactory $webhooksFactory,
        Processor $retrieverProcessor,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Config $config,
        PayloadParser $v1PayloadParser
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->webhooksFactory = $webhooksFactory;
        $this->retrieverProcessor = $retrieverProcessor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->config = $config;
        $this->v1PayloadParser = $v1PayloadParser;

        parent::__construct($context);
    }

    /**
     * Newsman index execute
     *
     * @return \Magento\Framework\Controller\Result\Json|ResultInterface|void
     */
    public function execute()
    {
        $result = [];

        $events = $this->getRequest()->getPost('newsman_events');
        if (!empty($events)) {
            try {
                $result = $this->webhooksFactory->create()
                    ->process($this->serializer->unserialize($events));
            } catch (\InvalidArgumentException $e) {
                $this->logger->critical($e);
                $result['error'] = $e->getMessage();
            } catch (\Exception $e) {
                $this->logger->error($e);
                $result['error'] = __('Something went wrong. Please try again later.');
            }

            return $this->resultJsonFactory->create()->setJsonData(
                $this->serializer->serialize($result)
            );
        }

        // Detect API v1 JSON payload (Content-Type: application/json or body starts with "{").
        $rawBody = (string) $this->getRequest()->getContent();
        $contentType = (string) $this->getRequest()->getHeader('Content-Type');
        if ($this->v1PayloadParser->isV1Payload($rawBody, $contentType)) {
            return $this->executeV1($rawBody);
        }

        // Legacy mode: query-string / form-POST parameters.
        try {
            $store = $this->storeManager->getStore();
            $parameters = $this->getRequest()->getParams();

            $apiKey = $this->getApiKeyFromHeader();
            if (!empty($apiKey) && empty($parameters[Authenticator::API_KEY_PARAM])) {
                $parameters[Authenticator::API_KEY_PARAM] = $apiKey;
            }

            $result = $this->retrieverProcessor->process(
                $this->retrieverProcessor->getCodeByData($parameters),
                $store,
                $parameters
            );
        } catch (AuthenticatorException $e) {
            $this->logger->critical($e);
            $this->getResponse()->clearBody()
                ->setStatusCode(Http::STATUS_CODE_403);
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return;
        } catch (LocalizedException $e) {
            $result = ['error' => $e->getMessage()];
            $this->logger->error($e);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
            $this->logger->error($e);
        }

        return $this->resultJsonFactory->create()->setJsonData(
            $this->serializer->serialize($result)
        );
    }

    /**
     * Handle an API v1 JSON payload request.
     *
     * Parses and validates the JSON body, authenticates via the Authorization
     * header (or nzmhash in params for convenience), routes to the matching
     * retriever, and returns a structured JSON response. All errors follow the
     * API v1 error format: {"error": {"code": <int>, "message": "<string>"}}.
     *
     * @param string $rawBody
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function executeV1($rawBody)
    {
        $jsonResult = $this->resultJsonFactory->create();

        try {
            $parsed = $this->v1PayloadParser->parse($rawBody);

            $store = $this->storeManager->getStore();

            // Resolve API key: Authorization header takes precedence, then nzmhash in params.
            // If neither is present, Processor::process() will throw AuthenticatorException,
            // which is caught below and returned as error code 1001.
            $apiKey = $this->getApiKeyFromHeader();
            if (!empty($apiKey)) {
                $parsed['data'][Authenticator::API_KEY_PARAM] = $apiKey;
            }

            $result = $this->retrieverProcessor->process(
                $parsed['code'],
                $store,
                $parsed['data']
            );

            return $jsonResult->setJsonData($this->serializer->serialize($result));

        } catch (ApiV1Exception $e) {
            $this->logger->critical($e);
            $jsonResult->setHttpResponseCode($e->getHttpStatus());
            return $jsonResult->setJsonData($this->serializer->serialize([
                'error' => [
                    'code'    => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                ]
            ]));
        } catch (AuthenticatorException $e) {
            $this->logger->critical($e);
            $jsonResult->setHttpResponseCode(403);
            return $jsonResult->setJsonData($this->serializer->serialize([
                'error' => [
                    'code'    => 1001,
                    'message' => 'Authentication failed',
                ]
            ]));
        } catch (LocalizedException $e) {
            $this->logger->error($e);
            $jsonResult->setHttpResponseCode(500);
            return $jsonResult->setJsonData($this->serializer->serialize([
                'error' => [
                    'code'    => 1009,
                    'message' => 'Internal server error',
                ]
            ]));
        } catch (\Exception $e) {
            $this->logger->error($e);
            $jsonResult->setHttpResponseCode(500);
            return $jsonResult->setJsonData($this->serializer->serialize([
                'error' => [
                    'code'    => 1009,
                    'message' => 'Internal server error',
                ]
            ]));
        }
    }

    /**
     * Get API key from the HTTP header
     *
     * @return string
     */
    protected function getApiKeyFromHeader()
    {
        /** @var \Laminas\Http\Headers $headers */
        $auth = $this->getRequest()->getServer('HTTP_AUTHORIZATION');
        if (empty($auth)) {
            $auth = $this->getRequest()->getHeader('Authorization');
            if (empty($auth)) {
                $name = $this->config->getExportAuthorizeHeaderName();
                if (!empty($name)) {
                    $auth =  trim((string) $this->getRequest()->getHeader($name));
                    if (!empty($auth)) {
                        return $auth;
                    }
                }
                return '';
            }
        }

        if (stripos($auth, 'Bearer') !== false) {
            return trim(str_replace('Bearer', '', $auth));
        }
        return (string) $auth;
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
}

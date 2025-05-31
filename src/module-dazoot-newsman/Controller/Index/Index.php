<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Index;

use Dazoot\Newsman\Model\Export\Retriever\Processor;
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Json $serializer
     * @param WebhooksFactory $webhooksFactory
     * @param Processor $retrieverProcessor
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Json $serializer,
        WebhooksFactory $webhooksFactory,
        Processor $retrieverProcessor,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->webhooksFactory = $webhooksFactory;
        $this->retrieverProcessor = $retrieverProcessor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;

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
        } else {
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
        }

        return $this->resultJsonFactory->create()->setJsonData(
            $this->serializer->serialize($result)
        );
    }

    /**
     * Get API key from HTTP header
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
                return '';
            }
        }

        if (stripos($auth, 'Bearer') !== false) {
            return trim(str_replace('Bearer', '', $auth));
        }
        return '';
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

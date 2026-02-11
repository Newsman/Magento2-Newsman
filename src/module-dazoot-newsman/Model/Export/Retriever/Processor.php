<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Retriever Processor
 */
class Processor
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Pool $pool
     * @param Authenticator $authenticator
     * @param Config $config
     * @param Json $serializer
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Pool $pool,
        Authenticator $authenticator,
        Config $config,
        Json $serializer,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->pool = $pool;
        $this->authenticator = $authenticator;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * Process retriever data
     *
     * @param string $code
     * @param StoreInterface $store
     * @param array $data
     * @return array
     * @throws AuthenticatorException
     */
    public function process($code, $store, $data = [])
    {
        $this->logger->info(__('Processing fetch data for store %1', $store->getName() . ' (' . $store->getId() . ')'));
        $tmpData = $data;
        unset($tmpData[Authenticator::API_KEY_PARAM]);
        $this->logger->info($this->serializer->serialize($tmpData));
        unset($tmpData);

        try {
            $apiKey = $this->getApiKeyFromData($code, $data);
            $this->authenticator->authenticate($apiKey, $store);
        } catch (AuthenticatorException $e) {
            $this->logger->critical($e);
            throw $e;
        }

        // Get list ID by specified store (usually the current store).
        $listId = $this->config->getListId($store);
        $storeIds = $this->config->getStoreIdsByListId($listId);
        if (empty($storeIds)) {
            $this->logger->notice(__('No store IDs found for retriever'));
            return [];
        }

        $retriever = $this->pool->getRetrieverByCode($code);
        unset($data[Authenticator::API_KEY_PARAM]);

        if ($retriever instanceof \Dazoot\Newsman\Model\Export\Retriever\Config) {
            $retriever->setRequestApyKey($apiKey);
        }

        return $retriever->process($data, $storeIds);
    }

    /**
     * Extract API key from request data.
     *
     * @param string $code
     * @param array $data
     * @return string
     */
    protected function getApiKeyFromData($code, $data)
    {
        if (!empty($data[Authenticator::API_KEY_PARAM])) {
            return $data[Authenticator::API_KEY_PARAM];
        }
        return '';
    }

    /**
     * Determine retriever code from request data.
     *
     * @param array $data
     * @return false|string
     */
    public function getCodeByData($data)
    {
        if (!(isset($data['newsman']) && !empty($data['newsman']))) {
            return false;
        }
        return str_replace('.json', '', $data['newsman']);
    }
}

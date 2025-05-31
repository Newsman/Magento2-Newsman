<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Write newsman admin configuration with a call from Newsman
 */
class Config implements RetrieverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var \Dazoot\Newsman\Model\Config
     */
    protected $config;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $configWhitelist = [];

    /**
     * @var string
     */
    protected $requestApyKey;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Json $json
     * @param \Dazoot\Newsman\Model\Config $config
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     * @param array $configWhitelist
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Json $json,
        \Dazoot\Newsman\Model\Config $config,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Logger $logger,
        $configWhitelist = []
    ) {
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
        $this->configWhitelist = $configWhitelist;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $this->logger->critical(__(
            'Attempt to change configuration for stores %1 %2',
            implode(",", $storeIds),
            json_encode($data)
        ));

        // Write configuration in current store only. Otherwise write in all stores from $storeIds parameter.
        // $storeIds are all stores that have the API key in admin configuration the same as the one in request.
        // The API key in request is used for authentication.
        $currentStoreOnly = isset($data['current_store_only']) && $data['current_store_only'];

        // This is for multistore if Newsman is not properly configured for current store URL.
        if ($currentStoreOnly && $this->config->getApiKey() !== $this->requestApyKey) {
            return [
                'error' => 'API key used for authentication does not match the one configured for store ' .
                    $this->storeManager->getStore()->getCode() . ' ' . $this->storeManager->getStore()->getName()
            ];
        }

        $configJson = !empty($data['config']) ? $data['config'] : '{}';
        try {
            $config = $this->json->unserialize($configJson);
            if (empty($config)) {
                return ['error' => 'Configuration is empty'];
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            return $result;
        }

        if (($notAllowedKeys = $this->validatePaths($config)) !== true) {
            return ['error' => 'Not allowed to write configuration paths: ' . implode(",", $notAllowedKeys)];
        }

        foreach ($this->getWriteStoreIds($storeIds, $currentStoreOnly) as $storeId) {
            foreach ($config as $key => $value) {
                $this->configWriter->save($key, $value, ScopeInterface::SCOPE_STORES, $storeId);
            }
        }

        $this->cleanCache();

        $this->logger->critical(__(
            'Changed configuration for stores %1 %2',
            implode(",", $storeIds),
            json_encode($data)
        ));

        return ['success' => true];
    }

    /**
     * @param array $config
     * @return true|array
     */
    protected function validatePaths($config)
    {
        $notAllowedKeys = [];
        foreach ($config as $key => $value) {
            if (!in_array($key, $this->configWhitelist)) {
                $notAllowedKeys[] = $key;
            }
        }

        return empty($notAllowedKeys) ? true : $notAllowedKeys;
    }

    /**
     * @param array $storeIds
     * @param bool $currentStoreOnly
     * @return array
     * @throws NoSuchEntityException
     */
    public function getWriteStoreIds($storeIds, $currentStoreOnly)
    {
        if ($currentStoreOnly) {
            return [$this->storeManager->getStore()->getId()];
        } else {
            return $storeIds;
        }
    }

    /**
     * @return void
     */
    public function cleanCache()
    {
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType(BlockCache::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType('full_page');
    }

    /**
     * @param string $requestApyKey
     * @return void
     */
    public function setRequestApyKey($requestApyKey)
    {
        $this->requestApyKey = $requestApyKey;
    }
}

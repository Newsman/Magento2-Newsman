<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Api;

use Dazoot\Newsman\Model\Config;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * API client context
 */
class Context implements ContextInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StoreInterface|null
     */
    protected $store;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var string
     */
    protected $segmentId;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var int
     */
    protected $listId;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getUserId()
    {
        if ($this->userId !== null) {
            return $this->userId;
        }
        return $this->config->getUserId($this->getStore());
    }

    /**
     * @inheritdoc
     */
    public function getSegmentId()
    {
        if ($this->segmentId !== null) {
            return $this->segmentId;
        }
        return $this->config->getSegmentId($this->getStore());
    }

    /**
     * @inheritdoc
     */
    public function getApiKey()
    {
        if ($this->apiKey !== null) {
            return $this->apiKey;
        }
        return $this->config->getApiKey($this->getStore());
    }

    /**
     * @inheritdoc
     */
    public function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStore()
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore();
        }
        return $this->store;
    }

    /**
     * @inheritdoc
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @inheritdoc
     */
    public function setListId($listId)
    {
        $this->listId = $listId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getListId()
    {
        if ($this->listId !== null) {
            return $this->listId;
        }
        return $this->config->getListId($this->getStore());
    }
}

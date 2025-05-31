<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Api;

use Magento\Store\Api\Data\StoreInterface;

/**
 * Newsman API Context interface
 */
interface ContextInterface
{
    /**
     * @return string
     */
    public function getUserId();

    /**
     * @return string
     */
    public function getSegmentId();

    /**
     * @return string
     */
    public function getApiKey();

    /**
     * @param StoreInterface $store
     * @return ContextInterface
     */
    public function setStore($store);

    /**
     * @return StoreInterface
     */
    public function getStore();

    /**
     * @param string $userId
     * @return ContextInterface
     */
    public function setUserId($userId);

    /**
     * @param string $segmentId
     * @return ContextInterface
     */
    public function setSegmentId($segmentId);

    /**
     * @param string $apiKey
     * @return ContextInterface
     */
    public function setApiKey($apiKey);

    /**
     * @param string $endpoint
     * @return ContextInterface
     */
    public function setEndpoint($endpoint);

    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @param int $listId
     * @return ContextInterface
     */
    public function setListId($listId);

    /**
     * @return int
     */
    public function getListId();
}

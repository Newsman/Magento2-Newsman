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
     * Get the Newsman user ID.
     *
     * @return string
     */
    public function getUserId();

    /**
     * Get the Newsman segment ID.
     *
     * @return string
     */
    public function getSegmentId();

    /**
     * Get the Newsman API key.
     *
     * @return string
     */
    public function getApiKey();

    /**
     * Set the store instance for the context.
     *
     * @param StoreInterface $store
     * @return ContextInterface
     */
    public function setStore($store);

    /**
     * Get the store instance from the context.
     *
     * @return StoreInterface
     */
    public function getStore();

    /**
     * Set the Newsman user ID.
     *
     * @param string $userId
     * @return ContextInterface
     */
    public function setUserId($userId);

    /**
     * Set the Newsman segment ID.
     *
     * @param string $segmentId
     * @return ContextInterface
     */
    public function setSegmentId($segmentId);

    /**
     * Set the Newsman API key.
     *
     * @param string $apiKey
     * @return ContextInterface
     */
    public function setApiKey($apiKey);

    /**
     * Set the Newsman API endpoint.
     *
     * @param string $endpoint
     * @return ContextInterface
     */
    public function setEndpoint($endpoint);

    /**
     * Get the Newsman API endpoint.
     *
     * @return string
     */
    public function getEndpoint();

    /**
     * Set the Newsman list ID.
     *
     * @param int $listId
     * @return ContextInterface
     */
    public function setListId($listId);

    /**
     * Get the Newsman list ID.
     *
     * @return int
     */
    public function getListId();
}

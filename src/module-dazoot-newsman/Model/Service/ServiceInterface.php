<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Dazoot\Newsman\Model\Api\ClientInterface;
use Dazoot\Newsman\Model\Api\ContextInterface;
use Magento\Store\Model\Store;
use Magento\Store\Api\Data\StoreInterface;

/**
 * API service interface
 */
interface ServiceInterface
{
    /**
     * Create a new Newsman API context instance.
     *
     * @return ContextInterface
     */
    public function createApiContext();

    /**
     * Create a new Newsman API client instance.
     *
     * @return ClientInterface
     */
    public function createApiClient();

    /**
     * Set the store context for the service.
     *
     * @param Store|StoreInterface $store
     * @return ServiceInterface
     */
    public function setStore($store);

    /**
     * Retrieve the current store context.
     *
     * @return Store|StoreInterface|null
     */
    public function getStore();

    /**
     * Execute the Newsman service action.
     *
     * @param ContextInterface $context
     * @return array
     */
    public function execute($context);
}

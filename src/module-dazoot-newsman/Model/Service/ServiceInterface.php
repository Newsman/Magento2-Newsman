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
     * @return ContextInterface
     */
    public function createApiContext();

    /**
     * @return ClientInterface
     */
    public function createApiClient();

    /**
     * @param Store|StoreInterface $store
     * @return ServiceInterface
     */
    public function setStore($store);

    /**
     * @return Store|StoreInterface|null
     */
    public function getStore();

    /**
     * @param ContextInterface $context
     * @return array
     */
    public function execute($context);
}

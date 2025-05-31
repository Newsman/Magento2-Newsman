<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context;

use Dazoot\Newsman\Model\Service\ContextInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Store data transfer context
 */
class StoreContext extends AbstractContext
{
    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @param StoreInterface $store
     * @return ContextInterface
     */
    public function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->store;
    }
}

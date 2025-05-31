<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Service\Context;

use Dazoot\Newsman\Model\Service\Context\StoreContext;
use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Set purchase (order) status (state) context
 */
class SetPurchaseStatusContext extends StoreContext
{
    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $state;

    /**
     * @param string $orderId
     * @return ContextInterface
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}

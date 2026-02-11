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
     * Increment ID of the order.
     *
     * @var string
     */
    protected $orderId;

    /**
     * Current state of the order.
     *
     * @var string
     */
    protected $state;

    /**
     * Set the order increment ID.
     *
     * @param string $orderId
     * @return ContextInterface
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Retrieve the order increment ID.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set the order state.
     *
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Retrieve the order state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}

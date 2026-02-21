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
 * Data transfer context for remarketing.saveOrders — saves multiple orders to Newsman remarketing.
 */
class SaveOrdersContext extends StoreContext
{
    /**
     * List of orders to save.
     *
     * @var array
     */
    protected $orders = [];

    /**
     * Set orders.
     *
     * @param array $orders
     * @return ContextInterface
     */
    public function setOrders(array $orders)
    {
        $this->orders = $orders;
        return $this;
    }

    /**
     * Get orders.
     *
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }
}

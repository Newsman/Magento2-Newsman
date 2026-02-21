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
 * Holds order details and order products for the remarketing.saveOrder API call.
 */
class SaveOrderContext extends StoreContext
{
    /**
     * Order details (order_no, email, firstname, etc.)
     *
     * @var array
     */
    protected $orderDetails = [];

    /**
     * Order products (id, quantity, price, variation_code)
     *
     * @var array
     */
    protected $orderProducts = [];

    /**
     * Set order details.
     *
     * @param array $orderDetails
     * @return ContextInterface
     */
    public function setOrderDetails(array $orderDetails)
    {
        $this->orderDetails = $orderDetails;
        return $this;
    }

    /**
     * Retrieve order details.
     *
     * @return array
     */
    public function getOrderDetails()
    {
        return $this->orderDetails;
    }

    /**
     * Set order products.
     *
     * @param array $orderProducts
     * @return ContextInterface
     */
    public function setOrderProducts(array $orderProducts)
    {
        $this->orderProducts = $orderProducts;
        return $this;
    }

    /**
     * Retrieve order products.
     *
     * @return array
     */
    public function getOrderProducts()
    {
        return $this->orderProducts;
    }
}

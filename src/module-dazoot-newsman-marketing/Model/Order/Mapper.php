<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Maps a Magento order to the Newsman remarketing.saveOrder API payload format.
 */
class Mapper
{
    /**
     * Build the order_details array for the API payload.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderDetails(OrderInterface $order)
    {
        $billingAddress = $order->getBillingAddress();

        $couponCode = (string) $order->getCouponCode();
        $discount   = round((float) abs((float) $order->getDiscountAmount()), 2);
        $shipping   = round((float) $order->getShippingAmount(), 2);
        $total      = round((float) $order->getGrandTotal(), 2);

        return [
            'order_no'      => $order->getIncrementId(),
            'firstname'     => $billingAddress ? $billingAddress->getFirstname() : '',
            'lastname'      => $billingAddress ? $billingAddress->getLastname() : '',
            'email'         => $order->getCustomerEmail(),
            'phone'         => $billingAddress ? (string) $billingAddress->getTelephone() : '',
            'status'        => $order->getState(),
            'created_at'    => $order->getCreatedAt(),
            'discount_code' => $couponCode,
            'discount'      => $discount,
            'shipping'      => $shipping,
            'rebates'       => 0,
            'fees'          => 0,
            'total'         => $total,
            'currency'      => $order->getOrderCurrencyCode(),
        ];
    }

    /**
     * Build the order_products array for the API payload.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderProducts(OrderInterface $order)
    {
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'id'             => (string) $item->getProductId(),
                'quantity'       => (int) $item->getQtyOrdered(),
                'price'          => round((float) $item->getPriceInclTax(), 2),
                'variation_code' => '',
            ];
        }
        return $products;
    }
}

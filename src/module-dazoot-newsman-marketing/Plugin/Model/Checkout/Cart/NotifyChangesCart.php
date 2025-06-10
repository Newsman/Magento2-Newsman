<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Plugin\Model\Checkout\Cart;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item;

/**
 * Notify product removed from cart plugin
 */
class NotifyChangesCart
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * @param Cart $subject
     * @param int $itemId
     * @return array
     */
    public function beforeRemoveItem(Cart $subject, $itemId)
    {
        if (!$this->config->isActive()) {
            return [$itemId];
        }

        try {
            $item = $subject->getQuote()->getItemById($itemId);

            if (!($item instanceof Item && $item->getProduct() instanceof Product &&
                $item->getProduct()->getId() > 0)) {
                return [$itemId];
            }

            $productData = [
                'id' => $item->getProduct()->getSku(),
                'quantity' => $item->getQty()
            ];

            $remove = $this->checkoutSession->getDazootMarketingRemoveCart();
            if (empty($remove)) {
                $remove = [];
            }
            $this->checkoutSession->setDazootMarketingRemoveCart(array_merge($remove, [$productData]));
        } catch (\Exception $e) {
            return [$itemId];
        }

        return [$itemId];
    }

    /**
     * @param Cart $subject
     * @param array $data
     * @return array
     */
    public function beforeUpdateItems(Cart $subject, $data)
    {
        if (!$this->config->isActive()) {
            return [$data];
        }

        if (!(is_array($data) && !empty($data))) {
            return [$data];
        }

        $addNew = [];
        $removeNew = [];
        foreach ($data as $itemId => $row) {
            if (!empty($row['remove'])) {
                /** @see self::beforeRemoveItem() */
                continue;
            }

            try {
                if (isset($row['qty']) && $row['qty'] > 0) {
                    $item = $subject->getQuote()->getItemById($itemId);
                    $diff = $row['qty'] - $item->getQty();

                    if ($diff == 0) {
                        continue;
                    }

                    if (!($item instanceof Item && $item->getProduct() instanceof Product) &&
                        $item->getProduct()->getId() > 0) {
                        continue;
                    }

                    if ($diff > 0) {
                        $productData = [
                            'id' => $item->getProduct()->getSku(),
                            'name' => $item->getName(),
                            'price' => number_format((float) $item->getPriceInclTax(), 2),
                            'quantity' => $diff
                        ];
                        $brandCode = $this->config->getBrandAttribute();
                        if (!empty($brandCode) && !empty($item->getProduct()->getData($brandCode))) {
                            $productData['brand'] = $item->getProduct()->getAttributeText($brandCode);
                        }
                        $addNew[] = $productData;
                    } else {
                        $removeNew[] = [
                            'id' => $item->getProduct()->getSku(),
                            'quantity' => abs($diff)
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $add = $this->checkoutSession->getDazootMarketingAddCart();
        if (empty($add)) {
            $add = [];
        }
        $this->checkoutSession->setDazootMarketingAddCart(array_merge($add, $addNew));

        $remove = $this->checkoutSession->getDazootMarketingRemoveCart();
        if (empty($remove)) {
            $remove = [];
        }
        $this->checkoutSession->setDazootMarketingRemoveCart(array_merge($remove, $removeNew));

        return [$data];
    }
}

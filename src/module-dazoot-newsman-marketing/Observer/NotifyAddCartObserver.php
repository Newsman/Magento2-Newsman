<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsmanmarketing\Observer;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Set Newsman Remarketing add cart product information to checkout session
 */
class NotifyAddCartObserver implements ObserverInterface
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
     * Notify Newsman after an item is successfully added to the cart.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isActive()) {
            return;
        }

        /** @var Item|string $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        if (!($quoteItem instanceof Item)) {
            return;
        }
        /** @var Product|string $product */
        $product = $observer->getEvent()->getProduct();

        $qty = 1;
        /** @see CaptureQtyBeforeAddCartObserver */
        if ($this->checkoutSession->getDazootMarketingQtyAddedCart()) {
            $qty = $this->checkoutSession->getDazootMarketingQtyAddedCart();
            $this->checkoutSession->unsDazootMarketingQtyAddedCart();
        }
        $productData = [
            'id' => $product->getSku(),
            'name' => $quoteItem->getName(),
            'price' => number_format((float) $quoteItem->getPrice(), 2, '.', ''),
            'quantity' => $qty
        ];
        $brandCode = $this->config->getBrandAttribute();
        if (!empty($brandCode) && !empty($product->getData($brandCode))) {
            $productData['brand'] = $product->getAttributeText($brandCode);
        }

        $add = $this->checkoutSession->getDazootMarketingAddCart();
        if (empty($add)) {
            $add = [];
        }
        $this->checkoutSession->setDazootMarketingAddCart(array_merge($add, [$productData]));
    }
}

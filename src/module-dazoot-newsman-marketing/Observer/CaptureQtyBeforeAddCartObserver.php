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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Set product qty added to checkout session for further processing
 *
 * @see NotifyAddCartObserver
 */
class CaptureQtyBeforeAddCartObserver implements ObserverInterface
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
     * Capture item quantity before it is added to the cart.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isActive()) {
            return;
        }

        /** @var DataObject|int|array|null $requestInfo */
        $requestInfo = $observer->getEvent()->getInfo();
        if (!($requestInfo instanceof Item || is_array($requestInfo))) {
            return;
        }

        $qty = 1;
        if (isset($requestInfo['qty']) && $requestInfo['qty'] > 0) {
            $qty = (float) $requestInfo['qty'];
        }

        $this->checkoutSession->setDazootMarketingQtyAddedCart($qty);
    }
}

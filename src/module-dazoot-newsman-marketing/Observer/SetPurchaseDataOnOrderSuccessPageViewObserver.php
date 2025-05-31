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
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * Set Newsman Remarketing purchase data module observer
 */
class SetPurchaseDataOnOrderSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param LayoutInterface $layout
     * @param Config $config
     */
    public function __construct(
        LayoutInterface $layout,
        Config $config
    ) {
        $this->layout = $layout;
        $this->config = $config;
    }

    /**
     * Add order information into Newsman Remarketing checkout success block to render on pages
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isActive()) {
            return;
        }

        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        /** @var \Magento\Framework\View\Element\Template|null $block */
        $block = $this->layout->getBlock('newsman.remarketing.purchase');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\CustomerData;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\DataObject;

/**
 * Newsman marketing source
 */
class Marketing extends DataObject implements SectionSourceInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Session $checkoutSession
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        array $data = []
    ) {
        parent::__construct($data);
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        if (!$this->config->isActive()) {
            return [];
        }

        $data = [];

        $add = $this->checkoutSession->getDazootMarketingAddCart();
        if (!empty($add)) {
            $data['add_cart'] = $add;
            $this->checkoutSession->unsDazootMarketingAddCart();
        }

        $remove = $this->checkoutSession->getDazootMarketingRemoveCart();
        if (!empty($remove)) {
            $data['remove_cart'] = $remove;
            $this->checkoutSession->unsDazootMarketingRemoveCart();
        }

        return $data;
    }
}

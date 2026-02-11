<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Block\Product;

use Magento\Catalog\Block\Product\View\Description;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Catalog\Model\Category;

/**
 * Product View Block class
 */
class View extends Description implements IdentityInterface
{
    /**
     * Get block cache identities based on current product.
     *
     * @return array
     */
    public function getIdentities()
    {
        return $this->getProduct()->getIdentities();
    }

    /**
     * Retrieve current category model from registry.
     *
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('current_category');
    }
}

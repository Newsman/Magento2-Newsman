<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Block\Category;

use Magento\Catalog\Block\Breadcrumbs;
use Magento\Catalog\Model\Category;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Category View Block class
 */
class View extends Template implements IdentityInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Prepare category layout and set current category data.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->getCurrentCategory();
        return $this;
    }

    /**
     * Retrieve current category model from registry.
     *
     * @return Category
     */
    public function getCurrentCategory()
    {
        if (!$this->hasData('current_category')) {
            $this->setData('current_category', $this->registry->registry('current_category'));
        }
        return $this->getData('current_category');
    }

    /**
     * Alias for getCurrentCategory.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->getCurrentCategory();
    }

    /**
     * Get block cache identities based on current category.
     *
     * @return array
     */
    public function getIdentities()
    {
        if ($this->getCurrentCategory()) {
            return $this->getCurrentCategory()->getIdentities();
        }
        return [];
    }
}

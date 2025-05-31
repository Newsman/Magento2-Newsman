<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Block\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\DataObject\IdentityInterface;
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
     * @return Category
     */
    public function getCategory()
    {
        if (!$this->hasData('current_category')) {
            $this->setData('current_category', $this->registry->registry('current_category'));
        }
        return $this->getData('current_category');
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return $this->getCurrentCategory()->getIdentities();
    }
}

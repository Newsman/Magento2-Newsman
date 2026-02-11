<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\Form\Field\Product;

use Dazoot\Newsman\Model\Config\Source\Product\Attributes as SourceAttributes;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class Attributes extends Select
{
    /**
     * @var SourceAttributes
     */
    protected $attributeSource;

    /**
     * @param Context $context
     * @param SourceAttributes $attributeSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        SourceAttributes $attributeSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributeSource = $attributeSource;
    }

    /**
     * Set input name for the select element.
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render the block as HTML.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $options = $this->attributeSource->toOptionArray();
            foreach ($options as $option) {
                if (in_array($option['value'], $this->getDisallowedAttributesCodes())) {
                    continue;
                }
                $this->_options[] = $option;
            }
        }
        return parent::_toHtml();
    }

    /**
     * Retrieve a list of attribute codes that are not allowed for mapping.
     *
     * @return string[]
     */
    public function getDisallowedAttributesCodes()
    {
        return ['entity_id', 'name', 'manufacturer'];
    }
}

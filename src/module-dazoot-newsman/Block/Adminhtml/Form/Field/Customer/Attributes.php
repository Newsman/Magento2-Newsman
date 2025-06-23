<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\Form\Field\Customer;

use Dazoot\Newsman\Model\Config\Source\Customer\Attributes as SourceAttributes;
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
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
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
     * @return string[]
     */
    public function getDisallowedAttributesCodes()
    {
        return ['email', 'firstname', 'lastname'];
    }
}

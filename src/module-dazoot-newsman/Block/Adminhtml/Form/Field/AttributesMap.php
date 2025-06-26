<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Html\Select;

class AttributesMap extends AbstractFieldArray
{
    /**
     * @var Select
     */
    protected $attributesRenderer;

    /**
     * Retrieve entity attributes column renderer
     *
     * @return Select
     * @throws LocalizedException
     */
    protected function getAttributesRenderer()
    {
        if (!$this->attributesRenderer) {
            $this->attributesRenderer = $this->getLayout()->createBlock(
                $this->getAttributesRendererClass(),
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributesRenderer;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getAttributesRendererClass()
    {
        throw new LocalizedException(__('Not implemented'));
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'a',
            ['label' => __('Attribute'), 'renderer' => $this->getAttributesRenderer()]
        );
        $this->addColumn('f', ['label' => __('Field in Newsman')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->getAttributesRenderer()->calcOptionHash(
            $row->getData('a')
        )] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = parent::render($element);
        $pos = strpos($html, '<td class="label">');
        if ($pos !== false) {
            $html = substr_replace($html, '<td style="width: 10%;" class="label">', $pos, strlen('<td class="label">'));
        }
        return $html;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        $html = parent::_renderValue($element);
        $pos = strpos($html, '<td class="value');
        if ($pos !== false) {
            $html = substr_replace($html, '<td style="width: 70%;" class="value', $pos, strlen('<td class="value'));
        }
        return $html;
    }
}

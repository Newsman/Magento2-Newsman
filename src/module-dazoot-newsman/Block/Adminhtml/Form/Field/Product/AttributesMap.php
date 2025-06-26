<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\Form\Field\Product;

use Dazoot\Newsman\Block\Adminhtml\Form\Field\Product\Attributes as ProductAttributes;

class AttributesMap extends \Dazoot\Newsman\Block\Adminhtml\Form\Field\AttributesMap
{
    /**
     * @return string
     */
    public function getAttributesRendererClass()
    {
        return ProductAttributes::class;
    }
}

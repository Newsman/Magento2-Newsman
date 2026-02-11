<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\Form\Field\Customer;

use Dazoot\Newsman\Block\Adminhtml\Form\Field\Customer\Attributes as CustomerAttributes;

class AttributesMap extends \Dazoot\Newsman\Block\Adminhtml\Form\Field\AttributesMap
{
    /**
     * Retrieve customer attributes mapper.
     *
     * @return string
     */
    public function getAttributesRendererClass()
    {
        return CustomerAttributes::class;
    }
}

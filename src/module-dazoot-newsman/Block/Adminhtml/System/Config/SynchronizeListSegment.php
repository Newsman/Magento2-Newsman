<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Synchronize Newsman list IDs and segments IDs
 */
class SynchronizeListSegment extends Field
{
    /**
     * @return $this
     */
    /**
     * Prepare layout and set template file for AJAX import button.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Dazoot_Newsman::system/config/import-list-segment.phtml');
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    /**
     * Render field without scope selectors.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    /**
     * Generate HTML for button and provide metadata for JS component.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('newsman/system_config/importListSegment'),
                'field_mapping' => str_replace('"', '\\"', json_encode([
                    'userId' => 'newsman_credentials_userId',
                    'apiKey' => 'newsman_credentials_apiKey'
                ]))
            ]
        );

        return $this->_toHtml();
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Render a "Test Credentials" button in system config
 */
class TestCredentials extends Field
{
    /**
     * Backend request instance.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $context->getRequest();
    }

    /**
     * Prepare layout and set template.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Dazoot_Newsman::system/config/test-credentials.phtml');
        return $this;
    }

    /**
     * Render form field element without scope controls.
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
     * Build button HTML and provide URL with current scope params.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $params = [];
        if ($website = (string)$this->request->getParam('website')) {
            $params['website'] = $website;
        }
        if ($store = (string)$this->request->getParam('store')) {
            $params['store'] = $store;
        }

        $this->addData([
            'button_label' => __($originalData['button_label'] ?? 'Test Credentials'),
            'html_id' => $element->getHtmlId(),
            'test_url' => $this->_urlBuilder->getUrl('newsman/system_config/testCredentials', $params)
        ]);

        return $this->_toHtml();
    }
}

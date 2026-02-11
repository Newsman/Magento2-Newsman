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
 * Render a "Configure with Newsman Login" button in system config
 */
class ConfigureWithLogin extends Field
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $context->getRequest();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Dazoot_Newsman::system/config/configure-with-login.phtml');
        return $this;
    }

    public function render(AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

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
            'button_label' => __($originalData['button_label'] ?? 'Configure with Newsman Login'),
            'html_id' => $element->getHtmlId(),
            'login_url' => $this->_urlBuilder->getUrl('newsman/system_config/login', $params)
        ]);

        return $this->_toHtml();
    }
}

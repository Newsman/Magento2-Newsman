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
use Magento\Store\Model\StoreManagerInterface;

/**
 * Render a "Configure with Newsman Login" button in system config
 */
class ConfigureWithLogin extends Field
{
    /**
     * Backend request instance.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $context->getRequest();
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare layout and set template.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Dazoot_Newsman::system/config/configure-with-login.phtml');
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

        $storeCount = count(array_filter(
            $this->storeManager->getStores(),
            function ($store) {
                return $store->isActive();
            }
        ));
        $showScopeWarning = $storeCount > 1 && empty($params['store']);

        $this->addData([
            'button_label' => __($originalData['button_label'] ?? 'Configure with Newsman Login'),
            'html_id' => $element->getHtmlId(),
            'login_url' => $this->_urlBuilder->getUrl('newsman/system_config/login', $params),
            'show_scope_warning' => $showScopeWarning,
        ]);

        return $this->_toHtml();
    }
}

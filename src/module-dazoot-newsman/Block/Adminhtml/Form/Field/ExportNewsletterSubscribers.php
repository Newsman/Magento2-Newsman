<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsman\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

/**
 * Export newsletter subscribers to Newsman by saved list ID and segment ID
 */
class ExportNewsletterSubscribers extends AbstractElement
{
    /**
     * Backend URL builder.
     *
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        UrlInterface $backendUrl,
        array $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->backendUrl = $backendUrl;
    }

    /**
     * Render the Export Newsletter Subscribers button HTML.
     *
     * @return string
     */
    public function getElementHtml()
    {
        /** @var Button $buttonBlock  */
        $buttonBlock = $this->getForm()->getParent()->getLayout()->createBlock(
            Button::class
        );

        $params = [
            'website' => $buttonBlock->getRequest()->getParam('website'),
            'store' => $buttonBlock->getRequest()->getParam('store'),
        ];

        $url = $this->backendUrl->getUrl("newsman/system_config/exportSubscribers", $params);
        $data = [
            'label' => __('Export Newsletter Subscribers'),
            'onclick' => "setLocation('" . $url . "')",
            'class' => '',
        ];

        return $buttonBlock->setData($data)
            ->toHtml();
    }
}

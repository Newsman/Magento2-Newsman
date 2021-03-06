<?php

namespace Dazoot\Newsman\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Link
 * @package Dazoot\Newsman\Block\Adminhtml\System\Config\Form\Field
 */
class Link extends Field
{

	/**
	 * @param Context $context
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		array $data = []
	) {
		parent::__construct($context, $data);
	}

	/**
	 * Render button
	 *
	 * @param  AbstractElement $element
	 * @return string
	 */
	public function render(AbstractElement $element)
	{
		// Remove scope label
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	/**
	 * Return element html
	 *
	 * @param  AbstractElement $element
	 * @return string
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _getElementHtml(AbstractElement $element)
	{
		return sprintf(
			'<a href ="%s#system_gmailsmtpapp-link">%s</a>',
			rtrim($this->_urlBuilder->getUrl('adminhtml/system_config/edit/section/system'), '/'),
			__('Stores > Configuration > Advanced > System > SMTP Configuration and Settings')
		);
	}
}

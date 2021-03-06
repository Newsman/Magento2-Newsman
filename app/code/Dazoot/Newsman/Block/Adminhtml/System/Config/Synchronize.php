<?php

namespace Dazoot\Newsman\Block\Adminhtml\System\Config;


class Synchronize extends \Magento\Config\Block\System\Config\Form\Field
{
	/**
	 * @var string
	 */
	protected $_template = 'Dazoot_Newsman::system/config/synchronize.phtml';

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		array $data = []
	) {
		parent::__construct($context, $data);
	}

	/**
	 * Remove scope label
	 *
	 * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 */
	public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	/**
	 * Return element html
	 *
	 * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		return $this->_toHtml();
	}

	/**
	 * Return ajax url for synchronize button
	 *
	 * @return string
	 */
	public function getAjaxSyncUrl()
	{

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		
		
		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		
		$storeId = (int) $this->getRequest()->getParam('website', 0);
		$param = "?storeid=" . $storeId;

		if($storeId == 0)
		{		
			$storeId = (int) $this->getRequest()->getParam('store', 0);
			$param = "?storeid=" . $storeId;
		}

		return $this->getUrl('newsman/system_config/synchronize') . $param;
	}

	/**
	 * Generate synchronize button html
	 *
	 * @return string
	 */
	public function getButtonHtml()
	{
		$button = $this->getLayout()->createBlock(
			'Magento\Backend\Block\Widget\Button'
		)->setData(
			[
				'id' => 'synchronize_button',
				'label' => __('Synchronize'),
			]
		);

		return $button->toHtml();
	}
}
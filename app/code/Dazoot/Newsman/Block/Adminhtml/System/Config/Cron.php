<?php
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Dazoot\Newsman\Helper\Data;
use Dazoot\Newsman\Helper\ApiClient;

class Cron extends \Magento\Backend\Block\AbstractBlock implements
	\Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
	/**
	 * @var \Dazoot\Newsman\Helper\Data
	 */
	protected $helper;

	/**
	 * Constructor
	 * @param Context $context
	 * @param array $data
	 * @param Data $helper
	 */
	public function __construct(
		Context $context,
		Data $helper
	)
	{
		$this->helper = $helper;
		parent::__construct($context);
	}

	/**
	 * Render form element as HTML
	 *
	 * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 */
	public function render(AbstractElement $element)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$storeId = $storeManager->getStore()->getId();
		$url = $storeManager->getStore()->getBaseUrl();

		$apiKey = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue("newsman/credentials/apiKey", $storeScope, $storeId);

		$html = 'CRON Sync url: </br>
<a target="_blank" href="' . $url . 'newsman/index/index?newsman=cron.json&nzmhash=' . $apiKey . '&start=1&limit=1000">' . $url . 'newsman/index/index?newsman=cron.json&nzmhash=' . $apiKey . '&start=1&limit=1000</a>
';
		return $html;
	}
}
<?php
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Dazoot\Newsman\Helper\Data;
use Dazoot\Newsman\Helper\ApiClient;

class Info extends \Magento\Backend\Block\AbstractBlock implements
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
		$version = $this->helper->getExtensionVersion();
		$name = $this->helper->getExtensionName();
		$logoUrl = 'http://s1-cdn.nl.nzmt.eu/images/templates/lcor/Images/NewsmanLogo/logo_white.png';

		$html = <<<HTML
<div style="width: 100%; padding: 15px; display: none;" id="infoPanel">
<span style="display: inline-block; color: #49e249; padding: 5px;" id="msgType"></span>
<button id="closeInfoPanel" style="display: inline-block; background: #a04747; color: #fff;" type="button">X</button>
</div>
<div style="border:1px solid #e3e3e3; min-height:100px; display;block;
padding:15px 15px 15px 15px;">
<p style="background: #a04747; border-radius: 10px;">
<img src="$logoUrl" style="padding: 5px;" />
</p>
<p>
<strong>$name v$version</strong> by <strong><a href="https://www.newsman.ro" target="_blank">Newsman</a></strong><br />
Import your customers in your Newsman account from Magento admin.</p>
<p>
<br />Like and follow us on 
<a href="http://www.facebook.com/NewsmanApp" target="_blank">Facebook</a>,
<a href="https://www.linkedin.com/company/newsman-app" target="_blank">LinkedIn</a> and
<a href="http://twitter.com/NewsmanApp" target="_blank">Twitter</a>.<br />
If you need support or have any questions, please contact us at
<a href="mailto:info@newsman.ro">info@newsman.ro</a>.
</p>
</div>
HTML;
		return $html;
	}
}

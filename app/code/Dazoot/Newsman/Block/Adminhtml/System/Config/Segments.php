<?php
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Dazoot\Newsman\Helper\Data;
use Dazoot\Newsman\Helper\Apiclient;

class Segments extends \Magento\Backend\Block\AbstractBlock implements
	\Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
	/**
	 * @var \Dazoot\Newsman\Helper\Data
	 */

	const XML_DATA_MAPPING = 'newsman/data/mapping';

	protected $helper;

	protected $client;

	protected $customerGroup;

	/**
	 * Constructor
	 * @param Context $context
	 * @param array $data
	 * @param Data $helper
	 */
	public function __construct(
		Context $context,
		Data $helper,
		\Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup
	)
	{
		$this->helper = $helper;
		$this->client = new Apiclient();
		$this->customerGroup = $customerGroup;
		parent::__construct($context);
	}

	public function getAjaxSyncUrl()
	{
		return $this->getUrl('newsman/system_config/segments');
	}

	public function getButtonHtml()
	{
		$button = $this->getLayout()->createBlock(
			'Magento\Backend\Block\Widget\Button'
		)->setData(
			[
				'id' => 'synchronizeSegments_button',
				'label' => __('Synchronize Segments'),
			]
		);

		return $button->toHtml();
	}

	/**
	 * Render form element as HTML
	 *
	 * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 */
	public function render(AbstractElement $element)
	{
		$customerGroups = $this->customerGroup->toOptionArray();

		$groupsCount = count($customerGroups);

		$html = "<div style='width: 49%; display: inline-block;'><p>Customer Groups</p>";

		for ($int = 1; $int < $groupsCount; $int++)
		{
			$id = "'" . "newsman_syncData_" . $customerGroups[$int]["value"] . "'";
			/*$name = "'" . "groups[syncData][fields][" . $customerGroups[$int]["value"] . "]" . "[value]'";*/
			$name = "segment" . $int;

			$html .= <<<HTML
<select id=$id name=$name class=" select admin__control-select" data-ui-id="select-groups-syncData-fields-customerGroups-value">
HTML;
			$label = $customerGroups[$int]["label"];
			$value = "'" . $customerGroups[$int]["value"] . "'";

			$html .= <<<HTML
<option value=$value selected="selected">$label</option>
HTML;

			$html .= <<<HTML
</select>
HTML;
		}

		$html .= <<<HTML
</div>
HTML;

		/*Insert segments*/

		$segments = $this->client->getSegmentsByList();

		/*Select data mapping from config_data*/

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$dataMapping = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_DATA_MAPPING);

		$dataMapping = json_decode($dataMapping, true);
		$dataMappingCount = count($dataMapping);

		$arr = array();

		$intCount = 1;
		for ($int = 0; $int < $dataMappingCount; $int++)
		{
			$arr[]["customerGroup"] = $intCount;
			$arr[]["segmentId"] = $dataMapping[$int][$intCount];
			$intCount++;
		}

		$html .= "<div style='width: 49%; display: inline-block;'><p>Newsman Segments</p>";

		$intArr = 1;
		$intCustomerGroup = 1;
		for ($int = 1; $int < $groupsCount; $int++)
		{
			$id = "'" . "newsman_syncData_" . $customerGroups[$int]["value"] . "segment'";
			/*$name = "'" . "groups[syncData][fields][" . $customerGroups[$int]["value"] . "segment]" . "[value]'";*/
			$name = "segment" . $int;

			$html .= <<<HTML
<select id=$id name=$name class=" select admin__control-select" data-ui-id="select-groups-syncData-fields-customerGroups-value">
HTML;

			$selected = "";
			$bool = false;

			$intArr = 1;
			$intCustomerGroup = 1;

			if ($segments != null && !array_key_exists("err", $segments))
			{
				foreach ($segments as $item => $value)
				{
					$label = $value["segment_name"];
					$values = "'" . $value["segment_id"] . "'";
					
					$html .= <<<HTML
<option value=$values>$label</option>
HTML;
				}
			} else
			{
				$html .= <<<HTML
<option value="nosegment">No segment</option>
HTML;
			}

			$html .= <<<HTML
</select>
HTML;
		}

		$html .= <<<HTML
</div>
HTML;

		$ajaxUrl = "'" . $this->getAjaxSyncUrl() . "'";

		$html .= <<<HTML
<script>
		require([
			'jquery',
			'prototype',
		], function (jQuery) {

param = {};

			function syncronizeSegments() {
			for(i = 0; i < $groupsCount; i++)
{
tempSegmentLabel = i;
tempSegmentValue = jQuery("#newsman_syncData_" + i + "segment").val();
param[tempSegmentLabel] = tempSegmentValue;
}

				params = param;
				new Ajax.Request($ajaxUrl, {
					loaderArea: false,
					asynchronous: true,
					parameters: params,
					dataType: "json",
					onSuccess: function (response) {
						jQuery('#infoPanel').css('display', 'block');
						jQuery('#msgType').html("Synchronization of segments completed..");
					},
					onError: function () {
						jQuery('#infoPanel').css('display', 'block');
						jQuery('#msgType').html("Synchronization of segments failed..");
					}
				});
			}

			jQuery('#synchronizeSegments_button').click(function () {
				syncronizeSegments();
				jQuery('#infoPanel').css('display', 'block');
				jQuery('#msgType').html("Synchronization of segments started..");
				console.log("Sync started");
			});
			jQuery('#closeInfoPanel').click(function () {
				jQuery('#infoPanel').css('display', 'none');
			});
		});
</script>
HTML;
		$enable = "";
		if ($segments != null)
		{
			$enable = "enabled";
		} else
		{
			$enable = "disabled";
		}

		$html .= <<<HTML
		<div style="display: block; width: 100%; padding: 20px 0px 5px 0px;">
<button id="synchronizeSegments_button" title="Synchronize Segments" type="button" class="action-default scalable" data-ui-id="widget-button-1" $enable>
    <span>Synchronize Segments</span>
</button>
</div>
HTML;

		return $html;
	}
}

<?php
namespace Dazoot\Newsmanmarketing\Block\Adminhtml\System;

/**
 * Class Extensions
 * @package Tatvic\EnhancedEcommerce\Block\Adminhtml\System
 */
class Support extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

		return '<script>var x = document.URL;</script>
		<div class="section-config">
		<div class="entry-edit-head admin__collapsible-block">
		<span id="tatvic_ee_support-link" class="entry-edit-head-link">
		</span><a id="tatvic_ee_support-head" href="#tatvic_ee_support-link" onclick="Fieldset.toggleCollapse(\'tatvic_ee_support\', \'x\'); return false;" class="">Installation Support</a></div>
		<input id="tatvic_ee_support-state" name="config_state[tatvic_ee_support]" type="hidden" value="0">
		<fieldset class="config admin__collapsible-block" id="tatvic_ee_support" style=""><legend>Conversion Settings</legend>
		<ul style="font-weight: 600;margin-left:25px;">
				<li><a style="font-size:18px;" href="https://www.newsman.com/" target="_blank">Get your Newsman Remarketing ID</a></li><br/>
				<br/>
			</ul>
		</fieldset>
		<script type="text/javascript">//<![CDATA[
		require(["prototype"], function(){Fieldset.applyCollapse("tatvic_ee_support");});
		//]]></script></div>';
    }
}
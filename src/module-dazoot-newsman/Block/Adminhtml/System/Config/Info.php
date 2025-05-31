<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Magento\Backend\Block\AbstractBlock;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Info extends AbstractBlock implements
    RendererInterface
{
    /**
     * Render form element as HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $logoUrl = 'http://s1-cdn.nl.nzmt.eu/images/templates/lcor/Images/NewsmanLogo/logo_white.png';
        $contactText = __('If you need support or have any questions, please contact us at');

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
<p><strong>Newsman</strong> by <strong><a href="https://www.newsman.ro" target="_blank">Newsman</a></strong><br /></p>
<p>
<br />Like and follow us on
<a href="http://www.facebook.com/NewsmanApp" target="_blank">Facebook</a>,
<a href="https://www.linkedin.com/company/newsman-app" target="_blank">LinkedIn</a> and
<a href="http://twitter.com/NewsmanApp" target="_blank">Twitter</a>.<br />
{$contactText}
<a href="mailto:info@newsman.ro">info@newsman.ro</a>.
</p>
</div>
HTML;
        return $html;
    }
}

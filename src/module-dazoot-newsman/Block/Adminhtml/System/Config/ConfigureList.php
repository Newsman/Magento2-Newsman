<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsman\Block\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class ConfigureList extends Template
{
    /** @var NewsmanConfig */
    protected $config;
    /** @var FormKey */
    protected $formKey;

    public function __construct(
        Context $context,
        NewsmanConfig $config,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->formKey = $formKey;
        $this->setTemplate('Dazoot_Newsman::system/config/save-configure-list.phtml');
    }

    public function getFormAction(): string
    {
        $params = [];
        $request = $this->getRequest();
        if ($website = (string)$request->getParam('website')) {
            $params['website'] = $website;
        }
        if ($store = (string)$request->getParam('store')) {
            $params['store'] = $store;
        }
        return $this->getUrl('newsman/system_config/saveConfigureList', $params);
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getListOptions(): array
    {
        $options = [["value" => '', "label" => (string)__("Please choose a list")]];
        $lists = $this->config->getStoredLists();
        foreach ($lists as $userId => $list) {
            foreach ($list as $item) {
                if (!(isset($item['list_type']) && $item['list_type'] === 'newsletter')) {
                    continue;
                }
                $options[] = [
                    'value' => $item['list_id'],
                    'label' => '[' . $userId . '] ' . $item['list_name'] . ' (' . $item['list_id'] . ')'
                ];
            }
        }
        return $options;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Config\Source;

use Dazoot\Newsman\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * All lists source
 */
class Lists implements OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array|null
     */
    protected $options;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = [['value' => '', 'label' => __('Please choose a list')]];

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

        $this->options = $options;
        return $options;
    }

    /**
     * @param int $value
     * @return string
     */
    public function getLabelByValue($value)
    {
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return '';
    }
}

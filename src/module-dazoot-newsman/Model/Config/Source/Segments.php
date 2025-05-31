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
 * All segments source
 */
class Segments implements OptionSourceInterface
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

        $options = [['value' => '', 'label' => __('Please choose a segment')]];

        $segments = $this->config->getStoredSegments();
        foreach ($segments as $userId => $userSegments) {
            foreach ($userSegments as $listId => $segments) {
                foreach ($segments as $item) {
                    $options[] = [
                        'value' => $item['segment_id'],
                        'label' => '{' . $listId . '} ' . $item['segment_name'] .
                            ' (' . $item['segment_id'] . ')'
                    ];
                }
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

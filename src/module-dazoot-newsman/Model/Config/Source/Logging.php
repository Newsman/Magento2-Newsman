<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Monolog\Logger;

/**
 * All logging source
 */
class Logging implements OptionSourceInterface
{
    /**
     * No logging
     */
    public const TYPE_NONE = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TYPE_NONE,
                'label' => __('None')
            ],
            [
                'value' => Logger::ERROR,
                'label' => __('Errors')
            ],
            [
                'value' => Logger::WARNING,
                'label' => __('Warning')
            ],
            [
                'value' => Logger::INFO,
                'label' => __('Info')
            ],
            [
                'value' => Logger::DEBUG,
                'label' => __('Debug')
            ]
        ];
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Config\Source\Customer;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Attributes implements OptionSourceInterface
{
    /**
     * @var CustomerMetadataInterface
     */
    protected $customerMetadata;

    /**
     * @param CustomerMetadataInterface $customerMetadata
     */
    public function __construct(
        CustomerMetadataInterface $customerMetadata
    ) {
        $this->customerMetadata = $customerMetadata;
    }

    /**
     * Options cache.
     *
     * @var array
     */
    protected $options;

    /**
     * Get options as an array.
     *
     * @param boolean $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        if (!$this->options) {
            $options = [];
            $attributesMetadata = $this->customerMetadata->getAllAttributesMetadata();
            foreach ($attributesMetadata as $attributeMetadata) {
                $options[] = [
                    'value' => $attributeMetadata->getAttributeCode(),
                    'label' => $attributeMetadata->getFrontendLabel() . ' ('
                        . $attributeMetadata->getAttributeCode() . ')'
                ];
            }
            $this->options = $this->sortOptionArray($options);
        }

        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('Please Select Attribute')]);
        }

        return $this->options;
    }

    /**
     * Sort option array by label.
     *
     * @param array $option
     * @return array
     */
    protected function sortOptionArray($option)
    {
        $data = [];
        foreach ($option as $item) {
            $data[$item['value']] = $item['label'];
        }
        asort($data);
        $option = [];
        foreach ($data as $key => $label) {
            $option[] = ['value' => $key, 'label' => $label];
        }
        return $option;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsman\Model\Config\Customer;

use Dazoot\Newsman\Helper\Customer\AttributesMap;
use Magento\Framework\Exception\LocalizedException;

class GetAdditionalAttributes
{
    /**
     * @var AttributesMap
     */
    protected $attributesMap;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param AttributesMap $attributesMap
     */
    public function __construct(AttributesMap $attributesMap)
    {
        $this->attributesMap = $attributesMap;
    }

    /**
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function get($storeIds)
    {
        $hash = md5(implode(',', $storeIds)); // phpcs:ignore

        if (isset($this->attributes[$hash]) && $this->attributes[$hash] !== null) {
            return $this->attributes[$hash];
        }
        $this->attributes[$hash] = [];
        foreach ($storeIds as $storeId) {
            $data = $this->attributesMap->getConfigValuebyStoreId($storeId);
            if (empty($data)) {
                continue;
            }
            foreach ($data as $row) {
                if (!empty($row['a']) && !empty($row['f']) && !isset($this->attributes[$hash][$row['a']])) {
                    $this->attributes[$hash][$row['a']] = $row['f'];
                }
            }
        }

        return $this->attributes[$hash];
    }
}

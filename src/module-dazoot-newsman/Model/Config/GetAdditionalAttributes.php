<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

namespace Dazoot\Newsman\Model\Config;

use Dazoot\Newsman\Helper\Customer\AttributesMap;
use Magento\Framework\Exception\LocalizedException;

class GetAdditionalAttributes
{
    /**
     * @var array
     */
    protected $attributes = [];

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
            $data = $this->getConfigValue($storeId);
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

    /**
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getConfigValue($storeId)
    {
        throw new LocalizedException(__('Not implemented'));
    }
}

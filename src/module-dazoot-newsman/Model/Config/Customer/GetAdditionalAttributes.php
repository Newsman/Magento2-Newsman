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

/**
 * Customer get additional attributes mapped to fields
 */
class GetAdditionalAttributes extends \Dazoot\Newsman\Model\Config\GetAdditionalAttributes
{
    /**
     * @var AttributesMap
     */
    protected $attributesMap;

    /**
     * @param AttributesMap $attributesMap
     */
    public function __construct(AttributesMap $attributesMap)
    {
        $this->attributesMap = $attributesMap;
    }

    /**
     * Retrieve additional attributes mapping for customers.
     *
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getConfigValue($storeId)
    {
        return $this->attributesMap->getConfigValuebyStoreId($storeId);
    }
}

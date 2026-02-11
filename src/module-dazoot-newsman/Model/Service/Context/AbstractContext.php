<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context;

use Dazoot\Newsman\Model\Service\ContextInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Abstract data transfer context
 */
class AbstractContext implements ContextInterface
{
    /**
     * Retrieve the Newsman API null value placeholder.
     *
     * @return string
     */
    public function getNullValue()
    {
        return self::NULL_VALUE;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Api\ErrorCode;

use Dazoot\Newsman\Model\Api\AbstractErrorCode;

/**
 * Newsman API error codes for endpoint subscriber.anonymiseEmail
 *
 * @see https://kb.newsman.com/api/1.2/subscriber.anonymiseEmail
 */
class Anonymise extends AbstractErrorCode
{
    /**
     * The email address is not on the list: there is nothing to anonymise
     */
    public const NO_SUCH_SUBSCRIBER = 121;
}

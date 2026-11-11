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
 * Newsman API error codes for endpoint subscriber.saveUnsubscribe
 *
 * @see https://kb.newsman.com/api/1.2/subscriber.saveUnsubscribe
 */
class Unsubscribe extends AbstractErrorCode
{
    /**
     * The email address is not on the list: there is nothing to unsubscribe
     */
    public const NO_SUCH_SUBSCRIBER = 121;
}

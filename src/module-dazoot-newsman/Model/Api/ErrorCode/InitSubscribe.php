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
 * Newsman API error codes for endpoint subscriber.initSubscribe
 *
 * @see https://kb.newsman.com/api/1.2/subscriber.initSubscribe
 */
class InitSubscribe extends AbstractErrorCode
{
    /**
     * Too many requests for this subscriber. Can only send once per 10 minutes
     */
    public const TOO_MANY_REQUESTS = 128;
}

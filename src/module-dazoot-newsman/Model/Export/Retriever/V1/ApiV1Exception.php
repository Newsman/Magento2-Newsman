<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever\V1;

/**
 * Exception for API v1 errors. Carries a structured error code and HTTP status.
 */
class ApiV1Exception extends \RuntimeException
{
    /**
     * @var int
     */
    private $errorCode;

    /**
     * @var int
     */
    private $httpStatus;

    /**
     * @param int $errorCode API v1 error code (e.g. 1001, 1002)
     * @param string $message Human-readable message
     * @param int $httpStatus HTTP status code to send
     */
    public function __construct($errorCode, $message, $httpStatus = 500)
    {
        $this->errorCode = (int) $errorCode;
        $this->httpStatus = (int) $httpStatus;
        parent::__construct($message);
    }

    /**
     * Get API v1 error code.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get HTTP status code for the response.
     *
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
}

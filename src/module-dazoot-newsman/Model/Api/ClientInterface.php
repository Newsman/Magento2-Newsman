<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Api;

/**
 * Newsman API Client interface
 */
interface ClientInterface
{
    /**
     * @param ContextInterface $context
     * @param array $params
     * @return array
     */
    public function get($context, $params = []);

    /**
     * @param ContextInterface $context
     * @param array $getParams
     * @param array $postParams
     * @return array
     */
    public function post($context, $getParams = [], $postParams = []);

    /**
     * @param ContextInterface $context
     * @param string $method
     * @param array $getParams
     * @param array $postParams
     * @return array
     */
    public function request($context, $method, $getParams = [], $postParams = []);

    /**
     * Get HTTP response status code
     *
     * @return int|string
     */
    public function getStatus();

    /**
     * Get error code from API, HTTP Error Code or JSON error == 1
     *
     * @return string
     */
    public function getErrorCode();

    /**
     * Get error message from API, HTTP error body message or JSON parse error
     *
     * @return string
     */
    public function getErrorMessage();

    /**
     * @return bool
     */
    public function hasError();
}

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
     * Perform a GET request to the Newsman API.
     *
     * @param ContextInterface $context
     * @param array $params
     * @return array
     */
    public function get($context, $params = []);

    /**
     * Perform a POST request to the Newsman API.
     *
     * @param ContextInterface $context
     * @param array $getParams
     * @param array $postParams
     * @return array
     */
    public function post($context, $getParams = [], $postParams = []);

    /**
     * Perform a custom request to the Newsman API.
     *
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
     * Check if the last request resulted in an error.
     *
     * @return bool
     */
    public function hasError();
}

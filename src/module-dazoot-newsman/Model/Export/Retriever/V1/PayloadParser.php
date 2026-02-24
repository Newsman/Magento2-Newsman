<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever\V1;

/**
 * Parses and validates the API v1 JSON request payload.
 *
 * Maps the JSON `method` field to an internal retriever code and flattens
 * `params.filter` into the top-level data array so the existing retrievers
 * can consume it without modification.
 */
class PayloadParser
{
    /**
     * Mapping from API v1 method name to internal retriever code.
     *
     * @var array
     */
    public static $methodMap = [
        'customer.list'          => 'customers',
        'subscriber.list'        => 'subscribers',
        'subscriber.subscribe'   => 'subscriber-subscribe',
        'subscriber.unsubscribe' => 'subscriber-unsubscribe',
        'product.list'           => 'products-feed',
        'order.list'             => 'orders',
        'coupon.create'          => 'coupons',
        'custom.sql'             => 'custom-sql',
        'platform.name'             => 'platform-name',
        'platform.version'          => 'platform-version',
        'platform.language'         => 'platform-language',
        'platform.language_version' => 'platform-language-version',
        'integration.name'          => 'integration-name',
        'integration.version'       => 'integration-version',
        'server.ip'                 => 'server-ip',
        'server.cloudflare'         => 'server-cloudflare',
        'sql.name'                  => 'sql-name',
        'sql.version'               => 'sql-version',
    ];

    /**
     * Determine whether the raw request body should be handled as an API v1 payload.
     *
     * Detection rules (either is sufficient):
     * - Content-Type header contains "application/json"
     * - Raw body starts with "{" (JSON object)
     *
     * @param string $rawBody
     * @param string $contentType
     * @return bool
     */
    public function isV1Payload($rawBody, $contentType = '')
    {
        if (!empty($contentType) && strpos($contentType, 'application/json') !== false) {
            return true;
        }
        $trimmed = ltrim((string) $rawBody);
        return !empty($trimmed) && $trimmed[0] === '{';
    }

    /**
     * Parse, validate and translate a JSON payload into a retriever code + flat data array.
     *
     * The returned array has two keys:
     * - "code": internal retriever code string (e.g. "customers")
     * - "data": flat key→value array ready to pass to Retriever::process()
     *
     * Filters from `params.filter` are merged into the top-level data array so
     * the existing AbstractRetriever::processListWhereParameters() can read them
     * by field name without any changes.
     *
     * @param string $rawBody Raw HTTP request body
     * @return array ['code' => string, 'data' => array]
     * @throws ApiV1Exception
     */
    public function parse($rawBody)
    {
        $payload = json_decode((string) $rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiV1Exception(1002, 'Invalid JSON payload', 400);
        }

        if (!is_array($payload) || !array_key_exists('method', $payload)) {
            throw new ApiV1Exception(1003, 'Missing "method" parameter', 400);
        }

        $method = $payload['method'];
        if (!isset(self::$methodMap[$method])) {
            throw new ApiV1Exception(1004, 'Unknown method: ' . $method, 404);
        }

        $params = isset($payload['params']) ? $payload['params'] : [];
        if (!is_array($params)) {
            throw new ApiV1Exception(1005, 'Invalid "params" parameter', 400);
        }

        // Flatten params.filter into the top-level data array.
        // The existing AbstractRetriever::processListWhereParameters() reads filter
        // fields by their request name at the top level of the $data array, so
        // {"filter": {"created_at": {"from": "2025-01-01"}}} becomes
        // $data['created_at'] = ['from' => '2025-01-01'].
        $data = $params;
        if (isset($params['filter']) && is_array($params['filter'])) {
            foreach ($params['filter'] as $fieldName => $fieldValue) {
                $data[$fieldName] = $fieldValue;
            }
        }
        unset($data['filter']);

        return [
            'code' => self::$methodMap[$method],
            'data' => $data,
        ];
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Detect whether the store server is behind Cloudflare
 */
class ServerCloudflare extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Returns true if the current request passed through Cloudflare's proxy
     * network, detected via the CF-Ray header that Cloudflare attaches to
     * every proxied request.
     *
     * @param array $data Data to filter entities, to save entities, other.
     * @param array $storeIds Store IDs.
     * @return array
     */
    public function process($data = [], $storeIds = [])
    {
        $cloudflare = !empty($this->request->getServer('HTTP_CF_RAY'));

        return ['cloudflare' => $cloudflare];
    }
}

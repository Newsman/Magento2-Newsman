<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Get server IP address
 */
class ServerIp extends AbstractRetriever implements RetrieverInterface
{
    /**
     * Process server IP retriever
     *
     * @param array $data Data to filter entities, to save entities, other.
     * @param array $storeIds Store IDs.
     * @return array
     */
    public function process($data = [], $storeIds = [])
    {
        $resolver = new \Dazoot\Newsman\Model\Util\ServerIpResolver();

        return ['ip' => $resolver->resolve()];
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Interface Retriever
 */
interface RetrieverInterface
{
    /**
     * Process retriever
     *
     * @param array $data
     * @param array $storeIds
     * @return array
     */
    public function process($data = [], $storeIds = []);
}

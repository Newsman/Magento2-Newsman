<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Get integration name
 */
class IntegrationName extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        return ['name' => 'newsman'];
    }
}

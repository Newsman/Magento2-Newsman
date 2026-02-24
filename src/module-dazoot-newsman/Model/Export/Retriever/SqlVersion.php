<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Magento\Framework\App\ResourceConnection;

/**
 * Get SQL server version
 */
class SqlVersion extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $full    = (string)$this->resource->getConnection()->fetchOne('SELECT VERSION()');
        $version = preg_replace('/[-\s].*/', '', $full);

        return ['version' => $version];
    }
}

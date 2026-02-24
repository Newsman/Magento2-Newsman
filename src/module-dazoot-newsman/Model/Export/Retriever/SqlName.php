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
 * Get SQL server name (MySQL or MariaDB)
 */
class SqlName extends AbstractRetriever implements RetrieverInterface
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
        $full = (string)$this->resource->getConnection()->fetchOne('SELECT VERSION()');
        $name = stripos($full, 'mariadb') !== false ? 'MariaDB' : 'MySQL';

        return ['name' => $name];
    }
}

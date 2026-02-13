<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use PHPSQLParser\PHPSQLParser;

/**
 * Custom SQL retriever
 *
 * Executes SELECT-only SQL queries with Magento table prefix placeholder replacement.
 * Table names use {table_name} syntax, e.g. {customer_entity} becomes the prefixed table name.
 */
class CustomSql extends AbstractRetriever implements RetrieverInterface
{
    /**
     * Statement types that are not allowed.
     *
     * @var array
     */
    protected $disallowedStatements = [
        // DML (write).
        'INSERT',
        'UPDATE',
        'DELETE',
        'REPLACE',
        // DDL.
        'CREATE',
        'ALTER',
        'DROP',
        'TRUNCATE',
        'RENAME',
        // Privileges.
        'GRANT',
        'REVOKE',
        // Locking.
        'LOCK',
        'UNLOCK',
        // Stored procedures / dynamic SQL.
        'CALL',
        'EXECUTE',
        'PREPARE',
        'DEALLOCATE',
        // File and handler operations.
        'LOAD',
        'HANDLER',
        // Server administration.
        'SET',
        'DO',
        'FLUSH',
        'RESET',
        'PURGE',
        'KILL',
        'SHUTDOWN',
        'INSTALL',
        'UNINSTALL',
        // Table maintenance.
        'ANALYZE',
        'CHECK',
        'CHECKSUM',
        'OPTIMIZE',
        'REPAIR',
        // Schema disclosure / database switching.
        'SHOW',
        'DESCRIBE',
        'EXPLAIN',
        'USE',
        // Transaction control.
        'BEGIN',
        'COMMIT',
        'ROLLBACK',
        'SAVEPOINT',
        'RELEASE',
        'XA',
    ];

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ResourceConnection $resource
     * @param Logger $logger
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Process custom SQL retriever
     *
     * @param array $data
     * @param array $storeIds
     * @return array
     * @throws LocalizedException
     */
    public function process($data = [], $storeIds = [])
    {
        $sql = isset($data['sql']) ? trim((string) $data['sql']) : '';

        if (empty($sql)) {
            throw new LocalizedException(__('The "sql" parameter is required.'));
        }

        $this->validateSelectOnly($sql);

        $sql = $this->replaceTablePlaceholders($sql);

        $this->logger->notice(__(
            'Custom SQL export, store IDs %1 - Query: %2',
            implode(',', $storeIds),
            $sql
        ));

        $connection = $this->resource->getConnection();
        $result = $connection->fetchAll($sql);

        $this->logger->notice(__(
            'Custom SQL export, store IDs %1 - Rows returned: %2',
            implode(',', $storeIds),
            count($result)
        ));

        return $result;
    }

    /**
     * Validate that the SQL is a SELECT-only query.
     *
     * @param string $sql
     * @return void
     * @throws LocalizedException
     */
    protected function validateSelectOnly($sql)
    {
        $this->validateNoMultipleStatements($sql);

        $parser = new PHPSQLParser();
        $parsed = $parser->parse($sql);

        if (empty($parsed)) {
            throw new LocalizedException(__('Unable to parse the SQL query.'));
        }

        $statementType = key($parsed);

        if ($statementType !== 'SELECT') {
            throw new LocalizedException(__('Only SELECT queries are allowed. Got: ' . $statementType));
        }

        if (isset($parsed['INTO'])) {
            throw new LocalizedException(__('SELECT INTO is not allowed.'));
        }
    }

    /**
     * Check for semicolons outside of string literals.
     *
     * @param string $sql
     * @return void
     * @throws LocalizedException
     */
    protected function validateNoMultipleStatements($sql)
    {
        $stripped = preg_replace("/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s", '', $sql);
        $stripped = preg_replace('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', '', $stripped);

        if (strpos($stripped, ';') !== false) {
            throw new LocalizedException(__('Multiple statements are not allowed.'));
        }
    }

    /**
     * Replace {table_name} placeholders with prefixed table names.
     *
     * @param string $sql
     * @return string
     */
    protected function replaceTablePlaceholders($sql)
    {
        $resource = $this->resource;

        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function ($matches) use ($resource) {
                return $resource->getTableName($matches[1]);
            },
            $sql
        );
    }
}

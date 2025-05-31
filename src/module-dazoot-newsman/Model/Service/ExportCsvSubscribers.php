<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsman\Model\Service\Context\ExportCsvSubscribersContext;

/**
 * Export CSV with subscribers by list ID
 */
class ExportCsvSubscribers extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/import.csv
     */
    public const ENDPOINT = 'import.csv';

    /**
     * @param ExportCsvSubscribersContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);
        $segmentId = $apiContext->getSegmentId();

        $this->logger->info(__(
            'Try to export %1 subscribers to list %2',
            count($context->getCsvData()),
            $apiContext->getListId()
        ));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [
                'list_id' => $apiContext->getListId(),
                'segments' => !empty($segmentId) ? [$segmentId] : $context->getNullValue(),
                'csv_data' => $this->serializeCsvData($context->getCsvData())
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__(
            'Exported %1 subscribers to list %2',
            count($context->getCsvData()),
            $apiContext->getListId()
        ));

        return $result;
    }

    /**
     * @param array $csvData
     * @return string void
     */
    public function serializeCsvData($csvData, $source = 'Magento2')
    {
        $csv = '"' . implode('","', $this->getCsvHeader()) . "\"\n";
        foreach ($csvData as $key => $row) {
            foreach ($row as &$value) {
                if ($value === null) {
                    $value = '';
                }
                $value = trim(str_replace('"', '', $value));
            }
            $row['source'] = $source;
            $csv .= $this->getCsvLine($row, $key);
        }
        return $csv;
    }

    /**
     * @return string[]
     */
    public function getCsvHeader()
    {
        return [
            'email',
            'firstname',
            'lastname',
            'source'
        ];
    }

    /**
     * @param array $row
     * @param int $key
     * @return string
     */
    public function getCsvLine($row, $key)
    {
        return '"' . implode('","', $row) . "\"\n";
    }
}

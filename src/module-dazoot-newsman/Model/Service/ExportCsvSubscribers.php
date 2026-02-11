<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Dazoot\Newsman\Model\Service\Context\ExportCsvSubscribersContext;
use Magento\Framework\Exception\LocalizedException;

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
     * Execute the CSV export API call.
     *
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
            [],
            [
                'list_id' => $apiContext->getListId(),
                'segments' => !empty($segmentId) ? [$segmentId] : $context->getNullValue(),
                'csv_data' => $this->serializeCsvData($context)
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
     * Serialize CSV data from context for the API request.
     *
     * @param ExportCsvSubscribersContext $context
     * @param string $source
     * @return string
     */
    public function serializeCsvData($context, $source = 'Magento2')
    {
        $header = $this->getCsvHeader($context);
        $columnCount = count($header);
        $csvData = $context->getCsvData();
        $additionalFields = $context->getAdditionalFields();

        $csv = '"' . implode('","', $this->getCsvHeader($context)) . "\"\n";
        foreach ($csvData as $key => $row) {
            $exportRow = array_combine($header, array_fill(0, $columnCount, ''));
            foreach ($row as $column => &$value) {
                if ($column !== 'additional') {
                    if ($value === null) {
                        $value = '';
                    }
                    $value = trim(str_replace('"', '', $value));
                } else {
                    if ($value === null) {
                        $value = [];
                    }
                }
            }
            $row['source'] = $source;

            foreach ($additionalFields as $attributeCode => $field) {
                $row[$field] = '';
                if (isset($row['additional'][$attributeCode])) {
                    $row[$field] = $row['additional'][$attributeCode];
                }
            }

            foreach ($exportRow as $exportKey => &$exportValue) {
                if (isset($row[$exportKey])) {
                    $exportValue = $row[$exportKey];
                }
            }

            $csv .= $this->getCsvLine($exportRow, $key);
        }

        return $csv;
    }

    /**
     * Build the CSV header array based on context.
     *
     * @param ExportCsvSubscribersContext $context
     * @return string[]
     */
    public function getCsvHeader($context)
    {
        $header = [
            'email',
            'firstname',
            'lastname',
        ];

        if ($this->config->isCustomerSendTelephoneByStoreIds($context->getStoreIds())) {
            $header[] = 'telephone';
            $header[] = 'billing_telephone';
            $header[] = 'shipping_telephone';
        }

        $header[] = 'source';

        foreach ($this->getAdditionalFieldsNames($context) as $field) {
            if (!in_array($field, $header)) {
                $header[] = $field;
            }
        }
        return $header;
    }

    /**
     * Convert a data row into a CSV line.
     *
     * @param array $row
     * @param int $key
     * @return string
     */
    public function getCsvLine($row, $key)
    {
        unset($row['additional']);
        return '"' . implode('","', $row) . "\"\n";
    }

    /**
     * Retrieve additional field names for the CSV from context.
     *
     * @param ExportCsvSubscribersContext $context
     * @return array
     */
    public function getAdditionalFieldsNames($context)
    {
        return array_values($context->getAdditionalFields());
    }
}

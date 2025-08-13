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
     * @param ExportCsvSubscribersContext $context
     * @return string void
     */
    public function serializeCsvData($context, $source = 'Magento2')
    {
        $csvData = $context->getCsvData();
        $additionalFields = $context->getAdditionalFields();

        $csv = '"' . implode('","', $this->getCsvHeader($context)) . "\"\n";
        foreach ($csvData as $key => $row) {
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

            $csv .= $this->getCsvLine($row, $key);
        }

        return $csv;
    }

    /**
     * @param ExportCsvSubscribersContext $context
     * @return string[]
     */
    public function getCsvHeader($context)
    {
        $header = [
            'email',
            'firstname',
            'lastname',
            'telephone',
            'billing_telephone',
            'shipping_telephone',
            'source'
        ];
        foreach ($this->getAdditionalFieldsNames($context) as $field) {
            if (!in_array($field, $header)) {
                $header[] = $field;
            }
        }
        return $header;
    }

    /**
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
     * @param ExportCsvSubscribersContext $context
     * @return array
     */
    public function getAdditionalFieldsNames($context)
    {
        return array_values($context->getAdditionalFields());
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context;

use Dazoot\Newsman\Model\Service\ContextInterface;

class ExportCsvSubscribersContext extends StoreContext
{
    /**
     * CSV data for export.
     *
     * @var array
     */
    protected $csvData;

    /**
     * Store IDs involved in export.
     *
     * @var array
     */
    protected $storeIds = [];

    /**
     * Additional Newsman fields mapping.
     *
     * @var array
     */
    protected $additionalFields = [];

    /**
     * Set CSV data for export.
     *
     * @param array $data
     * @return ContextInterface
     */
    public function setCsvData($data)
    {
        $this->csvData = $data;
        return $this;
    }

    /**
     * Get CSV data for export.
     *
     * @return array
     */
    public function getCsvData()
    {
        return $this->csvData;
    }

    /**
     * Set store IDs for export.
     *
     * @param array $storeIds
     * @return ContextInterface
     */
    public function setStoreIds($storeIds)
    {
        $this->storeIds = $storeIds;
        return $this;
    }

    /**
     * Get store IDs for export.
     *
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * Set additional Newsman fields for export.
     *
     * @param array $data
     * @return ContextInterface
     */
    public function setAdditionalFields($data)
    {
        $this->additionalFields = $data;
        return $this;
    }

    /**
     * Get additional Newsman fields for export.
     *
     * @return array
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }
}

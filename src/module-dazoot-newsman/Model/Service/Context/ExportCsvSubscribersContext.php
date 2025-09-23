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
     * @var array
     */
    protected $csvData;

    /**
     * @var array
     */
    protected $storeIds = [];

    /**
     * @var array
     */
    protected $additionalFields = [];

    /**
     * @param array $data
     * @return ContextInterface
     */
    public function setCsvData($data)
    {
        $this->csvData = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getCsvData()
    {
        return $this->csvData;
    }

    /**
     * @param array $storeIds
     * @return ContextInterface
    */
    public function setStoreIds($storeIds)
    {
        $this->storeIds = $storeIds;
        return $this;
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @param array $data
     * @return ContextInterface
    */
    public function setAdditionalFields($data)
    {
        $this->additionalFields = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }
}

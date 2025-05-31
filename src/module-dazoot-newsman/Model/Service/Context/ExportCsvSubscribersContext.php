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
}

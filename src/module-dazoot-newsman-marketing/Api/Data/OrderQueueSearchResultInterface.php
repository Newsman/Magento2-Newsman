<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * OrderQueueSearchResultInterface interface
 */
interface OrderQueueSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return OrderQueueInterface[]
     */
    public function getItems();

    /**
     * @param OrderQueueInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

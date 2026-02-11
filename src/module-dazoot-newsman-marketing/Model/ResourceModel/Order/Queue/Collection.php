<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\ResourceModel\Order\Queue;

/**
 * Order Queue collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'queue_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'dazoot_newsmanmarketing_order_queue_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'dazoot_newsmanmarketing_order_queue_collection';

    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Dazoot\Newsmanmarketing\Model\Order\Queue::class,
            \Dazoot\Newsmanmarketing\Model\ResourceModel\Order\Queue::class
        );
    }
}

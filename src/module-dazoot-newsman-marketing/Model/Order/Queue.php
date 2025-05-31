<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Order;

use Dazoot\Newsmanmarketing\Api\Data\OrderQueueInterface;

/**
 * Order Queue Model
 */
class Queue extends \Magento\Framework\Model\AbstractExtensibleModel implements OrderQueueInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'dazoot_newsmanmarketing_order_queue';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Dazoot\Newsmanmarketing\Model\ResourceModel\Order\Queue::class);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return parent::getData(self::QUEUE_ID);
    }

    /**
     * @inheritdoc
     */
    public function getQueueId()
    {
        return parent::getData(self::QUEUE_ID);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function getState()
    {
        return $this->getData(self::STATE);
    }

    /**
     * @inheritdoc
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function getSent()
    {
        return $this->getData(self::SENT);
    }

    /**
     * @inheritdoc
     */
    public function getFailures()
    {
        return $this->getData(self::FAILURES);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::QUEUE_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setQueueId($queueId)
    {
        return $this->setData(self::QUEUE_ID, $queueId);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function setState($state)
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @inheritdoc
     */
    public function setSent($sent)
    {
        return $this->setData(self::SENT, $sent);
    }

    /**
     * @inheritdoc
     */
    public function setFailures($failures)
    {
        return $this->setData(self::FAILURES, $failures);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Api\Data;

/**
 * Api Data Order Queue interface.
 */
interface OrderQueueInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const QUEUE_ID = 'queue_id';

    public const ORDER_ID = 'order_id';

    public const STORE_ID = 'store_id';

    public const STATE = 'state';

    public const INCREMENT_ID = 'increment_id';

    public const SENT = 'sent';

    public const FAILURES = 'failures';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get queue ID
     *
     * @return int|null
     */
    public function getQueueId();

    /**
     * Get Order ID
     *
     * @return int|null
     */
    public function getOrderId();

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Get state
     *
     * @return string
     */
    public function getState();

    /**
     * Get increment ID
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Get sent
     *
     * @return int
     */
    public function getSent();

    /**
     * Get failures
     *
     * @return int
     */
    public function getFailures();

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set ID
     *
     * @param int $id
     * @return OrderQueueInterface
     */
    public function setId($id);

    /**
     * Set queue ID
     *
     * @param int $queueId
     * @return OrderQueueInterface
     */
    public function setQueueId($queueId);

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return OrderQueueInterface
     */
    public function setOrderId($orderId);

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return OrderQueueInterface
     */
    public function setStoreId($storeId);

    /**
     * Set state
     *
     * @param string $state
     * @return OrderQueueInterface
     */
    public function setState($state);

    /**
     * Set increment ID
     *
     * @param string $incrementId
     * @return OrderQueueInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Set sent
     *
     * @param int $sent
     * @return OrderQueueInterface
     */
    public function setSent($sent);

    /**
     * Set failures
     *
     * @param int $failures
     * @return OrderQueueInterface
     */
    public function setFailures($failures);

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return OrderQueueInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return OrderQueueInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Dazoot\Newsmanmarketing\Api\Data\OrderQueueExtensionInterface $extensionAttributes
    );
}

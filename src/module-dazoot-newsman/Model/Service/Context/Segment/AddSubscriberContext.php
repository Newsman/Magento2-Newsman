<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Segment;

use Dazoot\Newsman\Model\Service\Context\StoreContext;
use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Data transfer context for segment.addSubscriber — adds an email subscriber to a segment.
 */
class AddSubscriberContext extends StoreContext
{
    /**
     * Newsman segment ID.
     *
     * @var string|int
     */
    protected $segmentId;

    /**
     * Newsman subscriber ID (email subscriber).
     *
     * @var string|int
     */
    protected $subscriberId;

    /**
     * Set segment ID.
     *
     * @param string|int $segmentId
     * @return ContextInterface
     */
    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
        return $this;
    }

    /**
     * Get segment ID.
     *
     * @return string|int
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * Set subscriber ID.
     *
     * @param string|int $subscriberId
     * @return ContextInterface
     */
    public function setSubscriberId($subscriberId)
    {
        $this->subscriberId = $subscriberId;
        return $this;
    }

    /**
     * Get subscriber ID.
     *
     * @return string|int
     */
    public function getSubscriberId()
    {
        return $this->subscriberId;
    }
}

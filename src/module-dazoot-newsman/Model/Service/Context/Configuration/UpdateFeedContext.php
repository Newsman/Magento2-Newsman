<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Configuration;

use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Data transfer context for feeds.updateFeed — updates properties of an existing feed.
 */
class UpdateFeedContext extends ListContext
{
    /**
     * Feed ID to update.
     *
     * @var string|int
     */
    protected $feedId;

    /**
     * Feed properties to update.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Set feed ID.
     *
     * @param string|int $feedId
     * @return ContextInterface
     */
    public function setFeedId($feedId)
    {
        $this->feedId = $feedId;
        return $this;
    }

    /**
     * Get feed ID.
     *
     * @return string|int
     */
    public function getFeedId()
    {
        return $this->feedId;
    }

    /**
     * Set feed properties.
     *
     * @param array $properties
     * @return ContextInterface
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Get feed properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}

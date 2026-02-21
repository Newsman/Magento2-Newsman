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
 * Data transfer context for feeds.setFeedOnList — installs a product feed on a list.
 */
class SetFeedOnListContext extends ListContext
{
    /**
     * Feed URL.
     *
     * @var string
     */
    protected $url = '';

    /**
     * Store / website URL.
     *
     * @var string
     */
    protected $website = '';

    /**
     * Feed type.
     *
     * @var string
     */
    protected $type = 'fixed';

    /**
     * Whether to return the created feed ID.
     *
     * @var int
     */
    protected $returnId = 0;

    /**
     * Set feed URL.
     *
     * @param string $url
     * @return ContextInterface
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get feed URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set store / website URL.
     *
     * @param string $website
     * @return ContextInterface
     */
    public function setWebsite($website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Get store / website URL.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set feed type.
     *
     * @param string $type
     * @return ContextInterface
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get feed type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set whether to return the created feed ID.
     *
     * @param int $returnId
     * @return ContextInterface
     */
    public function setReturnId($returnId)
    {
        $this->returnId = (int) $returnId;
        return $this;
    }

    /**
     * Get whether to return the created feed ID.
     *
     * @return int
     */
    public function getReturnId()
    {
        return $this->returnId;
    }
}

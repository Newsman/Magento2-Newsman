<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context;

use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Init unsubscribe email address data transfer context
 */
class InitUnsubscribeEmailContext extends UnsubscribeEmailContext
{
    /**
     * Additional options for initialization.
     *
     * @var array|null
     */
    protected $options;

    /**
     * Set initialization options.
     *
     * @param string $options
     * @return ContextInterface
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Retrieve initialization options.
     *
     * @return array|string
     */
    public function getOptions()
    {
        return $this->options;
    }
}

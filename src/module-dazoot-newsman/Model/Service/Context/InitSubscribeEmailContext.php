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
 * Init subscribe email address data transfer context
 */
class InitSubscribeEmailContext extends SubscribeEmailContext
{
    /**
     * @var array|null
     */
    protected $options;

    /**
     * @param string $options
     * @return ContextInterface
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getOptions()
    {
        return $this->options;
    }
}

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
 * Subscribe email address data transfer context
 */
class SubscribeEmailContext extends UnsubscribeEmailContext
{
    /**
     * @var string
     */
    protected $firstname;

    /**
     * @var string
     */
    protected $lastname;

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @param string $firstname
     * @return ContextInterface
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstsname()
    {
        if (empty($this->firstname)) {
            return ContextInterface::NULL_VALUE;
        }
        return $this->firstname;
    }

    /**
     * @param string $lastname
     * @return ContextInterface
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastsname()
    {
        if (empty($this->lastname)) {
            return ContextInterface::NULL_VALUE;
        }
        return $this->lastname;
    }

    /**
     * @param array $properties
     * @return ContextInterface
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}

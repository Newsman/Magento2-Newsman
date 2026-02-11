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
     * Customer first name.
     *
     * @var string
     */
    protected $firstname;

    /**
     * Customer last name.
     *
     * @var string
     */
    protected $lastname;

    /**
     * Additional Newsman subscriber properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Set customer first name.
     *
     * @param string $firstname
     * @return ContextInterface
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get customer first name or null value placeholder.
     *
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
     * Set customer last name.
     *
     * @param string $lastname
     * @return ContextInterface
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get customer last name or null value placeholder.
     *
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
     * Set Newsman subscriber properties.
     *
     * @param array $properties
     * @return ContextInterface
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Get Newsman subscriber properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}

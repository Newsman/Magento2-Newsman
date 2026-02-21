<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Sms;

use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Data transfer context for sms.saveSubscribe — subscribes a telephone number to an SMS list.
 */
class SubscribeContext extends UnsubscribeContext
{
    /**
     * Customer first name.
     *
     * @var string
     */
    protected $firstname = '';

    /**
     * Customer last name.
     *
     * @var string
     */
    protected $lastname = '';

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
     * Get customer first name.
     *
     * @return string
     */
    public function getFirstsname()
    {
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
     * Get customer last name.
     *
     * @return string
     */
    public function getLastsname()
    {
        return $this->lastname;
    }

    /**
     * Set Newsman subscriber properties.
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
     * Get Newsman subscriber properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}

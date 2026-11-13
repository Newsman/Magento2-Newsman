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
 * Anonymise email address data transfer context
 */
class AnonymiseEmailContext extends StoreContext
{
    /**
     * Email address.
     *
     * @var string
     */
    protected $email;

    /**
     * Set email address.
     *
     * @param string $email
     * @return ContextInterface
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Retrieve email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}

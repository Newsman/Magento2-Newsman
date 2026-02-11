<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Configuration;

use Dazoot\Newsman\Model\Service\Context\AbstractContext;
use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * User data transfer context
 */
class UserContext extends AbstractContext
{
    /**
     * Newsman user ID.
     *
     * @var string|int
     */
    protected $userId;

    /**
     * Newsman API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Set the Newsman user ID.
     *
     * @param string|int $userId
     * @return ContextInterface
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Retrieve the Newsman user ID.
     *
     * @return int|string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the Newsman API key.
     *
     * @param string $apiKey
     * @return ContextInterface
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Retrieve the Newsman API key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}

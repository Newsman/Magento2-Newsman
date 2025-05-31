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
     * @var string|int
     */
    protected $userId;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @param string|int $userId
     * @return ContextInterface
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $apiKey
     * @return ContextInterface
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}

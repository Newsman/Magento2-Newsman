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
 * List data transfer context
 */
class ListContext extends UserContext
{
    /**
     * Newsman list ID.
     *
     * @var int
     */
    protected $listId;

    /**
     * Set the Newsman list ID.
     *
     * @param int $listId
     * @return ContextInterface
     */
    public function setListId($listId)
    {
        $this->listId = $listId;
        return $this;
    }

    /**
     * Retrieve the Newsman list ID.
     *
     * @return int
     */
    public function getListId()
    {
        return $this->listId;
    }
}

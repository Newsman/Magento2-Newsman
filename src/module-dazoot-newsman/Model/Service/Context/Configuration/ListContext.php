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
     * List ID value that requests segments for every list in the account.
     */
    public const LIST_ID_ALL = 'all';

    /**
     * Newsman list ID: a single list ID, the string 'all', or an array of list IDs.
     *
     * @var int|string|int[]
     */
    protected $listId;

    /**
     * Set the Newsman list ID.
     *
     * @param int|string|int[] $listId Single list ID, 'all', or an array of list IDs
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
     * @return int|string|int[]
     */
    public function getListId()
    {
        return $this->listId;
    }
}

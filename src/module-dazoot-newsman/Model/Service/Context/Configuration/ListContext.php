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
     * @var int
     */
    protected $listId;

    /**
     * @param int $listId
     * @return ContextInterface
     */
    public function setListId($listId)
    {
        $this->listId = $listId;
        return $this;
    }

    /**
     * @return int
     */
    public function getListId()
    {
        return $this->listId;
    }
}

<?php

namespace Dazoot\Newsmansmtp\Model;

/**
 * Class Store
 * @package Dazoot\Newsmansmtp\Model
 */
class Store
{
    /** @var int/null  */
    protected $store_id = null;

    /**
     * @var null
     */
    protected $from = null;

    /**
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * @param $store_id
     * @return $this
     */
    public function setStoreId($store_id)
    {
        $this->store_id = $store_id;
        return $this;
    }

    /**
     * @return string|array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string|array $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }
}

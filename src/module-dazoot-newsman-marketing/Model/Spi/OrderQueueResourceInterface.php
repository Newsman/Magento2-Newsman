<?php

namespace Dazoot\Newsmanmarketing\Model\Spi;

use Magento\Framework\Model\AbstractModel;

/**
 * Interface OrderQueueResourceInterface
 */
interface OrderQueueResourceInterface
{
    /**
     * @param AbstractModel $object
     * @return $this
     */
    public function save(AbstractModel $object);

    /**
     * @param AbstractModel $object
     * @param mixed $value
     * @param string|null $field field to load by (defaults to model id)
     * @return mixed
     */
    public function load(AbstractModel $object, $value, $field = null);

    /**
     * @param AbstractModel $object
     * @return mixed
     */
    public function delete(AbstractModel $object);
}

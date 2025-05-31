<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class Export Retriever Factory
 */
class RetrieverFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $className
     * @param array $data
     * @return RetrieverInterface
     * @throws \InvalidArgumentException
     */
    public function create($className, array $data = [])
    {
        $model = $this->objectManager->create($className, $data);

        if (!$model instanceof RetrieverInterface) {
            throw new \InvalidArgumentException(
                'Type "' . $className . '" is not instance on ' . RetrieverInterface::class
            );
        }

        return $model;
    }
}

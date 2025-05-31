<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class retriever pool
 */
class Pool
{
    /**
     * @var array
     */
    protected $retrieverList = [];

    /**
     * @var array
     */
    protected $retrieverInstances = [];

    /**
     * @var RetrieverFactory
     */
    protected $factory;

    /**
     * @param RetrieverFactory $factory
     * @param array $retrieverList
     */
    public function __construct(
        RetrieverFactory $factory,
        array $retrieverList = []
    ) {
        $this->factory = $factory;
        $this->retrieverList = $retrieverList;
    }

    /**
     * Get retriever list
     *
     * @return array
     */
    public function getRetrieverList()
    {
        return $this->retrieverList;
    }

    /**
     * Retrieve retriever by code instantiated
     *
     * @param string $code
     * @return RetrieverInterface
     *
     * phpcs:disable Magento2.Classes.DiscouragedDependencies
     */
    public function getRetrieverByCode($code)
    {
        if (isset($this->retrieverInstances[$code])) {
            return $this->retrieverInstances[$code];
        }

        foreach ($this->retrieverList as $retriever) {
            if ($retriever['code'] == $code) {
                if (empty($retriever['class'])) {
                    throw new \InvalidArgumentException('The parameter "class" is missing.');
                }

                $this->retrieverInstances[$code] = $this->factory->create($retriever['class']);
                break;
            }
        }

        if (!isset($this->retrieverInstances[$code])) {
            throw new \InvalidArgumentException('The parameter "code" is missing.');
        }

        return $this->retrieverInstances[$code];
    }
}

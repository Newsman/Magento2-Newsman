<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Marketing view model
 */
class Marketing implements ArgumentInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Registry $registry
     * @param SerializerInterface $serializer
     * @param Escaper $escaper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        Registry $registry,
        SerializerInterface $serializer,
        Escaper $escaper
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->registry = $registry;
        $this->serializer = $serializer;
        $this->escaper = $escaper;
    }

    /**
     * Check if Newsman Marketing is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->config->isActive();
    }

    /**
     * Escape value for JavaScript output.
     *
     * @param string|int $value
     * @return string|int
     */
    public function escapeValue($value)
    {
        return $this->escaper->escapeJsQuote($this->escaper->escapeHtml($value));
    }

    /**
     * Retrieve the serializer instance.
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Dazoot\Newsman\Model\Api\ClientInterfaceFactory;
use Dazoot\Newsman\Model\Api\ContextInterfaceFactory;
use Dazoot\Newsman\Model\Validator\EmailAddress as EmailAddressValidator;
use Magento\Store\Api\Data\StoreInterface;
use Dazoot\Newsman\Model\Config;
use Magento\Store\Model\Store;
use Dazoot\Newsman\Logger\Logger;

/**
 * API endpoints abstract service class
 */
class AbstractService implements ServiceInterface
{
    /**
     * @var ContextInterfaceFactory
     */
    protected $contextFactory;

    /**
     * @var ClientInterfaceFactory
     */
    protected $clientFactory;

    /**
     * @var EmailAddressValidator
     */
    protected $emailAddressValidator;

    /**
     * @var Store|StoreInterface|null
     */
    protected $store;

    /**
     * @var Logger
     */
    protected $logger

    ;/**
     * @var Config
     */
    protected $config;

    /**
     * @param ContextInterfaceFactory $contextFactory
     * @param ClientInterfaceFactory $clientFactory
     * @param EmailAddressValidator $emailAddressValidator
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        ContextInterfaceFactory $contextFactory,
        ClientInterfaceFactory $clientFactory,
        EmailAddressValidator $emailAddressValidator,
        Logger $logger,
        Config $config
    ) {
        $this->contextFactory = $contextFactory;
        $this->clientFactory = $clientFactory;
        $this->emailAddressValidator = $emailAddressValidator;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function createApiContext()
    {
        return $this->contextFactory->create()
            ->setStore($this->getStore());
    }

    /**
     * @inheritdoc
     */
    public function createApiClient()
    {
        return $this->clientFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function execute($context)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStore()
    {
        return $this->store;
    }
}

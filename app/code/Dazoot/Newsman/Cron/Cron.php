<?php

namespace Dazoot\Newsman\Cron;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Dazoot\Newsman\Helper\Apiclient;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Cron extends \Magento\Backend\App\Action
{
    /**
     * @var \Dazoot\Newsman\Model\Processor
     */
    protected $processor;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Dazoot\Newsman\Helper\Manager
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Dazoot\Newsman\Model\Processor $processor
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Dazoot\Newsman\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Dazoot\Newsman\Model\Processor $processor,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Dazoot\Newsman\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->processor = $processor;
        $this->storeRepository = $storeRepository;
        $this->appEmulation = $appEmulation;
        $this->helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param \Magento\Cron\Model\Schedule $schedule
     *
     * @return void
     */
    public function execute(\Magento\Cron\Model\Schedule $schedule)
    {
        $this->_logger->info("Start NewsMAN cron");

        try {
            foreach ($this->storeRepository->getList() as $store) {
                if ($this->helper->isEnabled($store->getId())) {
                    $this->_logger->info("Start NewsMAN sync cron in store " . $store->getName());

                    $this->processor->init($store->getId())->process();
                }
            }

            $this->helper->clearOldLog();
        } catch (\Exception $e) {
            $this->_logger->info("Error to sync NewsMAN cron");
        }
    }
}
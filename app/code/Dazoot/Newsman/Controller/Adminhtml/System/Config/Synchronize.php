<?php

namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Dazoot\Newsman\Helper\Apiclient;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Synchronize extends \Magento\Backend\App\Action
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	protected $configWriter;

	protected $client;
	protected $subscriberCollectionFactory;
	protected $_subscriberCollectionFactory;
	protected $jsonHelper;

	/**
	 * @param \Magento\Backend\App\Action\Context $context
	 * @param \Psr\Log\LoggerInterface $logger
	 */
	public function __construct(
		Context $context,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $_subscriberCollectionFactory,
		WriterInterface $configWriter,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $__subscriberCollectionFactory
	)
	{
		$this->_logger = $logger;
		$this->client = new Apiclient();
		$this->subscriberCollectionFactory = $_subscriberCollectionFactory;
		$this->configWriter = $configWriter;
		$this->jsonHelper = $jsonHelper;
		$this->_subscriberCollectionFactory = $__subscriberCollectionFactory;
		parent::__construct($context);
	}

	/**
	 * Synchronize
	 *
	 * @return void
	 */
	public function execute()
	{
		$arr = array();
		$email = array();
		$firstname = array();

		$_email = array();

		$customers = $this->subscriberCollectionFactory->create();

		$subscribers = $this->_subscriberCollectionFactory->create()
			->addFilter('subscriber_status', ['eq' => 1]);

		foreach ($customers as $item)
		{
			$email[] = $item["email"];
			$firstname[] = $item["firstname"];
		}

		foreach ($subscribers as $item)
		{
			$_email[] = $item["subscriber_email"];
		}

		$max = 9999;

		$csv = "email, firstname" . PHP_EOL;
		for ($int = 0; $int < count($email); $int++)
		{
			$csv .= $email[$int];
			$csv .= ", ";
			$csv .= $firstname[$int];
			$csv .= PHP_EOL;

			if ($int == 9999)
			{
				$int = 0;

				$list = $this->client->getSelectedList();
				$ret = $this->client->importCSV($list, $csv);
			}
		}

		$list = $this->client->getSelectedList();
		$ret = $this->client->importCSV($list, $csv);

		$csv = "";
		$csv = "email" . PHP_EOL;
		for ($int = 0; $int < count($_email); $int++)
		{
			$csv .= $_email[$int];
			$csv .= PHP_EOL;

			if ($int == 9999)
			{
				$int = 0;

				$list = $this->client->getSelectedList();
				$ret = $this->client->importCSV($list, $csv);
			}
		}

		$list = $this->client->getSelectedList();
		$ret = $this->client->importCSV($list, $csv);
	}
}
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
		//customers import

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		
		
		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$storeId = (int) $this->getRequest()->getParam('storeid', 0);		
		
		$this->client->setCredentials($storeId);

		$batchSize = 9000;

		$list = $this->client->getSelectedList($storeId);
		$segment = $this->client->getSelectedSegment($storeId);

		if($segment == 0)
			$segment = array();
		
		$customers = $this->subscriberCollectionFactory->create()
		->addFilter('is_active', ['eq' => 1])
		->addFieldToFilter("website_id", $storeId);

		$customers_to_import = array();

		foreach ($customers as $item)
		{
			/*$date = strtotime($item["updated_at"]);
			$age = time() - $date;

			if ($age > 172800)
			{
				continue;
			}*/

			$customers_to_import[] = array(
				"email" => $item["email"],
				"firstname" => $item["firstname"],
				"date" => $item["updated_at"]
			);

			if ((count($customers_to_import) % $batchSize) == 0)
			{
				$this->importDataCustomers($customers_to_import, $list, array($segment));
			}
		}

		if (count($customers_to_import) > 0)
		{
			$this->importDataCustomers($customers_to_import, $list, array($segment));
		}

		unset($customers_to_import);

		//subscribers import

		$arr = array();
		$email = array();
		$firstname = array();

		$_email = array();

		$subscribers = $this->_subscriberCollectionFactory->create()
			->addFilter('subscriber_status', ['eq' => 1])
			->addFieldToFilter("store_id", $storeId);

		$customers_to_import = array();		

		foreach ($subscribers as $item)
		{
			$customers_to_import[] = array(
				"email" => $item["subscriber_email"]
			);

			if ((count($customers_to_import) % $batchSize) == 0)
			{
				$this->_importData($customers_to_import, $list, array($segment));
			}
		}

		if (count($customers_to_import) > 0)
		{
			$this->_importData($customers_to_import, $list, array($segment));
		}

		unset($customers_to_import);
	}

	protected function _importData(&$data, $list, $segments = null)
	{
		$csv = '"email","source"' . PHP_EOL;

		$source = self::safeForCsv("magento 2 newsman plugin - list&segment subscriber manual sync");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s",
				self::safeForCsv($_dat["email"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $this->client->importCSV($list, $segments, $csv);
			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{
			$this->_logger->debug('Cron failed Newsman_Import class');
		}

		$data = array();
	}

	protected function importDataCustomers(&$data, $list, $segments = null)
	{
		$csv = '"email","fullname","source"' . PHP_EOL;

		$source = self::safeForCsv("magento 2 newsman plugin - list&segment customer manual sync");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s,%s",
				self::safeForCsv($_dat["email"]),
				self::safeForCsv($_dat["firstname"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			if (is_array($segments) && count($segments) > 0)
			{
				$ret = $this->client->importCSVinSegment($list, $segments, $csv);
			} else
			{
				$ret = $this->client->importCSV($list, $csv);
			}

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{
			$this->_logger->debug('Cron failed Newsman_Import class');
		}

		$data = array();
	}

	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}
}
<?php

namespace Dazoot\Newsman\Cron;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Dazoot\Newsman\Helper\Apiclient;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Cron extends \Magento\Backend\App\Action
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	const XML_DATA_MAPPING = 'newsman/data/mapping';
	const XML_CRON_RUN = "newsman/data/cron_run";

	protected $client;
	//Customers
	protected $subscriberCollectionFactory;
	//Subscribers
	protected $_subscriberCollectionFactory;
	protected $jsonHelper;
	protected $cronRun;
	protected $configWriter;
	protected $timezone;

	protected $customerGroup;

	/**
	 * @param \Magento\Backend\App\Action\Context $context
	 * @param \Psr\Log\LoggerInterface $logger
	 */
	public function __construct(
		Context $context,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $_subscriberCollectionFactory,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
		\Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $__subscriberCollectionFactory,
		WriterInterface $configWriter,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
	)
	{
		$this->_logger = $logger;
		$this->client = new Apiclient();
		$this->subscriberCollectionFactory = $_subscriberCollectionFactory;
		$this->jsonHelper = $jsonHelper;
		$this->customerGroup = $customerGroup;
		$this->_subscriberCollectionFactory = $__subscriberCollectionFactory;
		$this->configWriter = $configWriter;
		$this->timezone = $timezone;
		parent::__construct($context);
	}

	public function execute()
	{		
		//customers import

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		
		
		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$storeId = 0;		
		
		$this->client->setCredentials($storeId);

		$batchSize = 9000;

		$list = $this->client->getSelectedList($storeId);
		$segment = $this->client->getSelectedSegment($storeId);

		if($segment == 0)
			$segment = array();

		$importType = $this->client->getImportType($storeId);
		if(empty($importType))
			$importType = 1;			
		
		$customers = $this->subscriberCollectionFactory->create()
		->addFilter('is_active', ['eq' => 1])
		->addFieldToFilter("website_id", $storeId);		

		if($importType == 2)
		{

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

		}

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
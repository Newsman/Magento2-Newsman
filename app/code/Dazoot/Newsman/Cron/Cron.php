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

	/**
	 * Synchronize
	 *
	 * @return void
	 */
	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}

	protected function _importData(&$data, $list, $segments = null)
	{
		$csv = '"email","fullname","source"' . PHP_EOL;

		$source = self::safeForCsv("magento 2 newsman plugin - segments customer, subscribers CRON");
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

	public function execute()
	{
		$customerGroups = $this->customerGroup->toOptionArray();

		$groupsCount = count($customerGroups);

		$dataMapping = [];

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$dataMapping = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_DATA_MAPPING);

		//Cron Schedule Time
		$this->cronRun = (!empty($objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_CRON_RUN))) ? $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_CRON_RUN) : null;

		$cronAllow = false;

		if (empty($this->cronRun))
		{
			$cronAllow = true;

			//Run first time
			$cronDate = $this->timezone->date();
			$cronDate = $cronDate->format("Y-m-d H:i:s");
			$this->configWriter->save(self::XML_CRON_RUN, $cronDate);
		} else
		{
			$date = strtotime($this->cronRun);
			$age = strtotime($this->timezone->date()->format("Y-m-d H:i:s")) - $date;

			if ($age >= 86400)
			{
				$cronAllow = true;

				//Run always
				$cronDate = $this->timezone->date();
				$cronDate = $cronDate->format("Y-m-d H:i:s");
				$this->configWriter->save(self::XML_CRON_RUN, $cronDate);
			}
		}
		//Cron Schedule Time

		if ($cronAllow)
		{
			$dataMapping = json_decode($dataMapping, true);

			$batchSize = 5000;
			$list = $this->client->getSelectedList();

			if ($dataMapping != null || $dataMapping != "")
			{
				$dataMappingCount = count($dataMapping);

				$intCount = 0;

				for ($gint = 1; $gint < $groupsCount; $gint++)
				{
					$customers = $this->subscriberCollectionFactory->create()->addFieldToFilter("group_id", $gint);

					$segment = $dataMapping[$intCount][$gint];

					$customers_to_import = array();

					foreach ($customers as $item)
					{
						$date = strtotime($item["updated_at"]);
						$age = time() - $date;

						if ($age > 172800)
						{
							continue;
						}

						$customers_to_import[] = array(
							"email" => $item["email"],
							"firstname" => $item["firstname"],
							"date" => $item["updated_at"]
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

					$intCount++;
				}
			}

			$arr = array();

			//Get only active subscriberss
			$subscribers = $this->_subscriberCollectionFactory->create()
				->addFilter('subscriber_status', ['eq' => 1]);

			$v = $this->MagentoVersionSubscriberFilter();

			$filterAfterDate = false;

			//Check Version
			$objectManager = null;
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
			$currentV = $productMetadata->getVersion();

			foreach ($v as $version)
			{
				if ($currentV == $version)
				{
					$filterAfterDate = true;
					break;
				}
			}

			$customers_to_import = array();

			foreach ($subscribers as $item)
			{
				if ($filterAfterDate)
				{
					$date = strtotime($item["change_status_at"]);
					$age = time() - $date;

					//2 days - 48 hours
					if ($age > 172800)
					{
						continue;
					}
				}

				$customers_to_import[] = array(
					"email" => $item["subscriber_email"],
					"firstname" => $item["firstname"],
					"date" => ""
				);

				if ((count($customers_to_import) % $batchSize) == 0)
				{
					$this->_importData($customers_to_import, $list);
				}
			}

			if (count($customers_to_import) > 0)
			{
				$this->_importData($customers_to_import, $list);
			}

			unset($customers_to_import);
		}

		$this->_logger->debug('Running Cron from Newsman_Import class');
	}

	public function MagentoVersionSubscriberFilter()
	{
		$versions = array(
			"2.2.3",
			"2.2.4",
			"2.2.5"
		);

		return $versions;
	}
}
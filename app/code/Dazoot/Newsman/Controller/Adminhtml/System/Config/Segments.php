<?php

namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Dazoot\Newsman\Helper\Apiclient;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Segments extends \Magento\Backend\App\Action
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	const XML_DATA_MAPPING = 'newsman/data/mapping';

	protected $configWriter;

	protected $client;
	protected $subscriberCollectionFactory;
	protected $_subscriberCollectionFactory;
	protected $jsonHelper;

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
		WriterInterface $configWriter,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
		\Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $__subscriberCollectionFactory
	)
	{
		$this->_logger = $logger;
		$this->client = new Apiclient();
		$this->subscriberCollectionFactory = $_subscriberCollectionFactory;
		$this->configWriter = $configWriter;
		$this->jsonHelper = $jsonHelper;
		$this->customerGroup = $customerGroup;
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
		$customerGroups = $this->customerGroup->toOptionArray();

		$groupsCount = count($customerGroups);

		$dataMapping = [];

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$storeId = (int) $this->getRequest()->getParam('website', 0);
		if($storeId == 0)
		{
			$storeId = (int) $this->getRequest()->getParam('store', 0);
		}	

		$batchSize = 5000;
		$list = $this->client->getSelectedList($storeId);
		
		$customers = $this->subscriberCollectionFactory->create()->addFieldToFilter("website_id", $storeId);

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
	}

	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}

	protected function _importData(&$data, $list, $segments = null)
	{
		$csv = '"email","fullname","source"' . PHP_EOL;

		$source = self::safeForCsv("magento 2 newsman plugin - segments customer manual sync");
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
}
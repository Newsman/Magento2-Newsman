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
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
		\Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $__subscriberCollectionFactory
	)
	{
		$this->_logger = $logger;
		$this->client = new Apiclient();
		$this->subscriberCollectionFactory = $_subscriberCollectionFactory;
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
		$max = 9999;

		$customerGroups = $this->customerGroup->toOptionArray();

		$groupsCount = count($customerGroups);

		$dataMapping = [];

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$dataMapping = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_DATA_MAPPING);


		$dataMapping = json_decode($dataMapping, true);

		if ($dataMapping != null || $dataMapping != "")
		{

			$dataMappingCount = count($dataMapping);

			$intCount = 0;

			for ($gint = 1; $gint < $groupsCount; $gint++)
			{
				$customers = $this->subscriberCollectionFactory->create()->addFieldToFilter("group_id", $gint);

				$segment = $dataMapping[$intCount][$gint];

				$email = array();
				$firstname = array();

				foreach ($customers as $item)
				{
					$email[] = $item["email"];
					$firstname[] = $item["firstname"];
				}

				$csv = 'email,firstname,source' . PHP_EOL;
				for ($sint = 0; $sint < count($email); $sint++)
				{
					$firstname[$sint] = str_replace(array('"', ","), "", $firstname[$sint]);
					$csv .= $email[$sint];
					$csv .= ",";
					$csv .= $firstname[$sint];
					$csv .= ",";
					$csv .= "magento 2 newsman plugin";
					$csv .= PHP_EOL;

					if ($sint == $max)
					{
						$max += 9999;

						$list = $this->client->getSelectedList();
						$ret = $this->client->importCSVinSegment($list, array($segment), $csv);

						$csv = "";
					}
				}

				$list = $this->client->getSelectedList();
				$ret = $this->client->importCSVinSegment($list, array($segment), $csv);

				$intCount++;
			}
		}

		$arr = array();

		//Get only active subscriberss

		$_email = array();

		$subscribers = $this->_subscriberCollectionFactory->create()
			->addFilter('subscriber_status', ['eq' => 1]);


		foreach ($subscribers as $item)
		{
			$_email[] = $item["subscriber_email"];
		}

		$max = 9999;

		$csv = "";
		$csv = "email,source" . PHP_EOL;
		for ($int = 0; $int < count($_email); $int++)
		{
			$csv .= $_email[$int];
			$csv .= ",";
			$csv .= "magento 2 newsman plugin";
			$csv .= PHP_EOL;

			if ($int == $max)
			{
				$max += 9999;

				$list = $this->client->getSelectedList();
				$ret = $this->client->importCSV($list, $csv);
			}
		}

		$list = $this->client->getSelectedList();
		$ret = $this->client->importCSV($list, $csv);


		$this->_logger->debug('Running Cron from Newsman_Import class');
	}
}
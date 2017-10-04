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

		for ($int = 1; $int < $groupsCount; $int++)
		{
			$val = $customerGroups[$int]["value"];
			$dataMapping[][$val] = $this->getRequest()->getPost($val);
		}

		$_dataMapping = $dataMapping;
		$dataMapping = json_encode($dataMapping);

		$this->configWriter->save('newsman/data/mapping', $dataMapping);

		$arr = array();

		/*
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$dataMapping = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_DATA_MAPPING);


		$dataMapping = json_decode($dataMapping, true);
		$dataMappingCount = count($dataMapping);
		*/

		$_dataMappingCount = count($_dataMapping);

		$intCount = 0;

		for ($gint = 1; $gint < $groupsCount; $gint++)
		{
			//subscribers
			$_email = array();
			$subscribers = $this->_subscriberCollectionFactory->create()
				->addFilter('subscriber_status', ['eq' => 1]);

			foreach ($subscribers as $item)
			{
				$_email[] = $item["subscriber_email"];
			}


			$customers = $this->subscriberCollectionFactory->create()->addFieldToFilter("group_id", $gint);

			$segment = $_dataMapping[$intCount][$gint];

			$email = array();
			$firstname = array();

			foreach ($customers as $item)
			{
				$email[] = $item["email"];
				$firstname[] = $item["firstname"];
			}

			$csv = 'email,firstname' . PHP_EOL;
			for ($sint = 0; $sint < count($email); $sint++)
			{
				$firstname[$sint] = str_replace(array('"', ","), "", $firstname[$sint]);
				$csv .= $email[$sint];
				$csv .= ",";
				$csv .= $firstname[$sint];
				$csv .= PHP_EOL;

				if ($sint == 9999)
				{
					$sint = 0;

					$list = $this->client->getSelectedList();
					$ret = $this->client->importCSVinSegment($list, array($segment), $csv);
				}
			}

			$list = $this->client->getSelectedList();
			$ret = $this->client->importCSVinSegment($list, array($segment), $csv);

			//Subscriber add

			$csv = 'email' . PHP_EOL;
			for ($sint = 0; $sint < count($_email); $sint++)
			{
				$csv .= $_email[$sint];
				$csv .= PHP_EOL;

				if ($sint == 9999)
				{
					$sint = 0;

					$list = $this->client->getSelectedList();
					$ret = $this->client->importCSVinSegment($list, array($segment), $csv);
				}
			}

			$list = $this->client->getSelectedList();
			$ret = $this->client->importCSVinSegment($list, array($segment), $csv);

			$intCount++;
		}
	}
}
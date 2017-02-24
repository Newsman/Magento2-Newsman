<?php

namespace Dazoot\Newsman\Helper;

use Dazoot\Newsman\Helper\Newsman\Client;
use Dazoot\Newsman\Helper\Customers;

class Apiclient extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $client;

	public $userId, $apiKey, $listId;

	protected $scopeConfig;

	const XML_PATH_USER_RECIPIENT = 'newsman/credentials/userId';
	const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';
	const XML_PATH_LIST_RECIPIENT = 'newsman/credentials/listId';

	public function __construct()
	{
		$this->initializeClient();
	}

	public function initializeClient()
	{
		try
		{
			$this->getCredentials();

			$this->client = new Client($this->userId, $this->apiKey);
			$this->client->setCallType('rest');
			$this->client->setTransport('curl');
		} catch (\Exception $e)
		{

		}
	}

	public function getLists()
	{
		$_lists = null;

		$_lists = $this->client->list->all();

		return $_lists;
	}

	public function importCSV($list, $csv)
	{
		return $this->client->import->csv($list, array(), $csv);
	}
	
	public function importCSVinSegment($list, $segments, $csv)
	{
		return $this->client->import->csv($list, $segments, $csv);
	}

	public function getSegmentsByList()
	{
		$listId = $this->getSelectedList();
		$segments = $this->client->segment->all($listId);

		return $segments;
	}

	public function getSelectedList()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		return $this->listId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_LIST_RECIPIENT);
	}

	public function getCredentials()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$this->userId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_USER_RECIPIENT);
		$this->apiKey = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_API_RECIPIENT);
		$this->listId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_LIST_RECIPIENT);
	}
}

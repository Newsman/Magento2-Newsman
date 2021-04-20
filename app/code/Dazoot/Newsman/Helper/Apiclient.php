<?php

namespace Dazoot\Newsman\Helper;

use Dazoot\Newsman\Helper\Customers;

class Apiclient extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $client;

	public $userId, $apiKey, $listId, $importType;

	protected $ip;
	protected $scopeConfig, $storeId;

	const XML_PATH_USER_RECIPIENT = 'newsman/credentials/userId';
	const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';
	const XML_PATH_LIST_RECIPIENT = 'newsman/credentials/listId';
	const XML_PATH_SEGMENT_RECIPIENT = 'newsman/credentials/segmentId';
	const XML_PATH_IMPORTTYPE_RECIPIENT = 'newsman/credentials/importType';

	public function __construct()
	{
		$this->initializeClient();
	}

	public function initializeClient()
	{
		try
		{			
		
		} catch (\Exception $e)
		{

		}
	}

	public function setCredentials($storeId){
		$this->storeId = $storeId;

		$this->getCredentials($this->storeId);
	
		try{
			$this->client = new Newsman_Client($this->userId, $this->apiKey);
			$this->client->setCallType('rest');
			$this->client->setTransport('curl');
		}
		catch(\Exception $e){

		}
	}

	public function getLists()
	{
		$_lists = null;

		$_lists = $this->client->list->all();

		return $_lists;
	}

	public function importCSV($list, $segments, $csv)
	{
		return $this->client->import->csv($list, $segments, $csv);
	}
	
	public function importCSVinSegment($list, $segments, $csv)
	{
		return $this->client->import->csv($list, $segments, $csv);
	}

	public function unsubscribe($email){
		$listId = $this->getSelectedList();
		return $this->client->subscriber->saveUnsubscribe($listId, $email, $this->ip);
	}

	public function getSegmentsByList($storeId)
	{
		$listId = $this->getSelectedList($storeId);		

		$segments = array();

		try{
		  $segments = $this->client->segment->all($listId);
		}
		catch(\Exception $e)
		{

		}

		return $segments;
	}

	public function getSelectedList($storeId)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		

		return $this->listId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_LIST_RECIPIENT, $storeScope, $storeId);
	}

	public function getImportType($storeId)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		

		return $this->importType = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_IMPORTTYPE_RECIPIENT, $storeScope, $storeId);
	}

	public function getSelectedSegment($storeId)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		

		return $this->listId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_SEGMENT_RECIPIENT, $storeScope, $storeId);
	}

	public function getCredentials($storeId)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$this->userId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_USER_RECIPIENT, $storeScope, $storeId);
		$this->apiKey = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_API_RECIPIENT, $storeScope, $storeId);
		$this->listId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_LIST_RECIPIENT, $storeScope, $storeId);

		if(!empty($_SERVER['HTTP_CLIENT_IP'])){
			//ip from share internet
			$this->ip = $_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			//ip pass from proxy
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}elseif(!empty($_SERVER['REMOTE_ADDR']))
		{
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}
		else{
			$this->ip = "127.0.0.1";
		}	
	}
}

class Newsman_Client
{
	/**
	 * The API URL
	 * @var string
	 */
	protected $api_url = "https://ssl.newsman.app/api";

	/**
	 * The user ID
	 * @var string
	 */
	protected $user_id;

	/**
	 * The API key
	 * @var string
	 */
	protected $api_key;

	/**
	 * The API version: only 1.2 for now
	 * @var string
	 */
	protected $api_version = "1.2";

	/**
	 * Output format: json or ser (php serialize)
	 * @var string
	 */
	protected $output_format = "json";

	/**
	 * The method namespace
	 * @var string
	 */
	protected $method_namespace = null;

	/**
	 * The method name
	 * @var string
	 */
	protected $method_name = null;

	/**
	 * Newsman V2 REST API - Client
	 * @param $user_id string
	 * @param $api_key string
	 */
	public function __construct($user_id, $api_key)
	{
		$this->user_id = $user_id;
		$this->api_key = $api_key;

		$this->_initCurl();
	}

	/**
	 * Initialize curl
	 */
	protected function _initCurl()
	{
		if (function_exists("curl_init") && function_exists("curl_exec"))
		{
		} else
		{
			throw new \Exception("No extensions found for the Newsman Api Client. Requires CURL extension for REST calls.");
		}
	}

	/**
	 * Deprecated
	 * @param string $transport
	 */
	public function setTransport($transport)
	{

	}

	/**
	 * Deprecated
	 * @param string $call_type
	 */
	public function setCallType($call_type)
	{

	}

	/**
	 * Updates the API URL - no trailing slash please
	 * @param string $api_url
	 */
	public function setApiUrl($api_url)
	{
		$url = parse_url($api_url);

		if ($url['scheme'] != 'https')
		{
			throw new \Exception("Protocol must be https");
		}

		$this->api_url = $api_url;
	}

	/**
	 * Updates the API version
	 * @param string $api_version
	 */
	public function setApiVersion($api_version)
	{
		$this->api_version = $api_version;
	}

	/**Deprecated
	 * Set the output format: json and ser (php serialize) accepted
	 * @param string $output_format
	 */
	public function setOutputFormat($output_format)
	{

	}

	public function __get($name)
	{
		$this->method_namespace = $name;
		return $this;
	}

	/**
	 * Set the namespace
	 * @param string $output_format
	 */
	public function setNamespace($namespace)
	{
		$this->method_namespace = $namespace;
	}

	public function __call($name, $params)
	{
		if (is_null($this->method_namespace))
		{
			throw new \Exception("No namespace defined");
		}

		$this->method_name = $name;
		
		$v_params = array();
		for ($i = 0; $i < count($params); $i++)
		{
			$k = "__" . $i . "__";
			$v_params[$k] = $params[$i];
		}
		
		$ret = $this->sendRequestRest($this->method_namespace . "." . $name, $v_params);

		// reset
		$this->method_namespace = null;
		return $ret;
	}
	
	public function sendRequestRest($api_method, $params)
	{
		$api_method_url = sprintf("%s/%s/rest/%s/%s/%s.%s", $this->api_url, $this->api_version, $this->user_id, $this->api_key, $api_method, $this->output_format);
		
		$ret = $this->_post_curl($api_method_url, $params);

		$ret = json_decode($ret, true);

		return $ret;
	}

	protected function _post_curl($url, $params)
	{
		$cu = curl_init();
		curl_setopt($cu, CURLOPT_URL, $url);
		curl_setopt($cu, CURLOPT_POST, true);
		curl_setopt($cu, CURLOPT_PORT, 443);

		//curl_setopt($cu, CURLOPT_POSTFIELDS, $params);
		curl_setopt($cu, CURLOPT_CUSTOMREQUEST, "POST");  	
		curl_setopt($cu, CURLOPT_POST, true);
		curl_setopt($cu, CURLOPT_POSTFIELDS, json_encode($params));
		curl_setopt($cu, CURLOPT_HTTPHEADER, array('application/json')); 		
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, true);
		
		$ret = curl_exec($cu);				
		
		$http_status = curl_getinfo($cu, CURLINFO_HTTP_CODE);
		if ($http_status != 200)
		{
			$_error = @json_decode($ret, true);

			if (is_array($_error) && array_key_exists("err", $_error) && array_key_exists("message", $_error) && array_key_exists("code", $_error))
			{
				throw new \Exception(
					$_error["message"], $_error["code"]
				);
			} else
			{
				throw new \Exception(
					(string)curl_error($cu), (string)$http_status
				);
			}
		}

		return $ret;
	}
}

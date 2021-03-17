<?php

namespace Dazoot\Newsman\Model\Config\Source;

use Dazoot\Newsman\Helper\Apiclient;

class Segments implements \Magento\Framework\Option\ArrayInterface
{
	protected $client;
	protected $request;

	public function __construct(
		\Magento\Framework\App\Request\Http $request
	)
	{
		$this->request = $request;
		$this->client = new Apiclient();
	}

	public function toOptionArray()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$storeId = (int) $this->request->getParam('website', 0);
		if($storeId == 0)
		{
			$storeId = (int) $this->request->getParam('store', 0);
		}		

		$this->client->setCredentials($storeId);

		$_lists = $this->client->getSegmentsByList($storeId);

		$arrayList = [];

		if (!empty($_lists) && is_array($_lists))
		{
			for ($int = 0; $int < count($_lists); $int++)
			{
				if($int == 0)				
					$arrayList[] = ['value' => '', 'label' => '(Optional) No segment'];								

				$arrayList[$int] = ['value' => $_lists[$int]["segment_id"], 'label' => $_lists[$int]["segment_name"]];
			}
		}
		else{
			$arrayList[0] = ['value' => '0', 'label' => 'Select a list'];
		}

		return $arrayList;
	}
}
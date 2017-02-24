<?php

namespace Dazoot\Newsman\Model\Config\Source;

use Dazoot\Newsman\Helper\Apiclient;

class Lists implements \Magento\Framework\Option\ArrayInterface
{
	protected $client;

	public function __construct(
	)
	{
		$this->client = new Apiclient();
	}

	public function toOptionArray()
	{
		$_lists = $this->client->getLists();

		$arrayList = [];

		for ($int = 0; $int < count($_lists); $int++)
		{
			$arrayList[$int] = ['value' => $_lists[$int]["list_id"], 'label' => $_lists[$int]["list_name"]];
		}

		return $arrayList;
	}
}
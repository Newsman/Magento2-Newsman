<?php

namespace Dazoot\Newsman\Model\Config\Source;

use Dazoot\Newsman\Helper\Apiclient;

class ImportType implements \Magento\Framework\Option\ArrayInterface
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
		$importType = [];
		$importType[] = array(
			"value" => "1",
			"label" => "Import only newsletter subscribers active"
		);
		$importType[] = array(
			"value" => "2", 
			"label" => "Import customers and newsletter subscribers active"
		);

		return $importType;
	}
}
<?php

namespace Dazoot\Newsman\Controller\Index;

use \DateTime;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Dazoot\Newsman\Helper\Apiclient;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';

    private $logger;
    private $_orderCollectionFactory;
    private $_customerCollectionFactory;
    private $_subscriberCollectionFactory;
    private $_productsCollectionFactory;
    private $_subscriber;
    private $_cartSession;
    protected $client;
	protected $subscriberCollectionFactory;

    public function __construct(   
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsCollectionFactory,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Magento\Checkout\Model\Session\Proxy $cartSession
    )
    {
        parent::__construct($context);

		$this->client = new Apiclient();
        $this->logger = $logger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_productsCollectionFactory = $productsCollectionFactory;
        $this->_subscriber= $subscriber;
        $this->_cartSession = $cartSession;
        $this->subscriberCollectionFactory = $customerCollectionFactory;

        // CsrfAwareAction Magento -> 2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    public function execute()
    {  
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $apiKey = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_API_RECIPIENT);

        if(isset($_POST["newsman_events"]))
            $this->webhookEvents($_POST);
        else                    
            $this->NewsmanFetch($apiKey);               
    }

    public function webhookEvents($post)
    {
        $newsman_events = json_decode($_POST["newsman_events"], true);

        foreach($newsman_events as $event)
        {            
            if($event['type'] == "unsub")
            {                                                            
                $email = $event["data"]["email"];     
                $subscriber = null;
        
                $subscribers = $this->_subscriberCollectionFactory->create()
                ->addFilter('subscriber_email', ['eq' => $email]);
        
                foreach($subscribers as $sub)
                {
                    $subscriber = $sub;
        
                    $col = $sub->getData();
                    $email = $col["subscriber_email"];              
                }       
        
                if (!empty($subscriber) && $subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
                ) {                             
                    $subscriber->unsubscribe();           
                }
            }
            elseif($event["type"] == "subscribe_confirm")
            {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $subscriberFactory = $objectManager->get('\Magento\Newsletter\Model\SubscriberFactory');
                $subscriberFactory->create()->subscribe($event["data"]["email"]);    
            }
        }
    }

    public function NewsmanFetch($_apikey)
    {
        $apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 1;
        $limit = (empty($_GET["limit"])) ? 1000 : $_GET["limit"];        
        $order_id = (empty($_GET["order_id"])) ? "" : $_GET["order_id"];
        $product_id = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];

        if (!empty($newsman) && !empty($apikey) || strpos($newsman, 'getCart.json') !== false) {
            $apikey = $_GET["apikey"] ?? "";
            $currApiKey = $_apikey;

            if(strpos($_GET["newsman"], 'getCart.json') !== false)
            {
                $cart = $this->_cartSession->getQuote()->getAllVisibleItems();
            
                $prod = array();

                foreach ( $cart as $cart_item_key => $cart_item ) {                   

                        $prod[] = array(
                            "id" => $cart_item->getProductId(),
                            "name" => $cart_item->getName(),
                            "price" => $cart_item->getPrice(),						
                            "quantity" => $cart_item->getQty()
                        );							
                                            
                    }									 						

                    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                    header("Cache-Control: post-check=0, pre-check=0", false);
                    header("Pragma: no-cache");
                    header('Content-Type:application/json');
                    echo json_encode($prod, JSON_PRETTY_PRINT);
                    exit;
            }
            else{
                if ($apikey != $currApiKey) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(403, JSON_PRETTY_PRINT);
                    return;
                }
            }

            switch ($_GET["newsman"]) {

                case "orders.json":

                    $orders = null;

                    $ordersObj = array();

                    if(empty($order_id))
                    {                    
                        $orders = $this->_orderCollectionFactory->create();
                        $orders->getSelect()->limit($limit, $start);
                    }
                    else{
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
                        $orders = array(
                            $order
                        );
                    }

                    foreach ($orders as $item) {

                        $colOrder = $item->getData();                       

                        $productsJson = array();

                        $products = $item->getAllItems();

                        foreach ($products as $prod) {

                           $prodData = $prod->getData();                      

                           $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                           $prodObjManager = $objectManager->create('Magento\Catalog\Model\Product')->load($prod->getId());

                           $imageHelper = $objectManager->get('\Magento\Catalog\Helper\Product');                                                      

                           $url = $prodObjManager->getProductUrl();
                           $image_url = $imageHelper->getImageUrl($prodObjManager);              

                            $productsJson[] = array(
                                "id" => $prod->getId(),
                                "name" => $prod->getName(),
                                "quantity" => (int)$prod->getQtyOrdered(),
                                "price" => (float)$prod->getPrice(),
                                "price_old" => (float)0,
                                "image_url" => $image_url,
                                "url" => $url
                            );
                        }

                        $date = new DateTime($colOrder["created_at"]);
                        $date = $date->getTimestamp(); 

                        $ordersObj[] = array(
                            "order_no" => $colOrder["entity_id"],
                            "date" => $date,
                            "status" => $colOrder["status"],
                            "lastname" => $colOrder["customer_lastname"],
                            "firstname" => $colOrder["customer_firstname"],
                            "email" => $colOrder["customer_email"],
                            "phone" => "",
                            "state" => "",
                            "city" => "",
                            "address" => "",
                            "discount" => $colOrder["base_discount_amount"],
                            "discount_code" => "",
                            "shipping" => "",
                            "fees" => 0,
                            "rebates" => 0,
                            "total" => $colOrder["base_grand_total"],
                            "products" => $productsJson
                        );
                    }

                    header('Content-Type: application/json');
                    echo json_encode($ordersObj, JSON_PRETTY_PRINT);
                    return;

                    break;

                case "products.json":

                    $productsJson = array();

                    $products = null;

                    if(empty($product_id))
                    {                
                        $products = $this->_productsCollectionFactory->create();
                        $products->addAttributeToSelect('*');
                        $products->getSelect()->limit($limit, $start);
                    }
                    else{
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $prodObjManager = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
                        $products = array(
                            $prodObjManager
                        );
                    }

                    foreach ($products as $prod) {

                        $_prod = $prod->getData();

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                        $s = $StockState->getStockQty($_prod["entity_id"]);                 

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $prodObjManager = $objectManager->create('Magento\Catalog\Model\Product')->load($prod->getId());

                        $imageHelper = $objectManager->get('\Magento\Catalog\Helper\Product');

                        $url = $prodObjManager->getProductUrl();
                        $image_url = $imageHelper->getImageUrl($prodObjManager);        

                        $priceOld = $prod->getPriceInfo()->getPrice('regular_price')->getValue();
                        $price = $prod->getPriceInfo()->getPrice('final_price')->getValue();

                        if(!empty($prod->getPriceInfo()->getPrice('special_price')->getValue()))
                        {
                            if(empty($prod->getPriceInfo()->getPrice('regular_price')->getValue()) || $prod->getPriceInfo()->getPrice('regular_price')->getValue() == 0)
                            {
                                $priceOld = $prod->getPriceInfo()->getPrice('special_price')->getValue();
                            }
                            else{
                                $price = $prod->getPriceInfo()->getPrice('special_price')->getValue();
                                $priceOld = $prod->getPriceInfo()->getPrice('regular_price')->getValue();
                            }
                        }
                        
                        $productsJson[] = array(
                            "id" => $_prod["entity_id"],
                            "name" => $_prod["name"],
                            "stock_quantity" => (int)$s,
                            "price" => $price,
                            "price_old" => $priceOld,
                            "image_url" => $image_url,
                            "url" => $url
                        );
                    }

                    header('Content-Type: application/json');
                    echo json_encode($productsJson, JSON_PRETTY_PRINT);
                    exit;
                    return;

                    break;

                case "customers.json":

                    $wp_cust = $this->_customerCollectionFactory->create();

                    $custs = array();

                    foreach ($wp_cust as $users) {

                        $col = $users->getData();

                        $custs[] = array(
                            "email" => $col["email"],
                            "firstname" => $col["firstname"],
                            "lastname" => $col["lastname"]
                        );
                    }

                    header('Content-Type: application/json');
                    echo json_encode($custs, JSON_PRETTY_PRINT);
                    return;

                    break;

                case "subscribers.json":

                    $wp_subscribers = $this->_subscriberCollectionFactory->create();

                    $subs = array();

                    foreach ($wp_subscribers as $users) {

                        $col = $users->getData();

                        $subs[] = array(
                            "email" => $col["subscriber_email"],
                            "firstname" => "",
                            "lastname" => ""
                        );
                    }

                    header('Content-Type: application/json');
                    echo json_encode($subs, JSON_PRETTY_PRINT);
                    return;

                    break;

                case "count.json":
                    
                    $subscribers = $this->_subscriberCollectionFactory->create()
                    ->addFilter('subscriber_status', ['eq' => 1]);
                    $subscribers = $subscribers->count();

                    $json = array(
                        "subscribers" => $subscribers
                    );
        
                    header('Content-Type: application/json');
                    echo json_encode($json, JSON_PRETTY_PRINT);   

                break;

                case "version.json":                    

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
                    $version = $productMetadata->getVersion(); 

                    $version = array(
                        "version" => "Magento " . $version
                    );

                    header('Content-Type: application/json');
                    echo json_encode($version, JSON_PRETTY_PRINT);   

                break;

                case "cron.json":

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;		
                    
                    $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
                    $storeId = $storeManager->getStore()->getId();
            
                    $this->client->setCredentials($storeId);
            
                    $batchSize = 9000;
            
                    $list = $this->client->getSelectedList($storeId);
                    $segmentVal = $this->client->getSelectedSegment($storeId);
                    $segment = null;
            
                    if($segmentVal == 0 || $segmentVal == 1)
                        $segment = array();
                    else
                        $segment = array($segmentVal);	
            
                    $importType = $this->client->getImportType($storeId);
                    if(empty($importType))
                        $importType = 1;
            
                    if($importType == 2)
                    {
                        //customers import
            
                        $customers = $this->subscriberCollectionFactory->create()
                        ->addFilter('is_active', ['eq' => 1])
                        ->addFieldToFilter("website_id", $storeId);
                    
                        $customers_to_import = array();
            
                        foreach ($customers as $item)
                        {
                            $customers_to_import[] = array(
                                "email" => $item["email"],
                                "firstname" => $item["firstname"],
                                "date" => $item["updated_at"]
                            );
            
                            if ((count($customers_to_import) % $batchSize) == 0)
                            {
                                $this->importDataCustomers($customers_to_import, $list, $segment);
                            }
                        }
            
                        if (count($customers_to_import) > 0)
                        {
                            $this->importDataCustomers($customers_to_import, $list, $segment);
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
                            $this->_importData($customers_to_import, $list, $segment);
                        }
                    }
            
                    if (count($customers_to_import) > 0)
                    {
                        $this->_importData($customers_to_import, $list, $segment);
                    }
            
                    unset($customers_to_import);

                    header('Content-Type: application/json');
                    echo json_encode("Imported successfully", JSON_PRETTY_PRINT);  

                    break;
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(403, JSON_PRETTY_PRINT);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

	protected function _importData(&$data, $list, $segments = null)
	{
		$csv = '"email","source"' . PHP_EOL;

		$source = self::safeForCsv("magento 2 newsman plugin");
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

		$source = self::safeForCsv("magento 2 newsman plugin");
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
}?>

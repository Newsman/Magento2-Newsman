<?php


namespace Dazoot\Newsman\Controller\Index;

use \DateTime;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';

    private $_orderCollectionFactory;
    private $_customerCollectionFactory;
    private $_subscriberCollectionFactory;
    private $_productsCollectionFactory;
    private $_subscriber;
    private $_cartSession;
    protected $client;
	protected $subscriberCollectionFactory;
    protected $couponFactory;
    protected $couponRepository;
    protected $resultJsonFactory;

    public function __construct(   
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsCollectionFactory,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Magento\Checkout\Model\Session\Proxy $cartSession,
        CouponFactory $couponFactory,
        CouponRepositoryInterface $couponRepository,
        JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);

        $this->client = new Apiclient();
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_productsCollectionFactory = $productsCollectionFactory;
        $this->_subscriber= $subscriber;
        $this->_cartSession = $cartSession;
        $this->subscriberCollectionFactory = $customerCollectionFactory;
        $this->couponFactory = $couponFactory;
        $this->couponRepository = $couponRepository;
        $this->resultJsonFactory = $resultJsonFactory;
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
        $apikey = (empty($_GET["nzmhash"])) ? "" : $_GET["nzmhash"];
        if(empty($apikey))
        {
            $apikey = empty($_POST['nzmhash']) ? '' : $_POST['nzmhash'];
        }	    
        $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (strpos($authorizationHeader, 'Bearer') !== false) {
            $apikey = trim(str_replace('Bearer', '', $authorizationHeader));
        }
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        if(empty($newsman))
        {
            $newsman = empty($_POST['newsman']) ? '' : $_POST['newsman'];
        }	    
        $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 1;
        $limit = (empty($_GET["limit"])) ? 1000 : $_GET["limit"];        
        $order_id = (empty($_GET["order_id"])) ? "" : $_GET["order_id"];
        $product_id = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];

        if (!empty($newsman) && !empty($apikey) || strpos($_GET["newsman"], 'getCart.json') !== false) {
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
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $imageHelper = $objectManager->get('\Magento\Catalog\Helper\Product');
                    
                    $productsJson = array();

                    $products = null;

                    if(empty($product_id)) {
                            $storeId = (empty($_GET["storeid"])) ? 1 : $_GET["storeid"];
                            $products = $this->_productsCollectionFactory
                                ->create()
                                ->addAttributeToSelect("*")
                                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                                ->setStoreId($storeId)
                                ->joinField(
                                    'qty',
                                    'cataloginventory_stock_item',
                                    'qty',
                                    'product_id=entity_id',
                                    '{{table}}.stock_id=1',
                                    'left'
                                );
                            if((int)$limit < 999999){
                                $products->getSelect()->limit($limit, $start);
                            }
                        }
                    else{
                        $prodObjManager = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
                
                        $products = array($prodObjManager);
                    }

                    foreach ($products as $prod) {

                        //$_prod = $prod->getData();
                                               $product_id = $prod->getId();
                        $product_name = $prod->getName();
                        $product_url = $prod->getProductUrl();
                        $product_image = $imageHelper->getImageUrl($prod);
                        $product_qty = $prod->getQty();
                        $product_price_final = $prod->getPriceInfo()->getPrice('final_price')->getValue();
                        $product_price_special = $prod->getPriceInfo()->getPrice('special_price')->getValue();
                        $product_price_regular = $prod->getPriceInfo()->getPrice('regular_price')->getValue();

                        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        //$StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                        //$s = $StockState->getStockQty($_prod["entity_id"]);                 
                        $priceOld = $product_price_regular;
                        $price = $product_price_final;

                        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        //$prodObjManager = $objectManager->create('Magento\Catalog\Model\Product')->load($prod->getId());

                        /*$imageHelper = $objectManager->get('\Magento\Catalog\Helper\Product');

                        $url = $prodObjManager->getProductUrl();
                        $image_url = $imageHelper->getImageUrl($prodObjManager);        

                        $priceOld = $prod->getPriceInfo()->getPrice('regular_price')->getValue();
                        $price = $prod->getPriceInfo()->getPrice('final_price')->getValue();*/

                        /*if(!empty($prod->getPriceInfo()->getPrice('special_price')->getValue()))
                        {
                            if(empty($prod->getPriceInfo()->getPrice('regular_price')->getValue()) || $prod->getPriceInfo()->getPrice('regular_price')->getValue() == 0)
                            {
                                $priceOld = $prod->getPriceInfo()->getPrice('special_price')->getValue();
                            }
                            else{
                                $price = $prod->getPriceInfo()->getPrice('special_price')->getValue();
                                $priceOld = $prod->getPriceInfo()->getPrice('regular_price')->getValue();
                            }
                        }*/

                        if(!empty($product_price_special)) {
                            if($product_price_regular <= 0) {
                                $priceOld = $product_price_special;
                            } else {
                                $price = $product_price_special;
                            }
                        }
                        
                        $productsJson[] = array(
                            "id" => $product_id,
                            "name" => $product_name,
                            "stock_quantity" => (int)$product_qty,
                            "price" => $price,
                            "price_old" => $priceOld,
                            "image_url" => $product_image,
                            "url" => $product_url
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

                    case "coupons.json":

                        $resultJson = $this->resultJsonFactory->create();

                        try {
                            $discountType = !isset($_GET["type"]) ? -1 : (int)$_GET["type"];
                            $value = !isset($_GET["value"]) ? -1 : (int)$_GET["value"];
                            $batch_size = !isset($_GET["batch_size"]) ? 1 : (int)$_GET["batch_size"];
                            $prefix = !isset($_GET["prefix"]) ? "" : $_GET["prefix"];
                            $expire_date = isset($_GET['expire_date']) ? $_GET['expire_date'] : null;
                            $min_amount = !isset($_GET["min_amount"]) ? -1 : (float)$_GET["min_amount"];

			if(empty($discountType))
			{
			    $discountType = empty($_POST['type']) ? '' : $_POST['type'];
			}			    
			if(empty($value))
			{
			    $value = empty($_POST['value']) ? '' : $_POST['value'];
			}			    
			if(empty($batch_size))
			{
			    $batch_size = empty($_POST['batch_size']) ? '' : $_POST['batch_size'];
			}			    
			if(empty($prefix))
			{
			    $prefix = empty($_POST['prefix']) ? '' : $_POST['prefix'];
			}			    
			if(empty($expire_date))
			{
			    $expire_date = empty($_POST['expire_date']) ? '' : $_POST['expire_date'];
			}			    
			if(empty($min_amount))
			{
			    $min_amount = empty($_POST['min_amount']) ? '' : $_POST['min_amount'];
			}			    
			if(empty($currency))
			{
			    $currency = empty($_POST['currency']) ? '' : $_POST['currency'];
			}				

                            if($discountType == -1)
                            {
                                return $resultJson->setData([
                                    "status" => 0,
                                    "msg" => "Missing type param"
                                ]);
                            }
                            elseif($value == -1)
                            {
                                return $resultJson->setData([
                                    "status" => 0,
                                    "msg" => "Missing value param"
                                ]);
                            }

                            $couponsList = [];

                            for($int = 0; $int < $batch_size; $int++)
                            {
                                $coupon = $this->couponFactory->create();

                                switch($discountType)
                                {
                                    case 1:
                                        $coupon->setDiscountType('by_percent');
                                        break;
                                    case 0:
                                        $coupon->setDiscountType('by_fixed');
                                        break;
                                }

                                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                                $coupon_code = '';
                            
                                do {
                                    $coupon_code = '';
                                    for ($i = 0; $i < 8; $i++) {
                                        $coupon_code .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $full_coupon_code = $prefix . $coupon_code;
                                    $existing_coupon = $this->couponRepository->get($full_coupon_code);
                                } while ($existing_coupon->getId());

                                $rule = $this->ruleFactory->create();
                                $rule->setName('NewsMAN generated coupon code')
                                    ->setDescription('Generated Coupon Code')
                                    ->setCouponType(($discountType == 1) ? 'by_percent' : 'by_fixed')
                                    ->setCouponCode($full_coupon_code)
                                    ->setDiscountAmount($value)
                                    ->setFromDate(date('Y-m-d'))
                                    ->setToDate($expire_date)
                                    ->setUsesPerCoupon(1)
                                    ->setUsesPerCustomer(1)
                                    ->setIsActive(true)
                                    ->save();

                                $couponsList[] = $full_coupon_code;
                            }

                            return $resultJson->setData([
                                "status" => 1,
                                "codes" => $couponsList
                            ]);
                        } catch (\Exception $e) {
                            return $resultJson->setData([
                                "status" => 0,
                                "msg" => $e->getMessage()
                            ]);
                        }

                        break;
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(403, JSON_PRETTY_PRINT);
        }
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
}

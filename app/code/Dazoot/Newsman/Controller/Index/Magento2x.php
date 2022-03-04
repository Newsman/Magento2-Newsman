<?php


namespace Dazoot\Newsman\Controller\Index;

use \DateTime;

class Index extends \Magento\Framework\App\Action\Action
{
    const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';

    private $_orderCollectionFactory;
    private $_customerCollectionFactory;
    private $_subscriberCollectionFactory;
    private $_productsCollectionFactory;
    private $_subscriber;
    private $_cartSession;

    public function __construct(   
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

        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_productsCollectionFactory = $productsCollectionFactory;
        $this->_subscriber= $subscriber;
        $this->_cartSession = $cartSession;
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

        if (!empty($newsman) && !empty($apikey) || $newsman == "getCart.json") {
            $apikey = $_GET["apikey"];
            $currApiKey = $_apikey;

            if($newsman != "getCart.json")
            {
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
    
                            $productsJson[] = array(
                                "id" => $_prod["entity_id"],
                                "name" => $_prod["name"],
                                "stock_quantity" => (int)$s,
                                "price" => (float)$_prod["price"],
                                "price_old" => (float)0,
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

                case "version.js":

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
                    $version = $productMetadata->getVersion(); 

                    $version = array(
                        "version" => "Magento " . $version
                    );

                    header('Content-Type: application/json');
                    echo json_encode($version, JSON_PRETTY_PRINT);   

                break;

                case "getCart.json":

                    if (!empty($_POST["post"]) && (bool)$_POST["post"] == true) {              
                       
                        $cart = $this->_cartSession->getQuote()->getAllVisibleItems();
              
                        $prod = array();

                        foreach ( $cart as $cart_item_key => $cart_item ) {                   

                                $prod[] = array(
                                    "id" => $cart_item->getId(),
                                    "name" => $cart_item->getName(),
                                    "price" => $cart_item->getPrice(),						
                                    "quantity" => $cart_item->getQty()
                                );							
                                                    
                            }									 						

                            header('Content-Type: application/json');
                            echo json_encode($prod, JSON_PRETTY_PRINT);  
                        return;
                    }

                break;
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(403, JSON_PRETTY_PRINT);
        }
    }
}
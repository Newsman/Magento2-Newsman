<?php


namespace Dazoot\Newsman\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    const XML_PATH_API_RECIPIENT = 'newsman/credentials/apiKey';

    private $_orderCollectionFactory;
    private $_customerCollectionFactory;
    private $_subscriberCollectionFactory;
    private $_productsCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsCollectionFactory
    )
    {
        parent::__construct($context);

        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_productsCollectionFactory = $productsCollectionFactory;
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $apiKey = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::XML_PATH_API_RECIPIENT);

        $this->NewsmanFetch($apiKey);
    }

    public function NewsmanFetch($_apikey)
    {
        $apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];

        if (!empty($newsman) && !empty($apikey)) {
            $apikey = $_GET["apikey"];
            $currApiKey = $_apikey;

            if ($apikey != $currApiKey) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(403, JSON_PRETTY_PRINT);
                return;
            }

            switch ($_GET["newsman"]) {
                case "orders.json":

                    $ordersObj = array();

                    $orders = $this->_orderCollectionFactory->create();

                    foreach ($orders as $item) {

                        $colOrder = $item->getData();

                        $productsJson = array();

                        $products = $item->getAllItems();

                        foreach ($products as $prod) {

                            $productsJson[] = array(
                                "id" => $prod->getId(),
                                "name" => $prod->getName(),
                                "quantity" => $prod->getQtyOrdered(),
                                "price" => $prod->getPrice()
                            );
                        }

                        $ordersObj[] = array(
                            "order_no" => $colOrder["entity_id"],
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

                    $products = $this->_productsCollectionFactory->create()->addAttributeToSelect('*')->load();

                    $productsJson = array();

                    foreach ($products as $prod) {

                        $_prod = $prod->getData();

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                        $s = $StockState->getStockQty($_prod["entity_id"]);

                        $productsJson[] = array(
                            "id" => $_prod["entity_id"],
                            "name" => $_prod["name"],
                            "quantity" => $s,
                            "price" => $_prod["price"]
                        );
                    }

                    header('Content-Type: application/json');
                    echo json_encode($productsJson, JSON_PRETTY_PRINT);
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
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(403, JSON_PRETTY_PRINT);
        }
    }
}
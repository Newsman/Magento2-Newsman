<?php
namespace Dazoot\Newsmanmarketing\Block;

use Magento\Framework\View\Element\Template;

class EnhancedEcommerce extends Template
{
    protected $_helper;

    protected $request;

    protected $blockFactory;
    
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
     \Magento\Framework\App\Request\Http $request,
     \Dazoot\Newsmanmarketing\Helper\Data $helper
    ){
        parent::__construct($context,$data);
        $this->_helper = $helper;
        $this->request = $request;
    }

    public function getMagentoVersion(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        return $version;
    }
    
    public function getUaId()
    {
        return $this->_helper->getUaID();
    }
    
    public function checkIP_anonymization()
    {
        return $this->_helper->checkIP_enabled();
    }
    
    public function checkOptOut_Enabled()
    {
        return $this->_helper->checkOptOut_Enabled();
    }
    
    public function getAction()
    {
        $get_action = $this->request->getFullActionName();
        if(method_exists($this, $get_action)) {
            $data = $this->$get_action();
            return array($data,$get_action);
        }
       
    }
    public function getLocalCurrency()
    {
        return $this->_helper->getCurrencyCode();
    }
    protected function catalog_category_view()
    {
        $t_getCategoryProduct = $this->_helper->getCategoryProduct();
         return json_encode($t_getCategoryProduct);    
    }
    protected function catalog_product_view()
    {
        $tvc_product_details = $this->_helper->getProductDetails();
         return json_encode($tvc_product_details);
    }
    protected function checkout_cart_index()
    {
        $tvc_cart_items = $this->_helper->getCartpageInfo();
        return json_encode($tvc_cart_items); 
    }
    
    protected function checkout_onepage_success()
    {
       $tvc_order_obj = $this->_helper->getOrderDetails();
       $tvc_trans_detail = $this->_helper->getTransactionDetails();
       array_push($tvc_order_obj, $tvc_trans_detail);
       return json_encode($tvc_order_obj);
    }    
}
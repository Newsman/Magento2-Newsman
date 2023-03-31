<?php

namespace Dazoot\Newsman\Model;

use Dazoot\Newsman\Helper\Apiclient;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;

class Processor
{
    const BATCH_SIZE = 9000;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    protected $subscriberCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Apiclient
     */
    protected $client;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Amasty\Rewards\Api\RewardsRepositoryInterface
     */
    protected $rewardsRepository;

    /**
     * @var array
     */
    protected $list;

    /**
     * @var array|null
     */
    protected $segments;

    /**
     * @var int
     */
    protected $importType;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var bool
     */
    protected $isApi;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollection;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
    ) {
        $this->logger = $logger;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        $this->_orderCollection = $orderCollection;

        $this->client = new Apiclient();
    }

    public function init($storeId = 0, $isApi = true)
    {
        $this->_storeManager->setCurrentStore($storeId);

        $this->list = $this->client->getSelectedList($storeId);
        $this->segments = $this->client->getSelectedSegment($storeId);
        $this->importType = $this->client->getImportType($storeId);
        $this->storeId = $storeId;
        $this->isApi = $isApi;

        if (!$this->segments) {
            $this->segments = null;
        } else {
            $this->segments = [$this->segments];
        }

        $this->client->setCredentials($storeId);

        return $this;
    }

    public function process()
    {
        if ($this->importType == 2) {
            $customers = $this->customerCollectionFactory->create()
                ->addFilter('is_active', ['eq' => 1])
                ->addFieldToFilter("store_id", $this->storeId);
            $customersToImport = [];

            /** @var \Magento\Customer\Model\Customer $customer */
            foreach ($customers as $customer) {
                $customersToImport[] = $this->_buildCustomerData($customer);

                if ((count($customersToImport) % self::BATCH_SIZE) == 0) {
                    $this->importData($customersToImport);
                    $customersToImport = [];
                }
            }

            $this->importData($customersToImport);
        }

        $subscribers = $this->subscriberCollectionFactory->create()
            ->addFilter('subscriber_status', ['eq' => 1])
            ->addFieldToFilter("store_id", $this->storeId);

        $customersToImport = [];
        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        foreach ($subscribers as $subscriber) {
            $customersToImport[] = $this->_buildSubscriberData($subscriber);

            if ((count($customersToImport) % self::BATCH_SIZE) == 0) {
                $this->importData($customersToImport);
                $customersToImport = [];
            }
        }

        $this->importData($customersToImport);
    }

    public function getWebsiteId($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getWebsiteId();
    }

    protected function _buildCustomerData($customer)
    {
        $customerToImport = [
            'email' => $customer->getEmail(),
            'fullname' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'address' => '',
            'phone_number' => '',
            'revenue' => $this->getRevenue($customer),
            'date_added' => $customer->getCreatedAt(),
            'last_changed' => $customer->getUpdatedAt(),
            'source' => $this->getSource($customer->getStoreId())
        ];

        
        /**
         * For customer address info
         */
        $address = $this->getAddress($customer);
        if ($address) {
            $renderer = $this->addressConfig->getFormatByCode('oneline')->getRenderer();

            $customerToImport['address'] = $renderer->renderArray($address->getData());
            $customerToImport['phone_number'] = $address->getTelephone();
        }

        return $customerToImport;
    }

    protected function importData($data)
    {
        if ($data) {
            $csv = $this->arrayToCsv([
                'email',
                'fullname',
                'address',
                'phone_number',
                'revenue',
                'date_added',
                'last_changed',
                'loyaltypoints',
                'source'
            ]);

            foreach ($data as $_dat) {
                $csv .= $this->arrayToCsv($_dat);
            }

            try {
                $ret = $this->client->importCSV($this->list, $this->segments, $csv);

                if ($ret == "") {
                    throw new \Exception("Import failed " . json_encode($ret));
                }
            } catch (\Exception $e) {
                $this->logger->info('Fail to import Customers Newsletter ' . $e->getMessage());
            }
        }
    }

    protected function arrayToCsv($array)
    {
        $csv = '';
        foreach ($array as $item) {
            $csv .= $this->safeForCsv($item) . ',';
        }

        return rtrim($csv, ',') . PHP_EOL;
    }

    protected function getSource($storeId = 0, $isApi = false)
    {
        return ($isApi ? 'API - ' : '') . $this->_storeManager->getStore($storeId)->getName();
    }

    protected function getRevenue($customer)
    {
        $orderCollection = $this->_orderCollection->create();
        $orderCollection->addFieldToFilter('state',
            ['nin' => [
                \Magento\Sales\Model\Order::STATE_CANCELED,
                \Magento\Sales\Model\Order::STATE_CLOSED
            ]]
        )->addAttributeToFilter('customer_id', ['eq' => $customer->getId()]);


        $totalAmountSpent = 0;

        /**
         * @var $customerOrder \Magento\Sales\Model\Order
         */
        foreach ($orderCollection as $customerOrder) {
            $totalAmountSpent += $customerOrder->getGrandTotal() - $customerOrder->getTotalRefunded()
                - $customerOrder->getTotalCanceled();
        }

        return $totalAmountSpent . ' ' . $this->_storeManager->getStore($customer->getStoreId())->getCurrentCurrency()->getCode();
    }

    protected function _buildSubscriberData($subscriber)
    {
        $subscriberToImport = [
            'email' => $subscriber->getEmail(),
            'fullname' => '',
            'address' => '',
            'phone_number' => '',
            'revenue' => '',
            'date_added' => '',
            'last_changed' => $subscriber->getChangeStatusAt(),
            'source' => $this->getSource($subscriber->getStoreId())
        ];

        if ($subscriber->getCustomerId()) {
            $webSiteId = $this->getWebsiteId($subscriber->getStoreId());
            try {
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer = $this->_customerFactory->create();
                $customer->setWebsiteId($webSiteId)->loadByEmail($subscriber->getEmail());

                $customerData = $this->_buildCustomerData($customer);
                $subscriberToImport = array_merge($subscriberToImport, $customerData);
            } catch (\Exception $e) {
                // Customer doesn't exist
            }
        }

        return $subscriberToImport;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     */
    private function getAddress($customer)
    {
        if ($customer->getDefaultShippingAddress()) {
            $address = $customer->getDefaultShippingAddress();
        } else {
            $address = $customer->getDefaultBillingAddress();
        }

        return $address;
    }

    private function safeForCsv($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }
}
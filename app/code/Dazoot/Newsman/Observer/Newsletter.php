<?php

namespace Dazoot\Newsman\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Dazoot\Newsman\Helper\Apiclient;

class Newsletter implements ObserverInterface
{

    private $logger;
    private $_subscriber;
    protected $client;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $this->logger = $logger;
        $this->_subscriber= $subscriber;
        $this->client = new Apiclient();
    }

    public function execute(Observer $observer)
    {
		$this->client->setCredentials(0);

        $event = $observer->getEvent();
        $customer = $event->getSubscriber();
        $customerEmail = $customer->getSubscriberEmail();
        $customerName = $customer->getFirstname();
        $customerLastname = $customer->getLastname();
        $customerId = $customer->getId();

        $checkSubscriber = $this->_subscriber->loadByEmail($customerEmail);
        if ($checkSubscriber->isSubscribed()) {

        }
        else{
            $ret = $this->client->unsubscribe($customerEmail);

            $json = \json_encode($ret);
        }

        $sub = $checkSubscriber->getData();
    }
}
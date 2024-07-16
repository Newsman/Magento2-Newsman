<?php

namespace Dazoot\Newsmansmtp\Plugin\Mail;

use Closure;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\TransportInterface;
use Dazoot\Newsmansmtp\Helper\Data;
use Dazoot\Newsmansmtp\Model\Store;
use Dazoot\Newsmansmtp\Model\ZendMailOne\Smtp as ZendMailOneSmtp;
use Dazoot\Newsmansmtp\Model\ZendMailTwo\Smtp as ZendMailTwoSmtp;
use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mail\Transport\Smtp as LaminasSmtpTransport;

/**
 * Class TransportPlugin
 * @package Dazoot\Newsmansmtp\Plugin\Mail
 */
class TransportPlugin extends LaminasSmtpTransport
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var Store
     */
    protected $storeModel;

    /**
     * @param Data $dataHelper
     * @param Store $storeModel
     */
    public function __construct(
        Data $dataHelper,
        Store $storeModel
    ) {
        $this->dataHelper = $dataHelper;
        $this->storeModel = $storeModel;
        parent::__construct();
    }

    /**
     * @param TransportInterface $subject
     * @param Closure $proceed
     * @throws MailException
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        Closure $proceed
    ) {
        if ($this->dataHelper->isActive()) {
            if (method_exists($subject, 'getStoreId')) {
                $this->storeModel->setStoreId($subject->getStoreId());
            }

            $message = $subject->getMessage();

            // ZendMail1 - Magento <= 2.2.7
            // ZendMail2 - Magento >= 2.2.8
            if ($message instanceof Zend_mail) {
                $smtp = new ZendMailOneSmtp($this->dataHelper, $this->storeModel);
                $smtp->sendSmtpMessage($message);
            } elseif ($message instanceof LaminasMessage) {
                $smtp = new ZendMailTwoSmtp($this->dataHelper, $this->storeModel);
                $smtp->sendSmtpMessage($message);
            } else {
                $proceed();
            }
        } else {
            $proceed();
        }
    }
}

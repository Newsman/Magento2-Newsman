<?php

namespace Dazoot\Newsmansmtp\Model\ZendMailOne;

use Exception;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as LaminasSmtpTransport;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Phrase;
use Dazoot\Newsmansmtp\Helper\Data;
use Dazoot\Newsmansmtp\Model\Store;

/**
 * Class Smtp
 * For Magento < 2.2.8
 * @package Dazoot\Newsmansmtp\Model\ZendMailOne
 */
class Smtp extends LaminasSmtpTransport
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
     * @param Data $dataHelper
     * @return Smtp
     */
    public function setDataHelper(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
        return $this;
    }

    /**
     * @param Store $storeModel
     * @return Smtp
     */
    public function setStoreModel(Store $storeModel)
    {
        $this->storeModel = $storeModel;
        return $this;
    }

    /**
     * @param MessageInterface $message
     * @throws MailException
     */
    public function sendSmtpMessage(MessageInterface $message)
    {
        $dataHelper = $this->dataHelper;
        $dataHelper->setStoreId($this->storeModel->getStoreId());

        if ($message instanceof \Laminas\Mail\Message) {
            if ($message->getDate() === null) {
                $message->setDate();
            }
        }

        // ... (rest of the code remains unchanged)

        // Initialize Laminas SMTP transport
        $smtpHost = $dataHelper->getConfigSmtpHost();
        $this->setOptions([
            'host' => $smtpHost,
            'port' => $dataHelper->getConfigSmtpPort(),
            'connection_class' => $dataHelper->getConfigSsl(),
            'connection_config' => [
                'username' => $dataHelper->getConfigUsername(),
                'password' => $dataHelper->getConfigPassword(),
            ],
        ]);

        try {
            parent::send($message);
        } catch (Exception $e) {
            throw new MailException(
                new Phrase($e->getMessage()),
                $e
            );
        }
    }
}

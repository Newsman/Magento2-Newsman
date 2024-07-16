<?php

namespace Dazoot\Newsmansmtp\Model;

use Exception;
use InvalidArgumentException;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Sendmail as LaminasSendmailTransport;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

/**
 * Class Transport
 * @package Dazoot\Newsmansmtp\Model
 */
class Transport implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $_message;

    /**
     * @param MessageInterface $message
     * @param null $parameters
     */
    public function __construct(MessageInterface $message, $parameters = null)
    {
        if (!$message instanceof MessageInterface) {
            throw new InvalidArgumentException('The message should be an instance of \Laminas\Mail\Message');
        }

        $this->_message = $message;
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws MailException
     */
    public function sendMessage()
    {
        try {
            $transport = new LaminasSendmailTransport();
            $transport->send($this->_message);
        } catch (Exception $e) {
            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return $this->_message;
    }
}

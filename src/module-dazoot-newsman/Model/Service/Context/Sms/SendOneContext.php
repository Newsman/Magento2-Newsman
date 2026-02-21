<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Sms;

use Dazoot\Newsman\Model\Service\Context\StoreContext;
use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * Data transfer context for sms.sendone — sends a single SMS message to a phone number.
 */
class SendOneContext extends StoreContext
{
    /**
     * Recipient phone number.
     *
     * @var string
     */
    protected $to = '';

    /**
     * SMS message text.
     *
     * @var string
     */
    protected $text = '';

    /**
     * Set recipient phone number.
     *
     * @param string $to
     * @return ContextInterface
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Get recipient phone number.
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set SMS message text.
     *
     * @param string $text
     * @return ContextInterface
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Get SMS message text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
}

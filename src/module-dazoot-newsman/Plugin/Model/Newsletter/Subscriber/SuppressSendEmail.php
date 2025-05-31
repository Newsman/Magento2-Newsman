<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Plugin\Model\Newsletter\Subscriber;

use Dazoot\Newsman\Model\Config;
use Magento\Newsletter\Model\Subscriber;

/**
 * Suppress send newsletter subscribe and unsubscribe emails when admin configuration is set so
 */
class SuppressSendEmail
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Subscriber $subject
     * @param \Closure $proceed
     * @return Subscriber
     */
    public function aroundSendConfirmationRequestEmail(Subscriber $subject, \Closure $proceed)
    {
        if (!$this->config->isEnabled($subject->getStoreId())) {
            return $proceed();
        }

        // Do not send email. Newsman will send the equivalent email.
        if ($this->config->isNewsletterNewsmanSendSub($subject->getStoreId())) {
            return $subject;
        }

        return $proceed();
    }

    /**
     * @param Subscriber $subject
     * @param \Closure $proceed
     * @return Subscriber
     */
    public function aroundSendConfirmationSuccessEmail(Subscriber $subject, \Closure $proceed)
    {
        if (!$this->config->isEnabled($subject->getStoreId())) {
            return $proceed();
        }

        // Do not send email. Newsman will send the equivalent email.
        if ($this->config->isNewsletterNewsmanSendSub($subject->getStoreId())) {
            return $subject;
        }

        return $proceed();
    }

    /**
     * @param Subscriber $subject
     * @param \Closure $proceed
     * @return Subscriber
     */
    public function aroundSendUnsubscriptionEmail(Subscriber $subject, \Closure $proceed)
    {
        if (!$this->config->isEnabled($subject->getStoreId())) {
            return $proceed();
        }

        // Do not send email. Newsman will send the equivalent email.
        if ($this->config->isNewsletterNewsmanSendSub($subject->getStoreId())) {
            return $subject;
        }

        return $proceed();
    }
}

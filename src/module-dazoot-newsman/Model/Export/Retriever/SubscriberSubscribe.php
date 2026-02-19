<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Export\Retriever\V1\ApiV1Exception;
use Dazoot\Newsman\Model\Validator\EmailAddress as EmailAddressValidator;
use Dazoot\Newsman\Model\WebhooksFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Subscribe an email address to the store's newsletter (API v1: subscriber.subscribe).
 *
 * Delegates to the existing Webhooks::processSubscribe() so that the same
 * double-opt-in rules, events, and subscriber lifecycle logic are applied.
 */
class SubscriberSubscribe extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var WebhooksFactory
     */
    protected $webhooksFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EmailAddressValidator
     */
    protected $emailAddressValidator;

    /**
     * @param WebhooksFactory $webhooksFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param EmailAddressValidator $emailAddressValidator
     */
    public function __construct(
        WebhooksFactory $webhooksFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        EmailAddressValidator $emailAddressValidator
    ) {
        $this->webhooksFactory = $webhooksFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->emailAddressValidator = $emailAddressValidator;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if (empty($email)) {
            throw new ApiV1Exception(3100, 'Missing "email" parameter', 400);
        }

        if (!$this->emailAddressValidator->isValid($email)) {
            throw new ApiV1Exception(3101, 'Invalid email address: ' . $email, 400);
        }

        $storeId = !empty($storeIds) ? (int) reset($storeIds) : (int) $this->storeManager->getStore()->getId();

        $this->logger->info(__('subscriber.subscribe: %1, store %2', $email, $storeId));

        $result = $this->webhooksFactory->create()->processSubscribe(
            ['data' => ['email' => $email]],
            $storeId
        );

        if (isset($result['error'])) {
            throw new ApiV1Exception(3102, 'Failed to subscribe email: ' . $email, 500);
        }

        return ['success' => true, 'email' => $email];
    }
}

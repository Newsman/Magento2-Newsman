<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Sms;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Sms\SendOneContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Send a single SMS message via Newsman API
 */
class SendOne extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/sms.sendone
     */
    public const ENDPOINT = 'sms.sendone';

    /**
     * Execute the API call to send a single SMS.
     *
     * @param SendOneContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to send one SMS to %1', $context->getTo()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id' => $apiContext->getListId(),
                'text'    => $context->getText(),
                'to'      => $context->getTo(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Sent SMS to %1', $context->getTo()));

        return $result;
    }
}

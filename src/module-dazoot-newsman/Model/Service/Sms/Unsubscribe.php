<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Sms;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Sms\UnsubscribeContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Unsubscribe a telephone number from a Newsman SMS list
 */
class Unsubscribe extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/sms.saveUnsubscribe
     */
    public const ENDPOINT = 'sms.saveUnsubscribe';

    /**
     * Execute the API call to unsubscribe a telephone number from an SMS list.
     *
     * @param UnsubscribeContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to unsubscribe telephone %1', $context->getTelephone()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'   => $apiContext->getListId(),
                'telephone' => $context->getTelephone(),
                'ip'        => $context->getIp(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Unsubscribed telephone %1', $context->getTelephone()));

        return $result;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContext;

/**
 * Unsubscribe an email address by list ID
 */
class UnsubscribeEmail extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/subscriber.saveUnsubscribe
     */
    public const ENDPOINT = 'subscriber.saveUnsubscribe';

    /**
     * @param UnsubscribeEmailContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to unsubscribe email %1', $context->getEmail()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [
                'list_id' => $apiContext->getListId(),
                'email' => $context->getEmail(),
                'ip' => $context->getIp()
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Unsubscribed email %1', $context->getEmail()));

        return $result;
    }
}

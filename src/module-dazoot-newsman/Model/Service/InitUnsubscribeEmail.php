<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsman\Model\Service\Context\InitUnsubscribeEmailContext;

/**
 * Init unsubscribe an email address by list ID
 */
class InitUnsubscribeEmail extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/subscriber.initUnsubscribe
     */
    public const ENDPOINT = 'subscriber.initUnsubscribe';

    /**
     * @param InitUnsubscribeEmailContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to init unsubscribe email %1', $context->getEmail()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [
                'list_id' => $apiContext->getListId(),
                'email' => $context->getEmail(),
                'ip' => $context->getIp(),
                'options' => empty($context->getOptions()) ? '' : $context->getOptions(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Init unsubscribed successful for email %1', $context->getEmail()));

        return $result;
    }
}

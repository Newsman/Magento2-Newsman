<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsman\Model\Service\Context\SubscribeEmailContext;

/**
 * Subscribe an email address by list ID
 */
class SubscribeEmail extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/subscriber.saveSubscribe
     */
    public const ENDPOINT = 'subscriber.saveSubscribe';

    /**
     * @param SubscribeEmailContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        if (!$this->emailAddressValidator->isValid($context->getEmail())) {
            $e = new LocalizedException(__('Invalid email address %1', $context->getEmail()));
            $this->logger->error($e);
            throw $e;
        }

        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to subscribe email %1', $context->getEmail()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id' => $apiContext->getListId(),
                'email' => $context->getEmail(),
                'firstname' => $context->getFirstsname(),
                'lastname' => $context->getLastsname(),
                'ip' => $context->getIp(),
                'props' => empty($context->getProperties()) ? '' : $context->getProperties(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Subscribed email %1', $context->getEmail()));

        return $result;
    }
}

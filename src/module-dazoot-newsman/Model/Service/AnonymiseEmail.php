<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Dazoot\Newsman\Model\Service\Context\AnonymiseEmailContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Anonymise an email address in a list
 *
 * Newsman accepts the request right away and anonymises the subscriber in the background: the email address and the
 * personal data are replaced and the subscriber is unsubscribed.
 */
class AnonymiseEmail extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/subscriber.anonymiseEmail
     */
    public const ENDPOINT = 'subscriber.anonymiseEmail';

    /**
     * Execute the email anonymise API call.
     *
     * @param AnonymiseEmailContext $context
     * @return array|bool
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(
            __('Try to anonymise email %1 in list %2', $context->getEmail(), $apiContext->getListId())
        );

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id' => $apiContext->getListId(),
                'email' => $context->getEmail()
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Anonymised email %1 in list %2', $context->getEmail(), $apiContext->getListId()));

        return $result;
    }
}

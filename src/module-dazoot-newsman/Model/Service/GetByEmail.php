<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service;

use Dazoot\Newsman\Model\Service\Context\GetByEmailContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get subscriber details by email address
 */
class GetByEmail extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/subscriber.getByEmail
     */
    public const ENDPOINT = 'subscriber.getByEmail';

    /**
     * Execute the API call to get a subscriber by email address.
     *
     * @param GetByEmailContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        if (!$this->emailAddressValidator->isValid($context->getEmail())) {
            throw new LocalizedException(__('Invalid email address: %1', $context->getEmail()));
        }

        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to get by email %1', $context->getEmail()));

        $client = $this->createApiClient();
        $result = $client->get(
            $apiContext,
            [
                'list_id' => $apiContext->getListId(),
                'email'   => $context->getEmail(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Done get by email %1', $context->getEmail()));

        return $result;
    }
}

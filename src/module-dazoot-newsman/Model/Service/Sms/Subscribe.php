<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Sms;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Sms\SubscribeContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Subscribe a telephone number to a Newsman SMS list
 */
class Subscribe extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/sms.saveSubscribe
     */
    public const ENDPOINT = 'sms.saveSubscribe';

    /**
     * Execute the API call to subscribe a telephone number to an SMS list.
     *
     * @param SubscribeContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to subscribe telephone %1', $context->getTelephone()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'   => $apiContext->getListId(),
                'telephone' => $context->getTelephone(),
                'firstname' => $context->getFirstsname(),
                'lastname'  => $context->getLastsname(),
                'ip'        => $context->getIp(),
                'props'     => empty($context->getProperties()) ? '' : $context->getProperties(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Subscribed telephone %1', $context->getTelephone()));

        return $result;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Service;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsmanmarketing\Model\Service\Context\SaveOrdersContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save multiple orders via Newsman remarketing API
 */
class SaveOrders extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/remarketing.saveOrders
     */
    public const ENDPOINT = 'remarketing.saveOrders';

    /**
     * Execute the API call to save multiple orders in Newsman remarketing.
     *
     * @param SaveOrdersContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__(
            'Try to save %1 orders, list %2',
            count($context->getOrders()),
            $apiContext->getListId()
        ));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id' => $apiContext->getListId(),
                'orders'  => $context->getOrders(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__(
            'Saved %1 orders, list %2',
            count($context->getOrders()),
            $apiContext->getListId()
        ));

        return $result;
    }
}

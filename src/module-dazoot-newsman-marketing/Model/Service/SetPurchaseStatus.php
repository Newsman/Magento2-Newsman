<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Service;

use Dazoot\Newsman\Model\Service\AbstractService;
use Magento\Framework\Exception\LocalizedException;
use Dazoot\Newsmanmarketing\Model\Service\Context\SetPurchaseStatusContext;

/**
 * Set purchase (order) status (state)
 */
class SetPurchaseStatus extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/remarketing.setPurchaseStatus
     */
    public const ENDPOINT = 'remarketing.setPurchaseStatus';

    /**
     * Execute the API call to update purchase status in Newsman.
     *
     * @param SetPurchaseStatusContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);
        $segmentId = $apiContext->getSegmentId();

        $this->logger->info(__(
            'Try to set purchase %1 state %2, list %3',
            $context->getOrderId(),
            $context->getState(),
            $apiContext->getListId()
        ));

        $client = $this->createApiClient();
        $result = $client->get(
            $apiContext,
            [
                'list_id' => $apiContext->getListId(),
                'order_id' => $context->getOrderId(),
                'status' => $context->getState()
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__(
            'Purchase %1 state %2 sent, list %3',
            $context->getOrderId(),
            $context->getState(),
            $apiContext->getListId()
        ));

        return $result;
    }
}

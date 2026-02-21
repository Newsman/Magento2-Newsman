<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Service;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsmanmarketing\Model\Service\Context\SaveOrderContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save order via Newsman remarketing API
 */
class SaveOrder extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/remarketing.saveOrder
     */
    public const ENDPOINT = 'remarketing.saveOrder';

    /**
     * Execute the API call to save an order in Newsman remarketing.
     *
     * @param SaveOrderContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $details = $context->getOrderDetails();
        $orderNo = isset($details['order_no']) ? $details['order_no'] : 'unknown';

        $this->logger->info(__(
            'Try to save order %1, list %2',
            $orderNo,
            $apiContext->getListId()
        ));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'        => $apiContext->getListId(),
                'order_details'  => $context->getOrderDetails(),
                'order_products' => $context->getOrderProducts(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__(
            'Saved order %1, list %2',
            $orderNo,
            $apiContext->getListId()
        ));

        return $result;
    }
}

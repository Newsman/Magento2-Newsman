<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Segment;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Segment\AddSubscriberContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Add an email subscriber to a Newsman segment
 */
class AddSubscriber extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/segment.addSubscriber
     */
    public const ENDPOINT = 'segment.addSubscriber';

    /**
     * Execute the API call to add a subscriber to a segment.
     *
     * @param AddSubscriberContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        $apiContext = $this->createApiContext()
            ->setStore($context->getStore())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__(
            'Try to add to segment %1 subscriber ID %2',
            $context->getSegmentId(),
            $context->getSubscriberId()
        ));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'       => $apiContext->getListId(),
                'segment_id'    => $context->getSegmentId(),
                'subscriber_id' => $context->getSubscriberId(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__(
            'Added to segment %1 subscriber ID %2',
            $context->getSegmentId(),
            $context->getSubscriberId()
        ));

        return $result;
    }
}

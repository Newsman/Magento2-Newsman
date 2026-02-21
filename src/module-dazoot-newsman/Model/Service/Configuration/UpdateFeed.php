<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\UpdateFeedContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Update a product feed in Newsman
 */
class UpdateFeed extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/feeds.updateFeed
     */
    public const ENDPOINT = 'feeds.updateFeed';

    /**
     * Execute the API call to update a product feed.
     *
     * @param UpdateFeedContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
        if (empty($context->getListId())) {
            $e = new LocalizedException(__('List ID is required.'));
            $this->logger->error($e);
            throw $e;
        }

        $apiContext = $this->createApiContext()
            ->setUserId($context->getUserId())
            ->setApiKey($context->getApiKey())
            ->setEndpoint(self::ENDPOINT);

        $this->logger->info(__('Try to update feed %1', $context->getFeedId()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id' => $context->getListId(),
                'feed_id' => $context->getFeedId(),
                'props'   => $context->getProperties(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Updated feed %1', $context->getFeedId()));

        return $result;
    }
}

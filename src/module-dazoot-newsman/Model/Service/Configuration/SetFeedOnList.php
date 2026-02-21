<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\SetFeedOnListContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Install a product feed on a Newsman list
 */
class SetFeedOnList extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/feeds.setFeedOnList
     */
    public const ENDPOINT = 'feeds.setFeedOnList';

    /**
     * Execute the API call to install a product feed on a list.
     *
     * @param SetFeedOnListContext $context
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

        $this->logger->info(__('Try to install products feed %1', $context->getUrl()));

        $client = $this->createApiClient();
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'   => $context->getListId(),
                'url'       => $context->getUrl(),
                'website'   => $context->getWebsite(),
                'type'      => $context->getType(),
                'return_id' => $context->getReturnId(),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        $this->logger->info(__('Installed products feed %1', $context->getUrl()));

        return $result;
    }
}

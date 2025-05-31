<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\ListContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get all newsletter segments by list ID
 */
class GetSegments extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/segment.all
     */
    public const ENDPOINT = 'segment.all';

    /**
     * @param ListContext $context
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

        $client = $this->createApiClient();
        $result = $client->get($apiContext, ['list_id' => $context->getListId()]);

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        return $result;
    }
}

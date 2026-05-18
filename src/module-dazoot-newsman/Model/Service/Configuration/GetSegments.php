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
 * Get newsletter segments in a single segment.all API call
 */
class GetSegments extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/segment.all
     */
    public const ENDPOINT = 'segment.all';

    /**
     * Fetch newsletter segments in a single segment.all API call.
     *
     * The context list ID may be a single list ID, the string 'all', or an
     * array of list IDs (sent as a comma-separated list). The raw API result
     * is returned as-is: a flat list of segment rows, each carrying its own
     * list_id. Callers group it as needed.
     *
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

        $listId = $context->getListId();
        if (is_array($listId)) {
            $listId = implode(',', array_map('intval', $listId));
        }

        $apiContext = $this->createApiContext()
            ->setUserId($context->getUserId())
            ->setApiKey($context->getApiKey())
            ->setEndpoint(self::ENDPOINT);

        $client = $this->createApiClient();
        $result = $client->get($apiContext, ['list_id' => $listId]);

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        return $result;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Service\Configuration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsmanmarketing\Model\Service\Context\GetSettingsContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get remarketing settings for a Newsman list
 */
class GetSettings extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/remarketing.getSettings
     */
    public const ENDPOINT = 'remarketing.getSettings';

    /**
     * Execute the API call to retrieve remarketing settings for a list.
     *
     * @param GetSettingsContext $context
     * @return array
     * @throws LocalizedException
     */
    public function execute($context)
    {
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

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration\Sms;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\UserContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get all SMS lists for a Newsman user
 */
class GetListAll extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/sms.lists
     */
    public const ENDPOINT = 'sms.lists';

    /**
     * Execute the API call to retrieve all SMS lists for the user.
     *
     * @param UserContext $context
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
        $result = $client->get($apiContext);

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        return $result;
    }
}

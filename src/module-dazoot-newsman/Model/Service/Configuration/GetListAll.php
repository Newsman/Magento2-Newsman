<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\UserContext;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get all newsletter lists
 */
class GetListAll extends AbstractService
{
    /**
     * @see https://kb.newsman.com/api/1.2/list.all
     */
    public const ENDPOINT = 'list.all';

    /**
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

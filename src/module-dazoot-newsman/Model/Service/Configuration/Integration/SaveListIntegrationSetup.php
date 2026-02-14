<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Configuration\Integration;

use Dazoot\Newsman\Model\Service\AbstractService;
use Dazoot\Newsman\Model\Service\Context\Configuration\SaveListIntegrationSetupContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Save list integration setup via Newsman API
 */
class SaveListIntegrationSetup extends AbstractService
{
    /**
     * @see https://kb.newsman.ro/api/1.2/integration.saveListIntegrationSetup
     */
    public const ENDPOINT = 'integration.saveListIntegrationSetup';

    /**
     * Execute API call to save integration setup for a list.
     *
     * @param SaveListIntegrationSetupContext $context
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
        $result = $client->post(
            $apiContext,
            [],
            [
                'list_id'     => $context->getListId(),
                'integration' => $context->getIntegration(),
                'payload'     => json_encode($context->getPayload()),
            ]
        );

        if ($client->hasError()) {
            throw new LocalizedException(__($client->getErrorMessage()), null, $client->getErrorCode());
        }

        return $result;
    }
}

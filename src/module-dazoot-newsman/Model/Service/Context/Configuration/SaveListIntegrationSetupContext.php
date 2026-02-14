<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Service\Context\Configuration;

use Dazoot\Newsman\Model\Service\ContextInterface;

/**
 * SaveListIntegrationSetup data transfer context
 */
class SaveListIntegrationSetupContext extends ListContext
{
    /**
     * Integration platform identifier.
     *
     * @var string
     */
    protected $integration = 'magento2';

    /**
     * Payload data for the integration setup.
     *
     * @var array
     */
    protected $payload = [];

    /**
     * Set the integration platform identifier.
     *
     * @param string $integration
     * @return ContextInterface
     */
    public function setIntegration($integration)
    {
        $this->integration = $integration;
        return $this;
    }

    /**
     * Retrieve the integration platform identifier.
     *
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * Set the payload data.
     *
     * @param array $payload
     * @return ContextInterface
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Retrieve the payload data.
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}

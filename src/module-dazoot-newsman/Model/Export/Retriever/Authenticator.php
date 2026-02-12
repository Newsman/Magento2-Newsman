<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Model\Config;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class Retriever Authenticator
 */
class Authenticator
{
    public const API_KEY_PARAM = 'nzmhash';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Authenticate request by API key or alternate authorization key.
     *
     * @param string $apiKey
     * @param StoreInterface $store
     * @return true
     * @throws AuthenticatorException
     */
    public function authenticate($apiKey, $store)
    {
        if (empty($apiKey)) {
            throw new AuthenticatorException(__('Empty API key provided.'));
        }

        $configApiKey = $this->config->getApiKey($store);
        $configAuthToken = $this->config->getExportAuthenticateToken($store);

        $alternateName = $this->config->getExportAuthorizeHeaderName($store);
        $alternateKey = $this->config->getExportAuthorizeHeaderKey($store);
        $isAlternate = false;
        if (!empty($alternateName) && !empty($alternateKey)) {
            $isAlternate = true;
        }

        $isAlternateWithToken = false;
        if (!empty($configAuthToken)) {
            $isAlternateWithToken = true;
        }

        $isAuthenticated = false;
        if ($configApiKey === $apiKey) {
            $isAuthenticated = true;
        }
        if ($isAlternateWithToken && ($configAuthToken === $apiKey)) {
            $isAuthenticated = true;
        }
        if ($isAlternate && ($alternateKey === $apiKey)) {
            $isAuthenticated = true;
        }

        if (!$isAuthenticated) {
            throw new AuthenticatorException(
                __('Invalid API key for store ' . $store->getName() . ' (' . $store->getId() . ').')
            );
        }

        return true;
    }
}

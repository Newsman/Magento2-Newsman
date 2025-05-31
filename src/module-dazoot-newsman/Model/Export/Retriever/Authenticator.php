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
     * @param string $apiKey
     * @param StoreInterface $store
     * @return true
     */
    public function authenticate($apiKey, $store)
    {
        if (empty($apiKey)) {
            throw new AuthenticatorException(__('Empty API key provided.'));
        }

        if ($this->config->getApiKey($store) !== $apiKey) {
            throw new AuthenticatorException(
                __('Invalid API key for store ' . $store->getName() . ' (' . $store->getId() . ').')
            );
        }

        return true;
    }
}

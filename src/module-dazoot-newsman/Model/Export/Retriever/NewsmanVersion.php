<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Magento\Framework\Composer\ComposerInformation;

/**
 * Get Newsman extension version
 */
class NewsmanVersion extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var ComposerInformation
     */
    protected $composerInformation;

    /**
     * @param ComposerInformation $composerInformation
     */
    public function __construct(ComposerInformation $composerInformation)
    {
        $this->composerInformation = $composerInformation;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        $packages = $this->composerInformation->getInstalledMagentoPackages();
        if (isset($packages[NewsmanConfig::COMPOSER_PACKAGE_NAME])) {
            return ['version' => $packages[NewsmanConfig::COMPOSER_PACKAGE_NAME]['version']];
        }

        return ['version' => 'unknown'];
    }
}

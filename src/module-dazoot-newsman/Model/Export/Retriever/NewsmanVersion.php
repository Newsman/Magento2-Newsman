<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Magento\Framework\Composer\ComposerInformation;

/**
 * Get Newsman extension version
 */
class NewsmanVersion implements RetrieverInterface
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
        if (isset($packages['newsman/magento2x'])) {
            return ['version' => $packages['newsman/magento2x']['version']];
        }

        return ['version' => 'unknown'];
    }
}

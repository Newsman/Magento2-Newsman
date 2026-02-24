<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Get store platform language version
 */
class PlatformLanguageVersion extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        return ['language_version' => phpversion()];
    }
}

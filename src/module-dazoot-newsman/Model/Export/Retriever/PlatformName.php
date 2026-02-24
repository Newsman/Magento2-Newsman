<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Get store platform name
 */
class PlatformName extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /**
     * @inheritdoc
     */
    public function process($data = [], $storeIds = [])
    {
        return ['name' => $this->productMetadata->getName()];
    }
}

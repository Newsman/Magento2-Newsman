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
 * Get Magento version
 */
class Version implements RetrieverInterface
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
        return ['version' => 'Magento ' . $this->productMetadata->getVersion() . ''];
    }
}

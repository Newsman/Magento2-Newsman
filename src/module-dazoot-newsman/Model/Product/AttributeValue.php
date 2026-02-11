<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Product;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Product Attribute Value
 */
class AttributeValue
{
    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Url
     */
    protected $catalogUrl;

    /**
     * @param CatalogConfig $catalogConfig
     * @param ProductResourceFactory $productResourceFactory
     * @param ProductFactory $productFactory
     * @param Url $catalogUrl
     */
    public function __construct(
        CatalogConfig $catalogConfig,
        ProductResourceFactory $productResourceFactory,
        ProductFactory $productFactory,
        Url $catalogUrl
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->productResourceFactory = $productResourceFactory;
        $this->productFactory = $productFactory;
        $this->catalogUrl = $catalogUrl;
    }

    /**
     * Retrieve the product URL for a specific product and store.
     *
     * @param int $productId
     * @param int $storeId
     * @return string
     */
    public function getUrl($productId, $storeId)
    {
        $products = $this->catalogUrl->getRewriteByProductStore([$productId => $storeId]);
        $urlDataObject = new DataObject($products[$productId]);
        $product = $this->productFactory->create()
            ->setId($productId)
            ->setUrlDataObject($urlDataObject);

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Retrieve the raw or labeled value of a product attribute.
     *
     * @param int $productId
     * @param string $attributeCode
     * @param int|bool $storeId
     * @param bool $useLabel
     * @param bool $useDefault
     * @return mixed
     * @throws LocalizedException
     */
    public function getValue(
        $productId,
        $attributeCode,
        $storeId = false,
        $useLabel = false,
        $useDefault = false
    ) {
        if ($storeId === false) {
            $storeId = $this->catalogConfig->getStoreId();
        }

        $attribute = $this->getAttribute($attributeCode);
        /** @var ProductResource $productResource */
        $productResource = $this->productResourceFactory->create();
        $val = $productResource->getAttributeRawValue($productId, $attribute->getId(), $storeId);
        if ($attribute->isStatic() && is_array($val) && isset($val[$attributeCode])) {
            $val = $val[$attributeCode];
        }

        if ($useLabel && $attribute->usesSource()) {
            $value = $attribute->getSource()->getOptionText($val);
        } else {
            $value = $val;
        }

        if ($useDefault && $value == '') {
            $value = $attribute->getDefaultValue();
        }

        return $value;
    }

    /**
     * Retrieve attribute model by attribute code.
     *
     * @param string $code
     * @return AbstractAttribute|null
     * @throws LocalizedException
     */
    public function getAttribute($code)
    {
        return $this->catalogConfig->getAttribute(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $code
        );
    }
}

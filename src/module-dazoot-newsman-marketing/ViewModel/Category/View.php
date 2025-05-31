<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel\Category;

use Dazoot\Newsmanmarketing\ViewModel\Marketing;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\LayoutInterface;

/**
 * Category view model
 */
class View extends Marketing
{
    /**
     * @param Category $category
     * @param LayoutInterface $layout
     * @return array
     */
    public function getMarketingData($category, $layout)
    {
        $data = ['list' => []];

        /** @var ListProduct $block */
        $block = $layout->getBlock('category.products.list');
        if (!($block instanceof ListProduct)) {
            return $data;
        }

        $collection = $block->getLoadedProductCollection();
        $step = 0;
        /** @var Product $product */
        foreach ($collection as $product) {
            $productData = [
                'id' => $this->escapeValue($product->getSku()),
                'name' => $this->escapeValue($product->getName()),
                'price' => $this->escapeValue($product->getFinalPrice()),
                'list' => __('Category List'),
                'category' => $this->escapeValue($category->getName()),
                'position' => (string) ++$step
            ];
            $brandCode = $this->config->getBrandAttribute();
            if (!empty($brandCode) && !empty($product->getData($brandCode))) {
                $productData['brand'] = $this->escapeValue($product->getAttributeText($brandCode));
            }
            $data['list'][] = $productData;
        }

        return $data;
    }
}

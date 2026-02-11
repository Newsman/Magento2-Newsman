<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel\Search;

use Dazoot\Newsmanmarketing\ViewModel\Marketing;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\LayoutInterface;

/**
 * Products search list view model
 */
class ProductList extends Marketing
{
    /**
     * Retrieve marketing data for the current search result list.
     *
     * @param LayoutInterface $layout
     * @return array
     */
    public function getMarketingData($layout)
    {
        $data = ['list' => []];

        /** @var ListProduct|\Magento\CatalogSearch\Block\SearchResult\ListProduct $block */
        $block = $layout->getBlock('search_result_list');
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
                'list' => __('Search List'),
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

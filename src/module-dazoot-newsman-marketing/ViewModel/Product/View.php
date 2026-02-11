<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\ViewModel\Product;

use Dazoot\Newsmanmarketing\ViewModel\Marketing;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;

/**
 * Product view model
 */
class View extends Marketing
{
    /**
     * Retrieve marketing data for the current product view.
     *
     * @param Product $product
     * @param Category|null $category
     * @return array
     */
    public function getMarketingData($product, $category = null)
    {
        $data = [
            'product' => [
                'id' => $this->escapeValue($product->getSku()),
                'name' => $this->escapeValue($product->getName()),
                'price' => $this->escapeValue($product->getFinalPrice())
            ]
        ];
        $brandCode = $this->config->getBrandAttribute();
        if (!empty($brandCode) && !empty($product->getData($brandCode))) {
            $data['product']['brand'] = $this->escapeValue($product->getAttributeText($brandCode));
        }

        if ($category) {
            $data['product']['category'] = $this->escapeValue($category->getName());
        }

        return $data;
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Helper\Customer;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Helper\Config\MapAbstract;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class AttributesMap extends MapAbstract
{
    /**
     * @var array
     */
    private $valueCache = [];

    /**
     * @param string|array $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!(is_array($row) &&
                array_key_exists('a', $row) &&
                array_key_exists('f', $row)
            )) {
                return false;
            }
        }
        return true;
    }

    /**
     * @see \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     * @throws LocalizedException
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $row) {
            $attribute = $row['a'];
            $field = $this->normalizeData($row['f']);
            if (empty($attribute) || empty($field)) {
                continue;
            }
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = [
                'a' => $attribute,
                'f' => $field
            ];
        }
        return $result;
    }

    /**
     * @see \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     * @throws LocalizedException
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!(is_array($row) &&
                array_key_exists('a', $row) &&
                array_key_exists('f', $row)
            )) {
                continue;
            }
            $attribute = $row['a'];
            $field = $this->normalizeData($row['f']);
            if (empty($attribute) || empty($field)) {
                continue;
            }
            $result[$this->mathRandom->getUniqueHash('_')] = [
                'a' => $attribute,
                'f' => $field
            ];
        }
        return $result;
    }

    /**
     * @param int|Store $store
     * @return array
     * @throws LocalizedException
     */
    public function getConfigValuebyStoreId($store)
    {
        if (is_object($store)) {
            $store = $store->getId();
        } else {
            $store = (int) $store;
        }

        $key = md5($store); // phpcs:ignore
        if (!isset($this->valueCache[$key])) {
            $value = $this->scopeConfig->getValue(
                Config::XML_PATH_EXPORT_CUSTOMER_ATTRIBUTES_MAP,
                ScopeInterface::SCOPE_STORE,
                $store
            );
            $value = $this->unserializeValue($value);
            if ($this->isEncodedArrayFieldValue($value)) {
                $value = $this->decodeArrayFieldValue($value);
            }
            $this->valueCache[$key] = $value;
        }
        return $this->valueCache[$key];
    }

    /**
     * @param string $value
     * @return string
     */
    public function normalizeData($value)
    {
        if ($value === null) {
            return $value;
        }
        $value = trim($value);
        return $value;
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isEmpty($store = null)
    {
        $value = $this->scopeConfig->getValue(
            Config::XML_PATH_EXPORT_CUSTOMER_ATTRIBUTES_MAP,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        return !(is_array($value) && count($value) > 0);
    }
}

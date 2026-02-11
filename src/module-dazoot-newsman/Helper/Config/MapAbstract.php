<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Helper\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;

abstract class MapAbstract
{
    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Map configuration path.
     *
     * @var string
     */
    public $path;

    /**
     * Map configuration prefix.
     *
     * @var string
     */
    public $prefix;

    /**
     * @param Json $serializer
     * @param Random $mathRandom
     * @param ScopeConfigInterface $scopeConfig
     * @param string $path
     * @param string $prefix
     */
    public function __construct(
        Json $serializer,
        Random $mathRandom,
        ScopeConfigInterface $scopeConfig,
        $path = null,
        $prefix = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
        $this->path = $path;
        $this->prefix = $prefix;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    protected function serializeValue($value)
    {
        if (is_array($value)) {
            return $this->serializer->serialize($value);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param string $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    abstract protected function isEncodedArrayFieldValue($value);

    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    abstract protected function encodeArrayFieldValue(array $value);

    /**
     * Decode value from used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    abstract protected function decodeArrayFieldValue(array $value);

    /**
     * Make value readable by \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param string|array $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);
        return $value;
    }

    /**
     * Normalize string value.
     *
     * @param string $value
     * @return string
     */
    public function normalizeValue($value)
    {
        if ($value === null) {
            return $value;
        }
        return trim($value);
    }
}

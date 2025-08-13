<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model;

use Magento\Config\Model\PreparedValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config for Newsman
 */
class Config
{
    /**
     * Active path
     */
    public const XML_PATH_ACTIVE = 'newsman/general/active';

    /**
     * Send User IP address path
     */
    public const XML_PATH_SEND_USER_IP = 'newsman/general/send_user_ip';

    /**
     * Server IP address path
     */
    public const XML_PATH_SERVER_IP = 'newsman/general/server_ip';

    /**
     * Order after date path
     */
    public const XML_PATH_ORDER_AFTER_DATE = 'newsman/general/order_after_date';

    /**
     * API URL
     */
    public const XML_PATH_API_URL = 'newsman/api/url';

    /**
     * API version
     */
    public const XML_PATH_API_VERSION = 'newsman/api/version';

    /**
     * API mass unsubscribe limit
     */
    public const XML_PATH_API_MASS_UNSUBSCRIBE_LIMIT = 'newsman/api/mass_unsubscribe_limit';

    /**
     * API export subscribers batch size
     */
    public const XML_PATH_API_EXPORT_SUBSCRIBERS_BATCH_SIZE = 'newsman/api/export_subscribers_batch_size';

    /**
     * Credentials User ID path
     */
    public const XML_PATH_CREDENTIALS_USER_ID = 'newsman/credentials/userId';

    /**
     * Credentials API Key path
     */
    public const XML_PATH_CREDENTIALS_API_KEY = 'newsman/credentials/apiKey';

    /**
     * Credentials API Timeout path
     */
    public const XML_PATH_CREDENTIALS_API_TIMEOUT = 'newsman/credentials/api_timeout';

    /**
     * Credentials list ID path
     */
    public const XML_PATH_CREDENTIALS_LIST_ID = 'newsman/credentials/listId';

    /**
     * Credentials segment ID path
     */
    public const XML_PATH_CREDENTIALS_SEGMENT_ID = 'newsman/credentials/segmentId';

    /**
     * Active developer send user IP
     */
    public const XML_PATH_DEVELOPER_ACTIVE_USER_IP = 'newsman/developer/active_user_ip';

    /**
     * User developer IP address to send
     */
    public const XML_PATH_DEVELOPER_USER_IP = 'newsman/developer/user_ip';

    /**
     * Log Mode path
     */
    public const XML_PATH_DEVELOPER_LOG_MODE = 'newsman/developer/log_mode';

    /**
     * Log Clean in days path
     */
    public const XML_PATH_DEVELOPER_LOG_CLEAN = 'newsman/developer/log_clean';

    /**
     * Export authorizationHTTP Header name path
     */
    public const XML_PATH_EXPORT_AUTHORIZE_HEADER_NAME = 'newsman/export/authorize_header_name';

    /**
     * Export authorizationHTTP Header value as secret key path
     */
    public const XML_PATH_EXPORT_AUTHORIZE_HEADER_KEY = 'newsman/export/authorize_header_key';

    /**
     * Export customer attributes map path
     */
    public const XML_PATH_EXPORT_CUSTOMER_ATTRIBUTES_MAP = 'newsman/export/customer_attributes_map';

    /**
     * Export product attributes map path
     */
    public const XML_PATH_EXPORT_PRODUCT_ATTRIBUTES_MAP = 'newsman/export/product_attributes_map';

    /**
     * Customer send telephone path
     */
    public const XML_PATH_EXPORT_CUSTOMER_SEND_TELEPHONE = 'newsman/export/customer_send_telephone';

    /**
     * Order send telephone path
     */
    public const XML_PATH_EXPORT_ORDER_SEND_TELEPHONE = 'newsman/export/order_send_telephone';

    /**
     * Newsletter send subscribe and unsubscribe emails from Newsman
     */
    public const XML_PATH_NEWSLETTER_NEWSMAN_SENDS_SUB = 'newsman/newsletter/send_subscribe_newsman';

    /**
     * Placeholder for lists config path by user ID
     */
    public const XML_PATH_STORED_LISTS_PLACEHOLDER = 'newsman/stored/lists_%s';

    /**
     * Placeholder for segments config path by user ID
     */
    public const XML_PATH_STORED_SEGMENTS_PLACEHOLDER = 'newsman/stored/segments_%s';

    /**
     * Store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PreparedValueFactory
     */
    protected $valueFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var int|null
     */
    protected $logMode;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PreparedValueFactory $valueFactory
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PreparedValueFactory $valueFactory,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->valueFactory = $valueFactory;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * Is active
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isActive($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Is send user IP address
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isSendUserIp($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SEND_USER_IP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get server IP address
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getServerIp($store = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SERVER_IP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get server IP address
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getOrderAfterDate($store = null)
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_AFTER_DATE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get API URL
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getApiUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get API Version
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getApiVersion($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_VERSION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get API mass unsubscribe limit
     *
     * @return int
     */
    public function getApiMassUnsubscribeLimit()
    {
        $limit = (int) $this->scopeConfig->getValue(
            self::XML_PATH_API_MASS_UNSUBSCRIBE_LIMIT,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        return max(min($limit, 1000), 1);
    }

    /**
     * Get API export subscribers batch size
     *
     * @return int
     */
    public function getApiExportSubscribersBatchSize()
    {
        $limit = (int) $this->scopeConfig->getValue(
            self::XML_PATH_API_EXPORT_SUBSCRIBERS_BATCH_SIZE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        return max(min($limit, 10000), 100);
    }

    /**
     * Get User ID
     *
     * @param null|string|bool|int|Store $store
     * @return int
     */
    public function getUserId($store = null)
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CREDENTIALS_USER_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get API Key
     *
     * @param null|string|bool|int|Store $store
     * @param null|string|bool|int|Website $website
     * @param bool $fromDefault
     * @return string
     */
    public function getApiKey($store = null, $website = null, $fromDefault = false)
    {
        $scopeType = ScopeInterface::SCOPE_STORE;
        $scopeCode = $store;
        if ($store === null && $website !== null) {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeCode = $website->getId();
        } elseif ($store === null && $fromDefault) {
            return (int) $this->scopeConfig->getValue(
                self::XML_PATH_CREDENTIALS_API_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        return $this->scopeConfig->getValue(
            self::XML_PATH_CREDENTIALS_API_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get API Timeout
     *
     * @param null|string|bool|int|Store $store
     * @return int
     */
    public function getApiTimeout($store = null)
    {
        $timeout = $this->scopeConfig->getValue(
            self::XML_PATH_CREDENTIALS_API_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($timeout < 5) {
            return 5;
        }
        return (int) $timeout;
    }

    /**
     * Get List ID
     *
     * @param null|string|bool|int|Store $store
     * @param null|string|bool|int|Website $website
     * @param bool $fromDefault
     * @return int
     */
    public function getListId($store = null, $website = null, $fromDefault = false)
    {
        $scopeType = ScopeInterface::SCOPE_STORE;
        $scopeCode = $store;
        if ($store === null && $website !== null) {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeCode = $website->getId();
        } elseif ($store === null && $fromDefault) {
            return (int) $this->scopeConfig->getValue(
                self::XML_PATH_CREDENTIALS_LIST_ID,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CREDENTIALS_LIST_ID,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get Segment ID
     *
     * @param null|string|bool|int|Store $store
     * @return int
     */
    public function getSegmentId($store = null)
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CREDENTIALS_SEGMENT_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get export authorize HTTP header name
     *
     * @param null|string|bool|int|Store $store
     * @return int
     */
    public function getExportAuthorizeHeaderName($store = null)
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_EXPORT_AUTHORIZE_HEADER_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Get export authorize HTTP header name
     *
     * @param null|string|bool|int|Store $store
     * @return int
     */
    public function getExportAuthorizeHeaderKey($store = null)
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_EXPORT_AUTHORIZE_HEADER_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Is customer send telephone
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isCustomerSendTelephone($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EXPORT_CUSTOMER_SEND_TELEPHONE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param array $storeIds
     * @return bool
     */
    public function isCustomerSendTelephoneByStoreIds($storeIds)
    {
        if (empty($storeIds)) {
            return $this->isCustomerSendTelephone();
        }

        foreach ($storeIds as $storeId) {
            if ($this->isCustomerSendTelephone($storeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is order send telephone
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isOrderSendTelephone($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EXPORT_ORDER_SEND_TELEPHONE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param array $storeIds
     * @return bool
     */
    public function isOrderSendTelephoneByStoreIds($storeIds)
    {
        if (empty($storeIds)) {
            return $this->isOrderSendTelephone();
        }

        foreach ($storeIds as $storeId) {
            if ($this->isOrderSendTelephone($storeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is Newsletter Newsman Send Subscribe and Unsubscribe emails
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isNewsletterNewsmanSendSub($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_NEWSLETTER_NEWSMAN_SENDS_SUB,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Is Magento double opt-in activated
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isDoubleOptIn($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            Subscriber::XML_PATH_CONFIRMATION_FLAG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Is active developer send user IP
     *
     * @return bool
     */
    public function isDeveloperActiveUserIp()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEVELOPER_ACTIVE_USER_IP,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get developer user IP address to send
     *
     * @return string
     */
    public function getDeveloperUserIp()
    {
        if (!$this->isDeveloperActiveUserIp()) {
            return '';
        }

        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DEVELOPER_USER_IP,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get developer log mode
     *
     * @return int
     */
    public function getLogMode()
    {
        if ($this->logMode !== null) {
            return $this->logMode;
        }
        $this->logMode = (int) $this->scopeConfig->getValue(
            self::XML_PATH_DEVELOPER_LOG_MODE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        return $this->logMode;
    }

    /**
     * Get developer log clean in days
     *
     * @return int
     */
    public function getLogClean()
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_DEVELOPER_LOG_CLEAN,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Is features enabled
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        if (!$this->isActive($store)) {
            return false;
        }

        if (!$this->hasApiAccess($store)) {
            return false;
        }

        if (empty($this->getListId($store))) {
            return false;
        }

        return true;
    }

    /**
     * Is features enabled on any store
     *
     * @return bool
     */
    public function isEnabledInAny()
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getIsActive() && $this->isEnabled($store)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Has user ID and API key
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function hasApiAccess($store = null)
    {
        return !empty($this->getUserId($store)) && !empty($this->getApiKey($store));
    }

    /**
     * @param int|string $userId
     * @return string
     */
    public function getStoredListsPath($userId)
    {
        return sprintf(self::XML_PATH_STORED_LISTS_PLACEHOLDER, $userId);
    }

    /**
     * @param int|string $userId
     * @param array $data
     * @return void
     */
    public function saveStoredLists($userId, $data)
    {
        $this->valueFactory->create(
            $this->getStoredListsPath($userId),
            $this->serializer->serialize($data),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        )->save();
    }

    /**
     * @param int|string $userId
     * @return string
     */
    public function getStoredSegmentsPath($userId)
    {
        return sprintf(self::XML_PATH_STORED_SEGMENTS_PLACEHOLDER, $userId);
    }

    /**
     * @param int|string $userId
     * @param array $data
     * @return void
     */
    public function saveStoredSegments($userId, $data)
    {
        $this->valueFactory->create(
            $this->getStoredSegmentsPath($userId),
            $this->serializer->serialize($data),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        )->save();
    }

    /**
     * @return array
     */
    public function getStoredLists()
    {
        $lists = [];
        foreach ($this->getAllUserIds() as $userId) {
            $listStr = $this->scopeConfig->getValue(
                $this->getStoredListsPath($userId),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
            $lists[$userId] = [];
            if (empty($listStr)) {
                continue;
            }
            try {
                $list = $this->serializer->unserialize($listStr);
                if (is_array($list) && !empty($list)) {
                    $lists[$userId] = $list;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $lists;
    }

    /**
     * @return array
     */
    public function getStoredSegments()
    {
        $segments = [];
        foreach ($this->getAllUserIds() as $userId) {
            $segmentStr = $this->scopeConfig->getValue(
                $this->getStoredSegmentsPath($userId),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
            $segments[$userId] = [];
            if (empty($segmentStr)) {
                continue;
            }
            try {
                $segmentsData = $this->serializer->unserialize($segmentStr);
                if (is_array($segmentsData) && !empty($segmentsData)) {
                    reset($segmentsData);
                    // The first key is userId and the value is all segments by lists IDs
                    $segments[$userId] = current($segmentsData);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $segments;
    }

    /**
     * @return array
     */
    public function getAllUserIds()
    {
        $userIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }
            $userIds[] = $this->getUserId($store);
        }
        return array_unique($userIds);
    }

    /**
     * @param int $listId
     * @return array
     */
    public function getStoreIdsByListId($listId)
    {
        if (empty($listId)) {
            return [];
        }
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->getListId($store) === $listId && $this->isEnabled($store)) {
                $storeIds[] = $store->getId();
            };
        }
        return $storeIds;
    }

    /**
     * @param array $storeIds
     * @return array
     */
    public function getUserIdsByStoreIds($storeIds)
    {
        $userIds = [];
        foreach ($storeIds as $storeId) {
            $userIds[] = $this->getUserId($storeId);
        }
        return array_unique($userIds);
    }

    /**
     * @return array
     */
    public function getAllListIds()
    {
        $listIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            $listIds[] = $this->getListId($store);
        }
        $listIds = array_unique($listIds);

        $return = [];
        foreach ($listIds as $listId) {
            $storeIds = $this->getStoreIdsByListId($listId);
            if (!empty($storeIds)) {
                $return[] = $listId;
            }
        }

        return $return;
    }

    /**
     * @param string $apiKey
     * @return array
     */
    public function getStoreIdsByApiKey($apiKey)
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->getApiKey($store) === $apiKey && $this->isEnabled($store)) {
                $storeIds[] = $store->getId();
            };
        }
        return $storeIds;
    }
}

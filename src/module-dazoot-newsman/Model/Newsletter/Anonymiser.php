<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Newsletter;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Api\ErrorCode\Anonymise as AnonymiseError;
use Dazoot\Newsman\Model\Api\ErrorCode\Unsubscribe as UnsubscribeError;
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Service\AnonymiseEmail;
use Dazoot\Newsman\Model\Service\Context\AnonymiseEmailContextFactory;
use Dazoot\Newsman\Model\Service\Context\UnsubscribeEmailContextFactory;
use Dazoot\Newsman\Model\Service\UnsubscribeEmail;
use Dazoot\Newsman\Model\User\HostIpAddress;
use Magento\Customer\Model\Config\Share as ShareConfig;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Anonymise an email address in the Newsman lists of the given store views after the person is deleted in Magento.
 *
 * The email is anonymised whatever its newsletter status in Magento, because the contact can be in Newsman with
 * personal data from a subscription, a customer or an order export. When "Anonymise in Newsman when customers are
 * deleted" is No for the store view, the email is unsubscribed from its Newsman list instead (subscriber
 * saveUnsubscribe, no confirmation email). The action is decided per Newsman list: when store views with different
 * values of the setting use the same list, the email is unsubscribed from it (the less destructive action wins).
 *
 * One API call is made per distinct Newsman list and email in a request (process): a customer delete also deletes
 * the newsletter subscribers of that customer, and store views can share a list. A Newsman error is logged and never
 * stops the delete.
 *
 * @see \Dazoot\Newsman\Observer\Customer\DeleteAnonymiseObserver
 * @see \Dazoot\Newsman\Observer\Newsletter\DeleteAnonymiseObserver
 * @see \Dazoot\Newsman\Model\Newsletter\Bulk\Delete\Consumer
 */
class Anonymiser
{
    /**
     * Anonymise the email in the Newsman list
     */
    public const ACTION_ANONYMISE = 'anonymise';

    /**
     * Unsubscribe the email from the Newsman list
     */
    public const ACTION_UNSUBSCRIBE = 'unsubscribe';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ShareConfig
     */
    protected $shareConfig;

    /**
     * @var AnonymiseEmail
     */
    protected $anonymiseEmail;

    /**
     * @var AnonymiseEmailContextFactory
     */
    protected $anonymiseEmailContextFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var UnsubscribeEmail
     */
    protected $unsubscribeEmail;

    /**
     * @var UnsubscribeEmailContextFactory
     */
    protected $unsubscribeEmailContextFactory;

    /**
     * @var HostIpAddress
     */
    protected $ipAddress;

    /**
     * Emails already sent in this request, by Newsman list: [user ID|list ID => [lowercase email => true]]
     *
     * @var array
     */
    protected $sent = [];

    /**
     * @var int
     */
    protected $suspended = 0;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ShareConfig $shareConfig
     * @param AnonymiseEmail $anonymiseEmail
     * @param AnonymiseEmailContextFactory $anonymiseEmailContextFactory
     * @param Config $config
     * @param Logger $logger
     * @param UnsubscribeEmail $unsubscribeEmail
     * @param UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory
     * @param HostIpAddress $ipAddress
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ShareConfig $shareConfig,
        AnonymiseEmail $anonymiseEmail,
        AnonymiseEmailContextFactory $anonymiseEmailContextFactory,
        Config $config,
        Logger $logger,
        UnsubscribeEmail $unsubscribeEmail,
        UnsubscribeEmailContextFactory $unsubscribeEmailContextFactory,
        HostIpAddress $ipAddress
    ) {
        $this->storeManager = $storeManager;
        $this->shareConfig = $shareConfig;
        $this->anonymiseEmail = $anonymiseEmail;
        $this->anonymiseEmailContextFactory = $anonymiseEmailContextFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->unsubscribeEmail = $unsubscribeEmail;
        $this->unsubscribeEmailContextFactory = $unsubscribeEmailContextFactory;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Anonymise the email in the distinct Newsman lists of the store views where the extension is enabled
     *
     * In the lists where "Anonymise in Newsman when customers are deleted" is No, the email is unsubscribed instead.
     *
     * @param string $email
     * @param array $storeIds
     * @param \Magento\Framework\Phrase|string|null $message Logged before the first anonymise API call
     * @param \Magento\Framework\Phrase|string|null $unsubscribeMessage Logged before the first unsubscribe API call
     * @return int Number of API calls made
     */
    public function anonymise($email, array $storeIds, $message = null, $unsubscribeMessage = null)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return 0;
        }

        try {
            $listStores = $this->getListStores($storeIds);
        } catch (\Throwable $e) {
            $this->logError($email, '', $e);
            return 0;
        }

        $emailKey = strtolower($email);
        $toSend = [];
        foreach ($listStores as $listKey => $store) {
            if (!isset($this->sent[$listKey][$emailKey])) {
                $toSend[$listKey] = $store;
            }
        }
        if (empty($toSend)) {
            return 0;
        }

        if ($this->isSuspended()) {
            $this->logInfo(
                __(
                    'Customer account creation was rolled back in Magento, email %1 is not anonymised or unsubscribed',
                    $email
                )
            );
            return 0;
        }

        $logged = [];
        foreach ($toSend as $listKey => $store) {
            $this->sent[$listKey][$emailKey] = true;
            $action = $this->getDeleteAction($store);
            $actionMessage = $action === self::ACTION_UNSUBSCRIBE ? $unsubscribeMessage : $message;
            if ($actionMessage !== null && !isset($logged[$action])) {
                $logged[$action] = true;
                $this->logInfo($actionMessage);
            }
            if ($action === self::ACTION_UNSUBSCRIBE) {
                $this->unsubscribe($email, $store);
            } else {
                $this->send($email, $store);
            }
        }

        return count($toSend);
    }

    /**
     * Get what is done in the Newsman list of the store view when a customer or a newsletter subscriber is deleted
     *
     * Anonymise, unless "Anonymise in Newsman when customers are deleted" is No for the store view or for another
     * store view where the extension is enabled and that uses the same Newsman list: then unsubscribe.
     *
     * @param StoreInterface|int|string $store
     * @return string self::ACTION_ANONYMISE or self::ACTION_UNSUBSCRIBE
     */
    public function getDeleteAction($store)
    {
        try {
            $store = $this->storeManager->getStore($store);
            if (!$this->config->isNewsletterAnonymiseOnDelete($store)) {
                return self::ACTION_UNSUBSCRIBE;
            }

            $listKey = $this->getListKey($store);
            foreach ($this->storeManager->getStores() as $otherStore) {
                if ((int) $otherStore->getId() === (int) $store->getId()
                    || $this->config->isNewsletterAnonymiseOnDelete($otherStore)
                    || !$this->config->isEnabled($otherStore)
                ) {
                    continue;
                }
                if ($this->getListKey($otherStore) === $listKey) {
                    return self::ACTION_UNSUBSCRIBE;
                }
            }
        } catch (\Throwable $e) {
            return self::ACTION_UNSUBSCRIBE;
        }

        return self::ACTION_ANONYMISE;
    }

    /**
     * Get the store view IDs where a customer of the website can have data
     *
     * All store views when customer accounts are shared globally, else the store views of the customer's website.
     *
     * @param int|string|null $websiteId
     * @return array
     */
    public function getCustomerStoreIds($websiteId)
    {
        try {
            if ($this->shareConfig->isGlobalScope()) {
                return array_keys($this->storeManager->getStores());
            }
            if ((int) $websiteId <= 0) {
                return [];
            }
            return array_values((array) $this->storeManager->getWebsite((int) $websiteId)->getStoreIds());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Do not anonymise or unsubscribe until resume() is called (a delete that rolls back a failed account creation)
     *
     * @return void
     */
    public function suspend()
    {
        $this->suspended++;
    }

    /**
     * Anonymise or unsubscribe again after suspend()
     *
     * @return void
     */
    public function resume()
    {
        if ($this->suspended > 0) {
            $this->suspended--;
        }
    }

    /**
     * Is anonymise or unsubscribe suspended
     *
     * @return bool
     */
    public function isSuspended()
    {
        return $this->suspended > 0;
    }

    /**
     * Forget the emails sent so far (a long running process starts a new unit of work)
     *
     * @return void
     */
    public function reset()
    {
        $this->sent = [];
    }

    /**
     * Get one store view per distinct Newsman list, only store views where the extension is enabled
     *
     * @param array $storeIds
     * @return StoreInterface[] [user ID|list ID => store]
     */
    protected function getListStores(array $storeIds)
    {
        $listStores = [];
        foreach (array_unique(array_map('intval', $storeIds)) as $storeId) {
            if ($storeId <= 0) {
                continue;
            }
            try {
                $store = $this->storeManager->getStore($storeId);
            } catch (\Exception $e) {
                continue;
            }
            if (!$this->config->isEnabled($store)) {
                continue;
            }
            $listKey = $this->getListKey($store);
            if (!isset($listStores[$listKey])) {
                $listStores[$listKey] = $store;
            }
        }

        return $listStores;
    }

    /**
     * Get the key of the Newsman list of the store view: user ID|list ID
     *
     * @param StoreInterface $store
     * @return string
     */
    protected function getListKey($store)
    {
        return $this->config->getUserId($store) . '|' . $this->config->getListId($store);
    }

    /**
     * Anonymise the email in the Newsman list of the store view
     *
     * @param string $email
     * @param StoreInterface $store
     * @return void
     */
    protected function send($email, $store)
    {
        $listId = '';
        try {
            $listId = $this->config->getListId($store);
            $this->anonymiseEmail->execute(
                $this->anonymiseEmailContextFactory->create()
                    ->setEmail($email)
                    ->setStore($store)
            );
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === AnonymiseError::NO_SUCH_SUBSCRIBER) {
                $this->logInfo(__('Email %1 is not on the Newsman list %2, nothing to anonymise', $email, $listId));
                return;
            }
            $this->logError($email, $listId, $e);
        }
    }

    /**
     * Unsubscribe the email from the Newsman list of the store view, without a confirmation email
     *
     * @param string $email
     * @param StoreInterface $store
     * @return void
     */
    protected function unsubscribe($email, $store)
    {
        $listId = '';
        try {
            $listId = $this->config->getListId($store);
            $this->logInfo(
                __('Unsubscribe email %1 from the Newsman list %2 (anonymise on delete is No)', $email, $listId)
            );
            $this->unsubscribeEmail->execute(
                $this->unsubscribeEmailContextFactory->create()
                    ->setEmail($email)
                    ->setStore($store)
                    ->setIp($this->ipAddress->getIp())
            );
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === UnsubscribeError::NO_SUCH_SUBSCRIBER) {
                $this->logInfo(__('Email %1 is not on the Newsman list %2, nothing to unsubscribe', $email, $listId));
                return;
            }
            $this->logError($email, $listId, $e, self::ACTION_UNSUBSCRIBE);
        }
    }

    /**
     * Log an info message without letting a logger error reach the delete
     *
     * @param \Magento\Framework\Phrase|string $message
     * @return void
     */
    protected function logInfo($message)
    {
        try {
            $this->logger->info($message);
        } catch (\Throwable $e) {
            return;
        }
    }

    /**
     * Log a failed anonymise or unsubscribe without letting a logger error reach the delete
     *
     * @param string $email
     * @param int|string $listId
     * @param \Throwable $exception
     * @param string $action
     * @return void
     */
    protected function logError($email, $listId, $exception, $action = self::ACTION_ANONYMISE)
    {
        try {
            $this->logger->error(
                $action === self::ACTION_UNSUBSCRIBE
                    ? __(
                        'Could not unsubscribe email %1 from the Newsman list %2 after the delete: %3',
                        $email,
                        $listId,
                        $exception->getMessage()
                    )
                    : __(
                        'Could not anonymise email %1 in the Newsman list %2 after the delete: %3',
                        $email,
                        $listId,
                        $exception->getMessage()
                    )
            );
        } catch (\Throwable $e) {
            return;
        }
    }
}

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
use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Service\AnonymiseEmail;
use Dazoot\Newsman\Model\Service\Context\AnonymiseEmailContextFactory;
use Magento\Customer\Model\Config\Share as ShareConfig;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Anonymise an email address in the Newsman lists of the given store views after the person is deleted in Magento.
 *
 * The email is anonymised whatever its newsletter status in Magento, because the contact can be in Newsman with
 * personal data from a subscription, a customer or an order export. One API call is made per distinct Newsman list
 * and email in a request (process): a customer delete also deletes the newsletter subscribers of that customer, and
 * store views can share a list. A Newsman error is logged and never stops the delete.
 *
 * @see \Dazoot\Newsman\Observer\Customer\DeleteAnonymiseObserver
 * @see \Dazoot\Newsman\Observer\Newsletter\DeleteAnonymiseObserver
 * @see \Dazoot\Newsman\Model\Newsletter\Bulk\Delete\Consumer
 */
class Anonymiser
{
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
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ShareConfig $shareConfig,
        AnonymiseEmail $anonymiseEmail,
        AnonymiseEmailContextFactory $anonymiseEmailContextFactory,
        Config $config,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->shareConfig = $shareConfig;
        $this->anonymiseEmail = $anonymiseEmail;
        $this->anonymiseEmailContextFactory = $anonymiseEmailContextFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Anonymise the email in the distinct Newsman lists of the store views where the extension is enabled
     *
     * @param string $email
     * @param array $storeIds
     * @param \Magento\Framework\Phrase|string|null $message Logged before the first API call
     * @return int Number of API calls made
     */
    public function anonymise($email, array $storeIds, $message = null)
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
                    'Customer account creation was rolled back in Magento, email %1 is not anonymised in Newsman',
                    $email
                )
            );
            return 0;
        }

        if ($message !== null) {
            $this->logInfo($message);
        }

        foreach ($toSend as $listKey => $store) {
            $this->sent[$listKey][$emailKey] = true;
            $this->send($email, $store);
        }

        return count($toSend);
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
     * Do not anonymise until resume() is called (a customer delete that only rolls back a failed account creation)
     *
     * @return void
     */
    public function suspend()
    {
        $this->suspended++;
    }

    /**
     * Anonymise again after suspend()
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
     * Is anonymise suspended
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
            $listKey = $this->config->getUserId($store) . '|' . $this->config->getListId($store);
            if (!isset($listStores[$listKey])) {
                $listStores[$listKey] = $store;
            }
        }

        return $listStores;
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
     * Log a failed anonymise without letting a logger error reach the delete
     *
     * @param string $email
     * @param int|string $listId
     * @param \Throwable $exception
     * @return void
     */
    protected function logError($email, $listId, $exception)
    {
        try {
            $this->logger->error(
                __(
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

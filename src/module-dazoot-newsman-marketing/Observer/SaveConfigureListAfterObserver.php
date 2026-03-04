<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
declare(strict_types=1);

namespace Dazoot\Newsmanmarketing\Observer;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Config as NewsmanConfig;
use Dazoot\Newsmanmarketing\Model\Config;
use Dazoot\Newsmanmarketing\Model\Service\Configuration\GetSettings;
use Dazoot\Newsmanmarketing\Model\Service\Context\GetSettingsContext;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Fetch remarketing settings and save the remarketing ID after a list is configured.
 */
class SaveConfigureListAfterObserver implements ObserverInterface
{
    /**
     * @var NewsmanConfig
     */
    protected $newsmanConfig;

    /**
     * @var GetSettings
     */
    protected $getSettingsService;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param NewsmanConfig $newsmanConfig
     * @param GetSettings $getSettingsService
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     */
    public function __construct(
        NewsmanConfig $newsmanConfig,
        GetSettings $getSettingsService,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->newsmanConfig = $newsmanConfig;
        $this->getSettingsService = $getSettingsService;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
    }

    /**
     * Retrieve remarketing settings and persist the remarketing ID for the configured list.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $listId = $observer->getEvent()->getData('list_id');
        $storeModel = $observer->getEvent()->getData('store');
        $scope = $observer->getEvent()->getData('scope');
        $scopeId = $observer->getEvent()->getData('scope_id');

        $userId = $this->newsmanConfig->getUserId($storeModel);
        $apiKey = $this->newsmanConfig->getApiKey($storeModel);

        if (empty($userId) || empty($apiKey) || empty($listId)) {
            return;
        }

        try {
            $context = new GetSettingsContext();
            $context->setUserId($userId)
                ->setApiKey($apiKey)
                ->setListId($listId);

            $settings = $this->getSettingsService->execute($context);

            if (!empty($settings) && is_array($settings)) {
                $remarketingId = $settings['site_id'] . '-' . $settings['list_id'] . '-' .
                    $settings['form_id'] . '-' . $settings['control_list_hash'];

                $this->configWriter->save(Config::XML_PATH_UA_ID, $remarketingId, $scope, $scopeId);
                $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

                $this->logger->info(__(
                    'Remarketing ID %1 saved for list %2.',
                    $remarketingId,
                    $listId
                ));
            }
        } catch (\Exception $e) {
            $this->logger->error('SaveConfigureListAfterObserver: ' . $e->getMessage());
        }
    }
}

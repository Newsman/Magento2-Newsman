<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Controller\Adminhtml\System\Config;

use Dazoot\Newsman\Model\Config;
use Dazoot\Newsman\Model\Service\Configuration\GetListAll;
use Dazoot\Newsman\Model\Service\Configuration\GetSegments;
use Dazoot\Newsman\Model\Service\Context\Configuration\ListContextFactory;
use Dazoot\Newsman\Model\Service\Context\Configuration\UserContextFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;

/**
 * Import lists and segments from Newsman in config
 */
class ImportListSegment extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dazoot_Newsman::config_newsman';

    /**
     * Factory for building JSON results.
     *
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * HTML tag filter.
     *
     * @var StripTags
     */
    private $tagFilter;

    /**
     * Service to retrieve all Newsman newsletter lists.
     *
     * @var GetListAll
     */
    protected $getListAll;

    /**
     * Factory for building API user contexts.
     *
     * @var UserContextFactory
     */
    protected $userContextFactory;

    /**
     * Service to retrieve Newsman segments.
     *
     * @var GetSegments
     */
    protected $getSegments;

    /**
     * Factory for building list context objects.
     *
     * @var ListContextFactory
     */
    protected $listContextFactory;

    /**
     * Newsman module configuration helper.
     *
     * @var Config
     */
    protected $config;

    /**
     * Cache type list for cleaning config cache.
     *
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * ImportListSegment constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param GetListAll $getListAll
     * @param UserContextFactory $userContextFactory
     * @param GetSegments $getSegments
     * @param ListContextFactory $listContextFactory
     * @param Config $config
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        StripTags $tagFilter,
        GetListAll $getListAll,
        UserContextFactory $userContextFactory,
        GetSegments $getSegments,
        ListContextFactory $listContextFactory,
        Config $config,
        TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tagFilter = $tagFilter;
        $this->getListAll = $getListAll;
        $this->userContextFactory = $userContextFactory;
        $this->getSegments = $getSegments;
        $this->listContextFactory = $listContextFactory;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Check for connection to server
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => ''
        ];
        $resultJson = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParams();

        if (empty($params['userId'])) {
            $result['errorMessage'] = __('Please enter a valid Newsman user ID.');
            return $resultJson->setData($result);
        }
        $userId = $params['userId'];

        $apiKey = $params['apiKey'];
        // User didn't set an apy key in the input type password. Load it from the configuration.
        if (stripos($params['apiKey'], '***') !== false) {
            if (isset($params['store']) && !empty($params['store'])) {
                $apiKey = $this->config->getApiKey($params['store']);
            } elseif (isset($params['website']) && !empty($params['website'])) {
                $apiKey = $this->config->getApiKey(null, $params['website']);
            } else {
                $apiKey = $this->config->getApiKey();
            }
        }

        if (empty($apiKey)) {
            $result['errorMessage'] = __('Please enter a valid Newsman API key.');
            return $resultJson->setData($result);
        }

        try {
            $listsData = $this->getListAll->execute(
                $this->userContextFactory->create()
                    ->setUserId($userId)
                    ->setApiKey($apiKey)
            );
            $segmentsData = [$userId => []];
            if (!empty($listsData)) {
                foreach ($listsData as $listItem) {
                    $segmentsData[$userId][$listItem['list_id']] = $this->getSegments->execute(
                        $this->listContextFactory->create()
                            ->setUserId($userId)
                            ->setApiKey($apiKey)
                            ->setListId($listItem['list_id'])
                    );
                }
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $this->tagFilter->filter($e->getMessage() . ' ' . $e->getLogMessage());
            return $resultJson->setData($result);
        }

        try {
            $this->config->saveStoredLists($userId, $listsData);
            $this->config->saveStoredSegments($userId, $segmentsData);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
            return $resultJson->setData($result);
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'success' => true,
            'lists' => $listsData,
            'segments' => $segmentsData
        ]);
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

use Dazoot\Newsman\Logger\Logger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\SalesRule\Model\Rule\Condition\Address as ConditionAddress;
use Magento\SalesRule\Model\Rule\Condition\AddressFactory as ConditionAddressFactory;
use Dazoot\Newsman\Model\Export\Retriever\V1\ApiV1Exception;

/**
 * Add coupons
 */
class Coupons extends AbstractRetriever implements RetrieverInterface
{
    /**
     * @var CouponFactory
     */
    protected $couponFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var ConditionAddressFactory
     */
    protected $conditionAddressFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param CouponFactory $couponFactory
     * @param RuleFactory $ruleFactory
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param GroupManagementInterface $groupManagement
     * @param ConditionAddressFactory $conditionAddressFactory
     * @param Logger $logger
     */
    public function __construct(
        CouponFactory $couponFactory,
        RuleFactory $ruleFactory,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        GroupManagementInterface $groupManagement,
        ConditionAddressFactory $conditionAddressFactory,
        Logger $logger
    ) {
        $this->couponFactory = $couponFactory;
        $this->ruleFactory = $ruleFactory;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->groupManagement = $groupManagement;
        $this->conditionAddressFactory = $conditionAddressFactory;
        $this->logger = $logger;
    }

    /**
     * Process coupon generation and save based on provided data and store IDs.
     *
     * @param array $data
     * @param array $storeIds
     * @return array
     * @throws ApiV1Exception On invalid parameters in API v1 context.
     */
    public function process($data = [], $storeIds = [])
    {
        $this->logger->info(__('Add coupons: %1', json_encode($data)));

        $isV1 = isset($data['_v1_filter_fields']);

        if (!isset($data['type'])) {
            if ($isV1) {
                throw new ApiV1Exception(8001, 'Missing "type" parameter', 400);
            }
            $this->logger->error(__('Missing type param'));
            return ['status' => 0, 'msg' => 'Missing type param'];
        }
        $discountType = (int) $data['type'];
        if (!in_array($discountType, [0, 1], true)) {
            if ($isV1) {
                throw new ApiV1Exception(8002, 'Invalid "type" parameter: must be 0 (fixed) or 1 (percent)', 400);
            }
            $this->logger->error(__('Invalid type param'));
            return ['status' => 0, 'msg' => 'Invalid type param'];
        }
        if (!isset($data['value'])) {
            if ($isV1) {
                throw new ApiV1Exception(8003, 'Missing "value" parameter', 400);
            }
            $this->logger->error(__('Missing value param'));
            return ['status' => 0, 'msg' => 'Missing value param'];
        }
        $value = (float) $data['value'];
        if ($value <= 0) {
            if ($isV1) {
                throw new ApiV1Exception(8004, 'Invalid "value" parameter: must be greater than 0', 400);
            }
            $this->logger->error(__('Invalid value param'));
            return ['status' => 0, 'msg' => 'Invalid value param'];
        }
        $batchSize = (int) (!empty($data['batch_size']) ? $data['batch_size'] : 1);
        if ($batchSize < 1) {
            if ($isV1) {
                throw new ApiV1Exception(8005, 'Invalid "batch_size" parameter: must be >= 1', 400);
            }
        }
        $expireDate = isset($data['expire_date']) ? $data['expire_date'] : null;
        if ($expireDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expireDate)) {
            if ($isV1) {
                throw new ApiV1Exception(8006, 'Invalid "expire_date" format: expected YYYY-MM-DD', 400);
            }
        }
        $prefix = (string) (!empty($data['prefix']) ? $data['prefix'] : '');
        //$currency = (string) (!empty($data['currency']) ? $data['currency'] : 'RON');

        $websiteIds = [];
        foreach ($storeIds as $storeId) {
            $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
        }
        $websiteIds = array_unique($websiteIds);

        $groupIds = [];
        $groups = $this->groupManagement->getLoggedInGroups();
        foreach ($groups as $group) {
            $groupIds[] = $group->getId();
        }
        $groupIds[] = $this->groupManagement->getNotLoggedInGroup()->getId();

        $count = 0;
        $couponsList = [];
        for ($step = 0; $step < $batchSize; $step++) {
            try {
                $couponsList[] = $this->saveCoupon(
                    $data,
                    $discountType,
                    $value,
                    $prefix,
                    $websiteIds,
                    $groupIds,
                    $expireDate
                );
                $count++;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        $this->logger->info(__('Added %1 coupons: %2', $count, implode(", ", $couponsList)));

        if (empty($couponsList)) {
            $this->logger->error(__('Something went wrong creating coupons'));
            if ($isV1) {
                throw new ApiV1Exception(8007, 'Failed to create coupons', 500);
            }
            return ['status' => 0, 'codes' => 'Something went wrong creating coupons'];
        }
        return ['status' => 1, 'codes' => $couponsList];
    }

    /**
     * Create and save a new coupon rule and code.
     *
     * @param array $data
     * @param int $discountType
     * @param float $value
     * @param string $prefix
     * @param array $websiteIds
     * @param array $groupIds
     * @param string|null $expireDate
     * @return string
     * @throws \Exception
     */
    public function saveCoupon($data, $discountType, $value, $prefix, $websiteIds, $groupIds, $expireDate = null)
    {
        $coupon = $this->couponFactory->create();
        $characters = $this->getCouponCharacters();

        if ($discountType == 1) {
            $coupon->setDiscountType('by_percent');
        } else {
            $coupon->setDiscountType('cart_fixed');
        }

        do {
            $couponCode = '';
            for ($i = 0; $i < 8; $i++) {
                $couponCode .= $characters[rand(0, strlen($characters) - 1)];
            }
            $fullCouponCode = $prefix . $couponCode;
            /** @var Coupon $existingCoupon */
            $existingCoupon = $this->couponFactory->create()
                ->loadByCode($fullCouponCode);
        } while ($existingCoupon->getId() > 0);

        /** @var Rule $rule */
        $rule = $this->ruleFactory->create();
        $rule->setName($this->getRuleName($fullCouponCode))
            ->setWebsiteIds(implode(", ", $websiteIds))
            ->setCustomerGroupIds(implode(", ", $groupIds))
            ->setDescription($this->getCouponDescription($fullCouponCode, $value))
            ->setCouponType(Rule::COUPON_TYPE_SPECIFIC)
            ->setSimpleAction(($discountType == 1) ? 'by_percent' : 'cart_fixed')
            ->setCouponCode($fullCouponCode)
            ->setDiscountAmount($value)
            ->setFromDate($this->timezone->date()->format('Y-m-d'))
            ->setToDate($expireDate)
            ->setUsesPerCoupon(1)
            ->setUsesPerCustomer(1)
            ->setIsActive(true);

        $this->addCondition($rule, $data);

        $rule->save();

        return $rule->getCouponCode();
    }

    /**
     * Add condition to the sales rule.
     *
     * @param Rule $rule
     * @param array $data
     * @return void
     */
    public function addCondition($rule, $data)
    {
        $minAmount = (float) (!empty($data['min_amount']) ? $data['min_amount'] : -1);
        if ($minAmount <= 0) {
            return;
        }

        $combineConditions = $rule->getConditionsInstance();

        /** @var ConditionAddress $subtotalCondition */
        $subtotalCondition = $this->conditionAddressFactory->create([
            'data' => [
                'type' => \Magento\SalesRule\Model\Rule\Condition\Address::class,
                'attribute' => 'base_subtotal',
                'operator' => '>=',
                'value' => $minAmount,
                'is_value_processed' => false
            ]
        ]);
        $combineConditions->setConditions([$subtotalCondition]);

        $rule->setConditions($combineConditions);
    }

    /**
     * Build standard rule name for a coupon code.
     *
     * @param string $couponCode
     * @return string
     */
    public function getRuleName($couponCode)
    {
        return __('NewsMAN generated coupon code')->__toString();
    }

    /**
     * Build standard coupon description.
     *
     * @param string $couponCode
     * @param float $value
     * @return string
     */
    public function getCouponDescription($couponCode, $value)
    {
        return __('Generated Coupon Code')->__toString();
    }

    /**
     * Retrieve characters allowed in coupon codes.
     *
     * @return string
     */
    public function getCouponCharacters()
    {
        return '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
}

<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Export\Retriever;

/**
 * Abstract Retriever
 */
abstract class AbstractRetriever implements RetrieverInterface
{
    /**
     * Get allowed request parameters
     *
     * @return array
     */
    public function getWhereParametersMapping()
    {
        return [];
    }

    /**
     * Get allowed sort fields
     *
     * @return array
     */
    public function getAllowedSortFields()
    {
        return [];
    }

    /**
     * Process list parameters
     *
     * @param array $data
     * @param int $defaultPageSize
     * @return array
     */
    public function processListParameters($data = [], $defaultPageSize = 1000)
    {
        $params = [
            'filters' => $this->processListWhereParameters($data),
            'sort' => null,
            'order' => 'ASC',
            'limit' => $defaultPageSize,
            'currentPage' => 1
        ];

        $allowedSort = $this->getAllowedSortFields();
        if (isset($data['sort']) && isset($allowedSort[$data['sort']])) {
            $params['sort'] = $allowedSort[$data['sort']];
        }

        if (isset($data['order']) && strcasecmp($data['order'], 'desc') === 0) {
            $params['order'] = 'DESC';
        }

        $limit = isset($data['limit']) ? (int)$data['limit'] : $defaultPageSize;
        if ($limit < 1) {
            $limit = $defaultPageSize;
        }
        $params['limit'] = $limit;

        $start = isset($data['start']) ? (int)$data['start'] : 0;
        if ($start < 0) {
            $start = 0;
        }
        $params['currentPage'] = (int)floor($start / $limit) + 1;

        return $params;
    }

    /**
     * Process list where parameters
     *
     * @param array $data
     * @return array
     */
    public function processListWhereParameters($data = [])
    {
        $filters = [];
        $operators = array_keys($this->getExpressionsDefinition());
        $expressions = $this->getExpressionsDefinition();

        foreach ($this->getWhereParametersMapping() as $requestName => $definition) {
            if (!isset($data[$requestName])) {
                continue;
            }

            $fieldName = $definition['field'];
            $value = $data[$requestName];

            if (is_array($value) && !empty(array_intersect(array_keys($value), $operators))) {
                foreach ($value as $operator => $val) {
                    if (!isset($expressions[$operator])) {
                        continue;
                    }
                    $filters[] = [
                        'field' => $fieldName,
                        'condition' => $expressions[$operator],
                        'value' => $val
                    ];
                }
            } elseif (is_array($value) && !empty($definition['multiple'])) {
                $filters[] = [
                    'field' => $fieldName,
                    'condition' => 'in',
                    'value' => $value
                ];
            } else {
                $filters[] = [
                    'field' => $fieldName,
                    'condition' => 'eq',
                    'value' => $value
                ];
            }
        }

        return $filters;
    }

    /**
     * Get SQL conditions expression definition
     *
     * @return array
     */
    public function getExpressionsDefinition()
    {
        return [
            'eq'      => 'eq',
            'neq'     => 'neq',
            'like'    => 'like',
            'nlike'   => 'nlike',
            'in'      => 'in',
            'nin'     => 'nin',
            'is'      => 'is',
            'notnull' => 'notnull',
            'null'    => 'null',
            'gt'      => 'gt',
            'lt'      => 'lt',
            'gteq'    => 'gteq',
            'lteq'    => 'lteq',
            'from'    => 'gteq',
            'to'      => 'lteq',
        ];
    }

    /**
     * Apply filters and sort to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @param array $params
     * @return void
     */
    public function applyFiltersToCollection($collection, $params)
    {
        foreach ($params['filters'] as $filter) {
            if (method_exists($collection, 'addAttributeToFilter')) {
                $collection->addAttributeToFilter($filter['field'], [$filter['condition'] => $filter['value']]);
            } else {
                $collection->addFieldToFilter($filter['field'], [$filter['condition'] => $filter['value']]);
            }
        }

        if ($params['sort']) {
            $collection->setOrder($params['sort'], $params['order']);
        }

        $collection->setPageSize($params['limit']);
        $collection->setCurPage($params['currentPage']);
    }

    /**
     * Apply filters and sort to SearchCriteriaBuilder
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param array $params
     * @return void
     */
    public function applyFiltersToSearchCriteria(
        $searchCriteriaBuilder,
        $filterBuilder,
        $sortOrderBuilderFactory,
        $params
    ) {
        foreach ($params['filters'] as $filter) {
            $searchCriteriaBuilder->addFilters([
                $filterBuilder
                    ->setField($filter['field'])
                    ->setConditionType($filter['condition'])
                    ->setValue($filter['value'])
                    ->create()
            ]);
        }

        if ($params['sort']) {
            $sortOrder = $sortOrderBuilderFactory->create()
                ->setField($params['sort'])
                ->setDirection($params['order'])
                ->create();
            $searchCriteriaBuilder->addSortOrder($sortOrder);
        }

        $searchCriteriaBuilder->setPageSize($params['limit']);
        $searchCriteriaBuilder->setCurrentPage($params['currentPage']);
    }
}

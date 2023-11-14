<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Models\Subscriber;

class ContactsQuery
{
    private $args = [];

    private $model = null;

    public function __construct($args = [])
    {
        $this->args = wp_parse_args($args, [
            'with'               => ['tags', 'lists'],
            'filter_type'        => 'simple',
            'filters_groups'     => [],
            'filters_groups_raw' => [],
            'search'             => '',
            'sort_by'            => 'id',
            'sort_type'          => 'DESC',
            'tags'               => [],
            'lists'              => [],
            'statuses'           => [],
            'has_commerce'       => false,
            'custom_fields'      => false,
            'limit'              => false,
            'offset'             => false,
            'contact_status'     => '',
            'company_ids'        => []
        ]);

        $this->setupQuery();
    }

    private function setupQuery()
    {
        if ($this->args['filters_groups_raw']) {
            $this->formatAdvancedFilters();
        }

        $subscribersQuery = Subscriber::with($this->args['with']);
        if ($search = $this->args['search']) {
            $subscribersQuery->searchBy($search, $this->args['custom_fields']);
        }

        if ($shortBy = $this->args['sort_by']) {
            if (in_array($shortBy, (new Subscriber())->getFillable()) || $shortBy == 'id') {
                $subscribersQuery->orderBy($shortBy, $this->args['sort_type']);
            }
        }

        if ($this->args['filter_type'] == 'advanced') {
            $filtersGroups = $this->args['filters_groups'];
            $subscribersQuery->where(function ($subscribersQueryGroup) use ($filtersGroups) {

                foreach ($filtersGroups as $groupIndex => $group) {
                    $method = 'orWhere';
                    if ($groupIndex == 0) {
                        $method = 'where';
                    }
                    $subscribersQueryGroup->{$method}(function ($q) use ($group) {
                        foreach ($group as $providerName => $items) {
                            do_action_ref_array('fluentcrm_contacts_filter_' . $providerName, [&$q, $items]);
                        }
                    });
                }
            });
        } else {
            if ($tags = $this->args['tags']) {
                $subscribersQuery->filterByTags($tags);
            }

            if ($lists = $this->args['lists']) {
                $subscribersQuery->filterByLists($lists);
            }

            if ($company_ids = $this->args['company_ids']) {
                $subscribersQuery->filterByCompanies($company_ids);
            }

            if ($statuses = $this->args['statuses']) {
                $statuses = (array) $statuses;
                $statuses = array_intersect($statuses, fluentcrm_subscriber_statuses());

                $subscribersQuery->filterByStatues($statuses);
            }
        }

        if ($this->args['has_commerce']) {
            $commerceProvider = apply_filters('fluentcrm_commerce_provider', '');
            if ($commerceProvider) {
                $subscribersQuery->with(['commerce_by_provider' => function ($query) use ($commerceProvider) {
                    $query->where('provider', $commerceProvider);
                }]);
            }
        }

        if ($this->args['contact_status']) {
            $subscribersQuery->where('status', $this->args['contact_status']);
        }

        if ($this->args['company_ids']) {
            $subscribersQuery->filterByCompanies($this->args['company_ids']);
        }

        $this->model = $subscribersQuery;
    }

    public function get()
    {
        $subscriberModel = $this->model;

        if ($limit = $this->args['limit']) {
            $subscriberModel = $subscriberModel->limit($limit);
        }

        if ($offset = $this->args['offset']) {
            $subscriberModel = $subscriberModel->offset($offset);
        }

        return $this->returnSubscribers($subscriberModel->get());
    }

    public function paginate()
    {
        return $this->returnSubscribers($this->model->paginate());
    }

    public function getModel()
    {
        return $this->model;
    }

    private function returnSubscribers($subscribers)
    {
        if ($this->args['custom_fields']) {
            // we have to include custom fields
            foreach ($subscribers as $subscriber) {
                $subscriber->custom_fields = $subscriber->custom_fields();
            }
        }

        return $subscribers;
    }

    private function formatAdvancedFilters()
    {
        $filters = $this->args['filters_groups_raw'];

        $groups = [];

        foreach ($filters as $filterGroup) {
            $group = [];
            foreach ($filterGroup as $filterItem) {
                if (count($filterItem['source']) != 2 || empty($filterItem['source'][0]) || empty($filterItem['source'][1]) || empty($filterItem['operator'])) {
                    continue;
                }
                $provider = $filterItem['source'][0];

                if (!isset($group[$provider])) {
                    $group[$provider] = [];
                }

                $property = $filterItem['source'][1];

                if ($property == 'purchased_groups') {
                    $property = 'purchased_items';
                }

                $group[$provider][] = [
                    'property' => $property,
                    'operator' => $filterItem['operator'],
                    'value'    => $filterItem['value']
                ];
            }

            if ($group) {
                $groups[] = $group;
            }
        }

        $this->args['filters_groups'] = $groups;
    }

}

<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Services\CrmMigrator\Api\ActiveCampaign;
use FluentCrm\Framework\Support\Arr;

class ActiveCampaignMigrator extends BaseMigrator
{
    public function getInfo()
    {
        return [
            'title'                  => 'ActiveCampaign',
            'description'            => __('Transfer your ActiveCampaign tags and contacts to FluentCRM', 'fluent-crm'),
            'logo'                   => fluentCrmMix('images/migrators/active_campaign.png'),
            'supports'               => [
                'tags'                => false,
                'lists'               => false,
                'empty_tags'          => true,
                'active_imports_only' => true,
                'has_list_mapper'     => true
            ],
            'credentials'            => [
                'api_key' => '',
                'api_url' => ''
            ],
            'field_map_info'         => __('Email and main contact fields will be mapped automatically', 'fluent-crm'),
            'credential_fields'      => [
                'api_key' => [
                    'label'       => __('API Token', 'fluent-crm'),
                    'placeholder' => __('ActiveCampaign API Token', 'fluent-crm'),
                    'data_type'   => 'password',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API key at ActiveCampaign Settings -> Developer', 'fluent-crm')
                ],
                'api_url' => [
                    'label'       => __('API Access URL', 'fluent-crm'),
                    'placeholder' => __('API Access URL', 'fluent-crm'),
                    'data_type'   => 'url',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find Account ID Settings -> Developer -> API Access', 'fluent-crm')
                ]
            ],
            'refresh_on_list_change' => false,
            'doc_url'                => 'https://fluentcrm.com/docs/migrating-into-fluentcrm-from-activecampaign/'
        ];
    }

    public function verifyCredentials($credential)
    {
        $api = $this->getApi($credential);

        try {
            $result = $api->auth_test();
            if (is_wp_error($result)) {
                return $result;
            }
        } catch (\Exception $exception) {
            return new \WP_Error('api_error', $exception->getMessage());
        }

        return true;
    }

    public function getListTagMappings($postedData)
    {
        $api = $this->getApi($postedData['credential']);

        $data = [];

        $lists = $api->get_lists();

        if (is_wp_error($lists)) {
            return $lists;
        }

        $formattedLists = [];
        foreach ($lists as $list) {
            if (!is_array($list)) {
                continue;
            }
            $formattedLists[] = [
                'remote_id'   => (string)$list['id'],
                'local_id'    => '',
                'will_create' => 'no',
                'remote_name' => (string)$list['name']
            ];
        }

        $data['mapped_lists'] = $formattedLists;

        $tags = $api->getTags();

        if (is_wp_error($tags)) {
            return $tags;
        }

        $formattedTags = [];

        foreach ($tags as $tag) {
            $formattedTags[] = [
                'remote_name'  => $tag['name'],
                'remote_id'    => (string)$tag['name'],
                'will_create'  => 'no',
                'fluentcrm_id' => ''
            ];
        }
        $data['tags'] = $formattedTags;

        $mergeFields = $api->get_custom_fields();

        if (is_wp_error($mergeFields)) {
            $mergeFields = [];
        }

        $formattedContactFields = [];

        foreach ($mergeFields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $item = [
                'type'            => 'any',
                'remote_label'    => $field['title'],
                'remote_tag'      => $field['perstag'],
                'fluentcrm_field' => '',
                'remote_type'     => '',
                'will_skip'       => 'no'
            ];

            $formattedContactFields[] = $item;
        }

        $data['contact_fields'] = $formattedContactFields;
        $data['contact_fillables'] = $this->getFillables();

        unset($data['contact_fillables']['first_name']);
        unset($data['contact_fillables']['last_name']);
        unset($data['contact_fillables']['full_name']);
        unset($data['contact_fillables']['phone']);

        $data['all_ready'] = true;

        return $data;
    }

    public function getSummary($postedData)
    {
        $mappedLists = Arr::get($postedData, 'mapped_lists', []);

        $listCounts = count($mappedLists);

        $message = __('Based on your selections ', 'fluent-crm') . $listCounts . __(' lists and associate contacts  will be imported', 'fluent-crm');

        return [
            'subscribed_count'   => 1,
            'unsubscribed_count' => 0,
            'all_count'          => 1,
            'message'            => $message
        ];

    }

    public function runImport($postedData)
    {
        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $api = $this->getApi($postedData['credential']);

        $page = Arr::get($postedData, 'completed', 1);

        $tagMappings = Arr::get($postedData, 'tags', []);

        $taggingArray = $this->mapTags($tagMappings);

        $mapSettings = Arr::get($postedData, 'map_settings', []);

        $listingArray = $this->getListingArray(Arr::get($postedData, 'mapped_lists', []));

        $subscribedOnly = false;
        if ($mapSettings['import_active_only'] == 'yes') {
            $subscribedOnly = true;
        }

        $params = [
            'ids'  => 'ALL',
            'full' => 1,
            'page' => $page
        ];
        $subscribers = $api->getContacts($params);

        if (is_wp_error($subscribers)) {

            if ($page > 1) {
                return [
                    'completed'     => 1,
                    'total'         => 1,
                    'has_more'      => false,
                    'hide_progress' => true
                ];
            }

            return $subscribers;
        }

        $fieldMaps = Arr::get($postedData, 'contact_fields', []);
        $stepCompletedCount = 0;
        foreach ($subscribers as $subscriber) {

            if (!is_array($subscriber)) {
                continue;
            }
            $stepCompletedCount++;

            if ($subscribedOnly && $subscriber['status'] != 1) {
                continue;
            }

            $status = 'subscribed';
            if ($subscriber['status'] != 1) {
                $status = 'pending';
            }

            $data = [
                'email'      => $subscriber['email'],
                'first_name' => $subscriber['first_name'],
                'last_name'  => $subscriber['last_name'],
                'phone'      => $subscriber['phone'],
                'created_at' => date('Y-m-d H:i:s', strtotime($subscriber['cdate'])),
                'source'     => 'ActiveCampaign',
                'ip'         => $subscriber['ip'],
                'status'     => $status,
                'lists'      => []
            ];


            if (!empty($subscriber['fields'])) {
                $customFields = [];
                foreach ($subscriber['fields'] as $field) {
                    $customFields['perstag'] = $field['val'];
                }
                if ($customFields) {
                    $mergeData = $this->getMergedData($customFields, $fieldMaps);
                    if ($mergeData) {
                        $data = array_merge($data, $mergeData);
                    }
                }
            }

            // Map Lists here
            if ($listingArray && $subscriber['lists']) {
                foreach ($subscriber['lists'] as $subList) {
                    $listId = $subList['listid'];
                    if (isset($listingArray[$listId])) {
                        $data['lists'][] = $listingArray[$listId];
                    }
                }
            }

            if (!empty($mapSettings['local_list_id'])) {
                $data['lists'][] = $mapSettings['local_list_id'];
            }

            if (!empty($subscriber['tags']) && $taggingArray) {
                $tagIds = [];
                foreach ($subscriber['tags'] as $contactTag) {
                    if (!empty($taggingArray[$contactTag])) {
                        $tagIds[] = $taggingArray[$contactTag];
                    }
                }

                if (empty($tagIds) && !empty($mapSettings['local_tag_id'])) {
                    $tagIds = [$mapSettings['local_tag_id']];
                }

                $data['tags'] = $tagIds;

            } else if (!empty($mapSettings['local_tag_id'])) {
                $data['tags'] = [$mapSettings['local_tag_id']];
            }

            $contact = FluentCrmApi('contacts')->createOrUpdate($data);

            if ($subscribedOnly && $contact && $contact->status != 'subscribed') {
                $oldStatus = $contact->status;
                $contact->status = 'subscribed';
                $contact->save();
                do_action('fluentcrm_subscriber_status_to_subscribed', $contact, $oldStatus);
            }
        }

        return [
            'completed'     => $page + 1,
            'total'         => $page + 1,
            'has_more'      => true,
            'hide_progress' => true,
            'message'       => ($page * 20) . __(' contacts has been imported so far.', 'fluent-crm')
        ];
    }

    private function getApi($credentials)
    {
        return new ActiveCampaign($credentials['api_url'], $credentials['api_key']);
    }

    private function getListingArray($listMappings)
    {
        $formattedMaps = [];

        foreach ($listMappings as $listMapping) {
            $remoteId = $listMapping['remote_id'];

            if ($listMapping['will_create'] == 'yes') {
                $remoteName = sanitize_text_field($listMapping['remote_name']);
                if (!$remoteName) {
                    continue;
                }
                $listMapping['fluentcrm_id'] = $this->getListId($remoteName);
            }

            if (empty($listMapping['fluentcrm_id'])) {
                continue;
            }

            $formattedMaps[$remoteId] = (int)$listMapping['fluentcrm_id'];
        }

        return $formattedMaps;
    }
}

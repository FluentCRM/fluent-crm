<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Services\CrmMigrator\Api\ConvertKit;
use FluentCrm\Framework\Support\Arr;

class ConvertKitMigrator extends BaseMigrator
{
    public function getInfo()
    {
        return [
            'title'                  => 'ConvertKit',
            'description'            => __('Migrate your ConvertKit contacts and associate to FluentCRM', 'fluent-crm'),
            'logo'                   => fluentCrmMix('images/migrators/convertkit.png'),
            'supports'               => [
                'tags'                => true,
                'lists'               => false,
                'empty_tags'          => false,
                'active_imports_only' => false
            ],
            'field_map_info'         => __('Email Address and First name will be mapped automatically', 'fluent-crm'),
            'tags_map_info'           => __('Only Selected tags will be imported from ConvertKit', 'fluent-crm'),
            'credentials'            => [
                'api_key'    => '',
                'api_secret' => ''
            ],
            'credential_fields'      => [
                'api_key'    => [
                    'label'       => __('API Key', 'fluent-crm'),
                    'placeholder' => __('ConvertKit API Key', 'fluent-crm'),
                    'data_type'   => 'text',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API key at ConvertKit ', 'fluent-crm') . '<a href="https://app.convertkit.com/account_settings/advanced_settings" rel="noopener" target="_blank">'. __("Account -> Settings -> Advanced", "fluent-crm") .'</a>'
                ],
                'api_secret' => [
                    'label'       => __('API Secret', 'fluent-crm'),
                    'placeholder' => __('ConvertKit API Secret', 'fluent-crm'),
                    'data_type'   => 'password',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API Secret key at ConvertKit Account -> Settings -> Advanced', 'fluent-crm')
                ]
            ],
            'refresh_on_list_change' => false,
            'doc_url' => 'https://fluentcrm.com/docs/migrating-into-fluentcrm-from-convertkit/'
        ];
    }

    public function verifyCredentials($credential)
    {
        $api = $this->getApi($credential);

        try {
            $result = $api->auth_test();
            if (!empty($result['error'])) {
                throw new \Exception($result['message']);
            }
        } catch (\Exception $exception) {
            return new \WP_Error('api_error', $exception->getMessage());
        }

        return true;
    }

    public function getListTagMappings($postedData)
    {
        $api = $this->getApi($postedData['credential']);
        $tags = $api->getTags();

        $formattedTags = [];

        foreach ($tags as $tag) {
            $formattedTags[] = [
                'remote_name'  => $tag['name'],
                'remote_id'    => (string)$tag['id'],
                'will_create'  => 'no',
                'fluentcrm_id' => ''
            ];
        }

        $data['tags'] = $formattedTags;

        $contactFields = $api->getCustomFields();
        $formattedContactFields = [];

        foreach ($contactFields as $field) {
            $item = [
                'type'            => 'any',
                'remote_label'    => $field['label'],
                'remote_tag'      => $field['key'],
                'fluentcrm_field' => '',
                'will_skip'       => 'no'
            ];

            $fieldKey = $field['key'];

            if ($fieldKey == 'last_name') {
                $item['fluentcrm_field'] = 'last_name';
            }

            $formattedContactFields[] = $item;
        }

        $data['contact_fields'] = $formattedContactFields;
        $data['contact_fillables'] = $this->getFillables();

        unset($data['contact_fillables']['first_name']);
        unset($data['contact_fillables']['full_name']);

        $data['all_ready'] = true;

        return $data;
    }

    public function getSummary($postedData)
    {
        $api = $this->getApi($postedData['credential']);

        $subscribers = $api->getSubscribers([
            'page' => 1
        ]);

        if (is_wp_error($subscribers)) {
            return $subscribers;
        }

        $totalSubscribers = Arr::get($subscribers, 'total_subscribers', 0);


        $tagMappings = Arr::get($postedData, 'tags', []);

        $tagCounts = 0;

        foreach ($tagMappings as $tagMapping) {
            if ($tagMapping['will_create'] == 'yes') {
                $tagCounts++;
                continue;
            }

            if ($tagMapping['will_create'] == 'no' || empty($tagMapping['fluentcrm_id'])) {
                continue;
            }

            $tagCounts++;

        }

        $message = __('Based on your selections, ', 'fluent-crm') . $tagCounts . __(' tags and associate contacts will be imported from ConvertKit', 'fluent-crm');


        return [
            'subscribed_count'   => $totalSubscribers,
            'unsubscribed_count' => 0,
            'all_count'          => $totalSubscribers,
            'message'            => $message
        ];
    }

    public function runImport($postedData)
    {
        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $api = $this->getApi($postedData['credential']);

        $tagMappings = Arr::get($postedData, 'tags', []);

        $taggingArray = $this->mapTags($tagMappings);

        $taggingKeys = array_keys($taggingArray);

        if (!$taggingKeys) {
            return new \WP_Error('not_found', 'No Tag found based on your selection');
        }

        $import_tracker = Arr::get($postedData, 'import_tracker', []);

        if (empty($import_tracker)) {
            $import_tracker = [
                'current_index'  => 0,
                'completed_page' => 0
            ];
        }

        $currentTagId = $taggingKeys[$import_tracker['current_index']];
        $completedPage = $import_tracker['completed_page'];

        $members = $api->getTagSubscribers($currentTagId, [
            'page' => $completedPage + 1
        ]);

        $subscribers = $members['subscriptions'];

        $fieldMaps = Arr::get($postedData, 'contact_fields');

        $mapSettings = Arr::get($postedData, 'map_settings', []);

        foreach ($subscribers as $subscriberItem) {

            $subscriber = $subscriberItem['subscriber'];
            if ($subscriber['state'] != 'active') {
                continue;
            }

            $data = [
                'email'      => $subscriber['email_address'],
                'first_name' => $subscriber['first_name'],
                'created_at' => date('Y-m-d H:i:s', strtotime($subscriber['created_at'])),
                'source'     => 'ConvertKit',
                'status'     => 'subscribed'
            ];

            $mergeData = $this->getMergedData($subscriber['fields'], $fieldMaps);

            if ($mergeData) {
                $data = array_merge($data, $mergeData);
            }

            if (!empty($mapSettings['local_list_id'])) {
                $data['lists'] = [$mapSettings['local_list_id']];
            }

            $data['tags'] = [$taggingArray[$currentTagId]];


            $contact = FluentCrmApi('contacts')->createOrUpdate($data);

            if ($contact && $contact->status != 'subscribed') {
                $oldStatus = $contact->status;
                $contact->status = 'subscribed';
                $contact->save();
                do_action('fluentcrm_subscriber_status_to_subscribed', $contact, $oldStatus);
            }
        }

        $stepCompleted = ($completedPage + 1) >= $members['total_pages'];

        if (!$stepCompleted) {
            $import_tracker = [
                'current_index'  => $import_tracker['current_index'],
                'completed_page' => $completedPage + 1
            ];
        } else {
            $nextIndex = $import_tracker['current_index'] + 1;
            $import_tracker = [
                'current_index'  => $nextIndex,
                'completed_page' => 0
            ];
        }

        return [
            'completed'      => 0,
            'total'          => 0,
            'import_tracker' => $import_tracker,
            'has_more'       => isset($taggingKeys[$import_tracker['current_index']]),
            'message' => __('Importer is running now. ', 'fluent-crm').( $import_tracker['current_index']+1 ) .__(' tags have been imported so far', 'fluent-crm')
        ];
    }

    private function getApi($credential)
    {
        return new ConvertKit($credential['api_key'], $credential['api_secret']);
    }
}

<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Services\CrmMigrator\Api\MailerLite;
use FluentCrm\Framework\Support\Arr;

class MailerLiteMigrator extends BaseMigrator
{
    public function getInfo()
    {
        return [
            'title'                  => 'MailerLite',
            'description'            => __('Migrate your MailerLite contacts and associate to FluentCRM', 'fluent-crm'),
            'logo'                   => fluentCrmMix('images/migrators/mailerlite.png'),
            'supports'               => [
                'tags'                => true,
                'lists'               => false,
                'empty_tags'          => false,
                'active_imports_only' => true
            ],
            'field_map_info'         => __('Email Address and First name will be mapped automatically', 'fluent-crm'),
            'tags_map_info'           => __('Only Selected Groups will be imported from MailerLite', 'fluent-crm'),
            'credentials'            => [
                'api_key' => ''
            ],
            'credential_fields'      => [
                'api_key' => [
                    'label'       => __('API Key', 'fluent-crm'),
                    'placeholder' => __('MailerLite API Key', 'fluent-crm'),
                    'data_type'   => 'password',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API key at MailerLite', 'fluent-crm') . ' <a href="https://app.mailerlite.com/integrations/api/" target="_blank" rel="noopener">'. __("Account -> Integrations -> Developer API", "fluent-crm") .'</a>'
                ]
            ],
            'refresh_on_list_change' => false,
            'doc_url' => 'https://fluentcrm.com/docs/migrating-into-fluentcrm-from-mailerlite/'
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

        $groups = $api->getGroups();

        $formattedTags = [];

        foreach ($groups as $tag) {
            $formattedTags[] = [
                'remote_name'  => (string)$tag['name'],
                'remote_id'    => (string)$tag['id'],
                'will_create'  => 'no',
                'fluentcrm_id' => ''
            ];
        }

        $data['tags'] = $formattedTags;

        $contactFields = $api->getCustomFields();
        $formattedContactFields = [];

        $autoFieldMaps = [
            'name'      => 'first_name',
            'last_name' => 'last_name',
            'country'   => 'country',
            'city'      => 'city',
            'phone'     => 'phone',
            'state'     => 'state',
            'zip'       => 'postal_code'
        ];

        foreach ($contactFields as $field) {
            $item = [
                'type'            => 'any',
                'remote_label'    => $field['title'],
                'remote_tag'      => $field['key'],
                'fluentcrm_field' => '',
                'will_skip'       => 'no'
            ];


            $fieldKey = $field['key'];

            if ($fieldKey == 'email') {
                continue;
            }

            if (isset($autoFieldMaps[$fieldKey])) {
                $item['fluentcrm_field'] = $autoFieldMaps[$fieldKey];
            }

            $formattedContactFields[] = $item;
        }

        $data['contact_fields'] = $formattedContactFields;
        $data['contact_fillables'] = $this->getFillables();

        $data['all_ready'] = true;

        return $data;
    }

    public function getSummary($postedData)
    {
        $api = $this->getApi($postedData['credential']);

        $groups = $api->getGroups();

        if (is_wp_error($groups)) {
            return $groups;
        }

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

        $message = __('Based on your selections, ', 'fluent-crm') . $tagCounts . __(' groups and associate contacts will be imported from MailerLite', 'fluent-crm');

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

        $tagMappings = Arr::get($postedData, 'tags', []);

        $taggingArray = $this->mapTags($tagMappings);

        $taggingKeys = array_keys($taggingArray);

        if (!$taggingKeys) {
            return new \WP_Error('not_found', 'No Tag found based on your selection');
        }

        $limitPerChunk = 50;

        $import_tracker = Arr::get($postedData, 'import_tracker', []);

        if (empty($import_tracker)) {
            $import_tracker = [
                'current_index' => 0,
                'offset'        => 0
            ];
        }

        $currentTagId = $taggingKeys[$import_tracker['current_index']];

        $subscribers = $api->getGroupSubscribers($currentTagId, [
            'offset' => $import_tracker['offset'],
            'limit'  => $limitPerChunk
        ]);

        if (is_wp_error($subscribers)) {
            return $subscribers;
        }

        $fieldMaps = Arr::get($postedData, 'contact_fields', []);

        $mapSettings = Arr::get($postedData, 'map_settings', []);

        foreach ($subscribers as $subscriber) {

            if ($subscriber['type'] != 'active') {
                continue;
            }

            $data = [
                'email'      => $subscriber['email'],
                'first_name' => $subscriber['name'],
                'created_at' => date('Y-m-d H:i:s', strtotime($subscriber['date_created'])),
                'source'     => 'MailerLite',
                'status'     => 'subscribed',
                'ip'         => $subscriber['signup_ip']
            ];

            $remoteData = $subscriber['fields'];

            $formattedRemoteData = [];

            foreach ($remoteData as $remoteDatum) {
                $formattedRemoteData[$remoteDatum['key']] = $remoteDatum['value'];
            }

            $mergeData = $this->getMergedData($formattedRemoteData, $fieldMaps);

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

        $stepCompletedCount = $limitPerChunk + $import_tracker['offset'];

        $groupContactsCount = $api->getContactCountByGroup($currentTagId);

        if (is_wp_error($groupContactsCount)) {
            return $groupContactsCount;
        }
        $stepCompleted = $stepCompletedCount >= $groupContactsCount;

        if (!$stepCompleted) {
            $import_tracker = [
                'current_index' => $import_tracker['current_index'],
                'offset'        => $stepCompletedCount
            ];
        } else {
            $nextIndex = $import_tracker['current_index'] + 1;
            $import_tracker = [
                'current_index' => $nextIndex,
                'offset'        => 0
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
        return new MailerLite($credential['api_key']);
    }

}

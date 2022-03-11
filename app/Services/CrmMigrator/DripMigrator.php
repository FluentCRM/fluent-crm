<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Services\CrmMigrator\Api\Drip;
use FluentCrm\Framework\Support\Arr;

class DripMigrator extends BaseMigrator
{
    public function getInfo()
    {
        return [
            'title'                  => 'Drip',
            'description'            => __('Transfer your Drip tags and contacts to FluentCRM', 'fluent-crm'),
            'logo'                   => fluentCrmMix('images/migrators/drip.png'),
            'supports'               => [
                'tags'                => true,
                'lists'               => false,
                'empty_tags'          => true,
                'active_imports_only' => true
            ],
            'credentials'            => [
                'api_key'    => '',
                'account_id' => ''
            ],
            'field_map_info'         => __('Email and main contact fields will be mapped automatically', 'fluent-crm'),
            'credential_fields'      => [
                'api_key'    => [
                    'label'       => __('API Token', 'fluent-crm'),
                    'placeholder' => __('Drip API Token', 'fluent-crm'),
                    'data_type'   => 'password',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API key at Drip Profile -> User Info -> API Token', 'fluent-crm')
                ],
                'account_id' => [
                    'label'       => __('Account ID', 'fluent-crm'),
                    'placeholder' => __('Drip Account ID', 'fluent-crm'),
                    'data_type'   => 'text',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find Account ID Settings -> General Info -> Account ID', 'fluent-crm')
                ]
            ],
            'refresh_on_list_change' => false,
            'doc_url' => 'https://fluentcrm.com/docs/migrating-into-fluentcrm-from-drip/'
        ];
    }

    public function verifyCredentials($credential)
    {
        $api = $this->getApi($credential);

        try {
            $result = $api->make_request('accounts', [], 'GET');
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

        $tags = $api->sendAccountItems('tags');

        $data = [];

        if (is_wp_error($tags)) {
            return $tags;
        }

        $formattedTags = [];

        foreach ($tags['tags'] as $tag) {
            $formattedTags[] = [
                'remote_name'  => (string)$tag,
                'remote_id'    => (string)$tag,
                'will_create'  => 'no',
                'fluentcrm_id' => ''
            ];
        }
        $data['tags'] = $formattedTags;

        $mergeFields = $api->sendAccountItems('custom_field_identifiers');

        $contactFields = $mergeFields['custom_field_identifiers'];
        $formattedContactFields = [];

        $defaultFields = [
            'First_Name',
            'Last_Name',
            'address1',
            'address2',
            'city',
            'phone',
            'state',
            'zip',
            'country'
        ];

        $contactFields = array_values(array_diff($contactFields, $defaultFields));

        foreach ($contactFields as $field) {
            $item = [
                'type'            => 'any',
                'remote_label'    => $field,
                'remote_tag'      => $field,
                'fluentcrm_field' => '',
                'remote_type'     => '',
                'will_skip'       => 'no'
            ];

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

        $status = 'all';

        $settings = Arr::get($postedData, 'map_settings', []);

        if (Arr::get($settings, 'import_active_only') == 'yes') {
            $status = 'active';
        }

        $members = $api->sendAccountItems('subscribers', [
            'status'   => $status,
            'page'     => 1,
            'per_page' => 10
        ]);

        if (is_wp_error($members)) {
            return $members;
        }

        $meta = $members['meta'];

        $message = __('Based on your selections ', 'fluent-crm') . $meta['total_count'] . __(' contacts will be imported', 'fluent-crm');

        return [
            'subscribed_count'   => $meta['total_count'],
            'unsubscribed_count' => 0,
            'all_count'          => $meta['total_count'],
            'message'            => $message
        ];

    }

    public function runImport($postedData)
    {
        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $api = $this->getApi($postedData['credential']);

        $processPerPage = 10;

        $page = Arr::get($postedData, 'completed', 0);

        if (!$page) {
            $page = 1;
        }

        $params = [
            'page'     => $page,
            'per_page' => $processPerPage,
            'status'   => 'all'
        ];

        $tagMappings = Arr::get($postedData, 'tags', []);

        $taggingArray = $this->mapTags($tagMappings);

        $mapSettings = Arr::get($postedData, 'map_settings', []);

        if ($mapSettings['import_active_only'] == 'yes') {
            $params['status'] = 'subscribed';
        }

        $members = $api->sendAccountItems('subscribers', $params);

        if (is_wp_error($members)) {
            return $members;
        }

        $memberMeta = $members['meta'];

        $subscribers = $members['subscribers'];

        $fieldMaps = Arr::get($postedData, 'contact_fields', []);

        foreach ($subscribers as $subscriber) {

            $statusMaps = [
                'active'       => 'subscribed',
                'unsubscribed' => 'unsubscribed'
            ];
            $status = (isset($statusMaps[$subscriber['status']])) ? $statusMaps[$subscriber['status']] : 'pending';
            if ($mapSettings['import_active_only'] == 'yes') {
                $status = 'subscribed';
            }

            $data = [
                'email'          => $subscriber['email'],
                'first_name'     => $subscriber['first_name'],
                'last_name'      => $subscriber['last_name'],
                'address_line_1' => $subscriber['address1'],
                'address_line_2' => $subscriber['address2'],
                'city'           => $subscriber['city'],
                'state'          => $subscriber['state'],
                'postal_code'    => $subscriber['zip'],
                'phone'          => $subscriber['phone'],
                'created_at'     => date('Y-m-d H:i:s', strtotime($subscriber['created_at'])),
                'source'         => 'Drip',
                'ip'             => $subscriber['ip_address'],
                'country'        => Arr::get($subscriber, 'country'),
                'status'         => $status
            ];

            $mergeData = $this->getMergedData($subscriber['custom_fields'], $fieldMaps);

            if ($mergeData) {
                $data = array_merge($data, $mergeData);
            }

            if (!empty($mapSettings['local_list_id'])) {
                $data['lists'] = [$mapSettings['local_list_id']];
            }

            if (!empty($subscriber['tags'])) {
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

            if ($status == 'subscribed' && $contact && $contact->status != 'subscribed') {
                $oldStatus = $contact->status;
                $contact->status = 'subscribed';
                $contact->save();
                do_action('fluentcrm_subscriber_status_to_subscribed', $contact, $oldStatus);
            }
        }

        $completed = $memberMeta['page'] + 1;

        return [
            'completed' => $completed,
            'total'     => $memberMeta['total_pages'],
            'has_more'  => $memberMeta['page'] < $memberMeta['total_pages']
        ];
    }

    private function getApi($credentials)
    {
        return new Drip($credentials['api_key'], $credentials['account_id']);
    }
}

<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Models\Tag;
use FluentCrm\App\Services\CrmMigrator\Api\MailChimp;
use FluentCrm\Framework\Support\Arr;

class MailChimpMigrator extends BaseMigrator
{
    private $tagNameCache = [];
    private $taggingArray = [];

    public function getInfo()
    {
        return [
            'title'                  => 'MailChimp',
            'description'            => __('Transfer your mailchimp lists, tags and contacts from MailChimp to FluentCRM', 'fluent-crm'),
            'logo'                   => fluentCrmMix('images/migrators/mailchimp.png'),
            'supports'               => [
                'tags'                => true,
                'lists'               => true,
                'empty_tags'          => true,
                'active_imports_only' => true,
                'auto_tag_mapper'     => true
            ],
            'credentials'            => [
                'api_key' => ''
            ],
            'credential_fields'      => [
                'api_key' => [
                    'label'       => __('API Key', 'fluent-crm'),
                    'placeholder' => __('MailChimp API Key', 'fluent-crm'),
                    'data_type'   => 'password',
                    'type'        => 'input-text',
                    'inline_help' => __('You can find your API key at MailChimp Account -> Extras -> API keys', 'fluent-crm')
                ]
            ],
            'refresh_on_list_change' => true,
            'doc_url'                => 'https://fluentcrm.com/docs/migrating-into-fluentcrm-from-mailchimp/'
        ];
    }

    public function verifyCredentials($credential)
    {
        $api = $this->getApi($credential);
        try {
            $result = $api->get('lists');
            if (!$api->success()) {
                throw new \Exception($api->getLastError());
            }
        } catch (\Exception $exception) {
            return new \WP_Error('api_error', $exception->getMessage());
        }

        return true;
    }

    public function getListTagMappings($postedData)
    {
        $api = $this->getApi($postedData['credential']);
        $lists = $api->get('lists');
        $formattedLists = [];

        foreach ($lists['lists'] as $list) {
            $formattedLists[] = [
                'id'          => $list['id'],
                'name'        => $list['name'],
                'description' => __('(Contacts count ', 'fluent-crm') . Arr::get($list, 'stats.member_count') . ')'
            ];
        }

        $data = [
            'lists'             => $formattedLists,
            'tags'              => [],
            'contact_fields'    => [],
            'contact_fillables' => []
        ];

        $settings = Arr::get($postedData, 'map_settings', []);

        if (!empty($settings['list_id'])) {
            $tags = $api->get('lists/' . $settings['list_id'] . '/tag-search');
            $formattedTags = [];

            foreach ($tags['tags'] as $tag) {
                $formattedTags[] = [
                    'remote_name'  => (string)$tag['name'],
                    'remote_id'    => (string)$tag['id'],
                    'will_create'  => 'no',
                    'fluentcrm_id' => ''
                ];
            }
            $data['tags'] = $formattedTags;
            $mergeFields = $api->get('lists/' . $settings['list_id'] . '/merge-fields', array('count' => 9999));

            $contactFields = $mergeFields['merge_fields'];

            $mcFieldTypes = ['text', 'radio', 'phone', 'imageurl', 'url', 'dropdown', 'date', 'number', 'address'];
            $formattedContactFields = [];

            $addressMapped = false;
            foreach ($contactFields as $field) {
                $fieldType = $field['type'];

                if (!in_array($fieldType, $mcFieldTypes)) {
                    continue;
                }

                $item = [
                    'type'            => 'any',
                    'remote_label'    => $field['name'],
                    'remote_tag'      => $field['tag'],
                    'fluentcrm_field' => '',
                    'remote_type'     => $fieldType,
                    'will_skip'       => 'no'
                ];

                if ($fieldType == 'address') {
                    $item['type'] = 'selections';
                    $item['options'] = ['contact_address' => 'Contact Address'];

                    if (!$addressMapped) {
                        $addressMapped = true;
                        $item['fluentcrm_field'] = 'contact_address';
                    }
                } else if ($fieldType == 'date') {
                    $item['date_format'] = $field['options']['date_format'];
                }

                if ($item['remote_tag'] == 'FNAME') {
                    $item['fluentcrm_field'] = 'full_name';
                } else if ($item['remote_label'] == 'First Name') {
                    $item['fluentcrm_field'] = 'first_name';
                } else if ($item['remote_label'] == 'Last Name') {
                    $item['fluentcrm_field'] = 'last_name';
                }

                $formattedContactFields[] = $item;
            }

            $data['contact_fields'] = $formattedContactFields;
            $data['contact_fillables'] = $this->getFillables();

            $data['all_ready'] = true;

        }

        return $data;
    }

    public function getSummary($postedData)
    {
        $settings = Arr::get($postedData, 'map_settings', []);
        $listId = Arr::get($settings, 'list_id');
        $api = $this->getApi($postedData['credential']);

        $list = $api->get('lists/' . $listId);

        $membersCount = Arr::get($list, 'stats.member_count', 0);
        $allCount = Arr::get($list, 'stats.unsubscribe_count') + $membersCount;

        $count = $allCount;

        if (Arr::get($settings, 'import_active_only') == 'yes') {
            $count = $membersCount;
        }

        $message = __('Based on your selections ', 'fluent-crm') . $count . __(' contacts will be imported', 'fluent-crm');

        return [
            'subscribed_count'   => $membersCount,
            'unsubscribed_count' => Arr::get($list, 'stats.unsubscribe_count', 0),
            'all_count'          => $allCount,
            'message'            => $message
        ];
    }

    public function runImport($postedData)
    {

        $isAutoTagMapping = Arr::get($postedData, 'auto_mapping') == 'yes';

        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $mapSettings = Arr::get($postedData, 'map_settings', []);

        $api = $this->getApi($postedData['credential']);

        $processPerPage = 100;

        $params = [
            'offset' => Arr::get($postedData, 'completed', 0),
            'count'  => $processPerPage
        ];

        if(!$isAutoTagMapping) {
            $tagMappings = Arr::get($postedData, 'tags', []);
            $this->taggingArray = $this->mapTags($tagMappings);
        }

        if ($mapSettings['import_active_only'] == 'yes') {
            $params['status'] = 'subscribed';
        }

        $members = $api->get('lists/' . $mapSettings['list_id'] . '/members', $params);

        $subscribers = $members['members'];

        $fieldMaps = Arr::get($postedData, 'contact_fields', []);

        foreach ($subscribers as $subscriber) {
            $created_at = date('Y-m-d H:i:s');
            if (!empty($subscriber['timestamp_signup'])) {
                $created_at = date('Y-m-d H:i:s', strtotime($subscriber['timestamp_signup']));
            }

            $data = [
                'email'      => $subscriber['email_address'],
                'full_name'  => $subscriber['full_name'],
                'created_at' => $created_at,
                'source'     => $subscriber['source'],
                'ip'         => $subscriber['ip_signup'],
                'country'    => Arr::get($subscriber, 'location.country_code'),
                'status'     => $subscriber['status']
            ];

            $mergeData = $this->getMergedData($subscriber['merge_fields'], $fieldMaps);

            if (!empty($mergeData['contact_address']) && is_array($mergeData['contact_address'])) {
                $address = $mergeData['contact_address'];
                unset($mergeData['contact_address']);

                $address = array_filter($address);

                $addressMappings = [
                    'addr1'   => 'address_line_1',
                    'addr2'   => 'address_line_2',
                    'city'    => 'city',
                    'country' => 'country',
                    'state'   => 'state',
                    'zip'     => 'postal_code'
                ];

                foreach ($address as $key => $value) {
                    if (isset($addressMappings[$key])) {
                        $mergeData[$addressMappings[$key]] = $value;
                    }
                }
            }

            if ($mergeData) {
                $data = array_merge($data, $mergeData);
            }

            if (!empty($mapSettings['local_list_id'])) {
                $data['lists'] = [$mapSettings['local_list_id']];
            }

            $data['tags'] = $this->formatRemoteTags($subscriber['tags'], $isAutoTagMapping, $mapSettings);

            $contact = FluentCrmApi('contacts')->createOrUpdate($data);

            if (isset($params['status']) && $params['status'] == 'subscribed' && $contact && $contact->status != 'subscribed') {
                $oldStatus = $contact->status;
                $contact->status = 'subscribed';
                $contact->save();

                do_action('fluentcrm_subscriber_status_to_subscribed', $contact, $oldStatus);
            }

        }

        $completed = Arr::get($postedData, 'completed', 0) + $processPerPage;

        return [
            'completed' => $completed,
            'total'     => $members['total_items'],
            'has_more'  => $completed < $members['total_items']
        ];
    }

    private function formatRemoteTags($remoteTags, $isAuto, $mapSettings)
    {
        $tagIds = [];
        if($isAuto) {
            foreach ($remoteTags as $tag) {
                $tagName = sanitize_text_field($tag['name']);
                if(!empty($this->tagNameCache[$tagName])) {
                    $tagIds[] = $this->tagNameCache[$tagName];
                } else {
                    $createdTag = Tag::updateOrCreate(
                        ['slug' => sanitize_title($tagName, 'display')],
                        ['title' => sanitize_text_field($tagName)]
                    );
                    if($createdTag) {
                        $this->tagNameCache[$createdTag->title] = $createdTag->id;
                        $tagIds[] = $createdTag->id;
                    }
                }
            }

            if (empty($tagIds) && !empty($mapSettings['local_tag_id'])) {
                $tagIds = [$mapSettings['local_tag_id']];
            }

            return $tagIds;
        }

        foreach ($remoteTags as $contactTag) {
            if (!empty($this->taggingArray[$contactTag['id']])) {
                $tagIds[] = $this->taggingArray[$contactTag['id']];
            }
        }

        if (empty($tagIds) && !empty($mapSettings['local_tag_id'])) {
            $tagIds = [$mapSettings['local_tag_id']];
        }
        return $tagIds;
    }

    private function getApi($credential)
    {
        return new MailChimp($credential['api_key']);
    }
}

<?php

namespace FluentCrm\App\Services\CrmMigrator;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;

abstract class BaseMigrator
{
    abstract public function getInfo();

    abstract public function verifyCredentials($credential);

    abstract public function getSummary($settings);

    abstract public function runImport($settings);

    abstract public function getListTagMappings($settings);

    public function getFillables($with = ['main_fields', 'address', 'custom_fields'])
    {
        $mainFields = [
            'prefix'     => __('Name Prefix', 'fluent-crm'),
            'first_name' => __('First Name', 'fluent-crm'),
            'last_name'  => __('Last Name', 'fluent-crm'),
            'full_name'  => __('Full Name', 'fluent-crm'),
            'phone'      => __('Phone', 'fluent-crm')
        ];

        if (!$with) {
            return $mainFields;
        }

        $addressFields = [
            'address_line_1' => __('Address Line 1', 'fluent-crm'),
            'address_line_2' => __('Address Line 2', 'fluent-crm'),
            'postal_code'    => __('Postal Code', 'fluent-crm'),
            'city'           => __('City', 'fluent-crm'),
            'state'          => __('State', 'fluent-crm'),
            'country'        => __('Country', 'fluent-crm'),
        ];

        $customFields = fluentcrm_get_custom_contact_fields();

        $customs = [];
        foreach ($customFields as $customField) {
            $customs[$customField['slug']] = $customField['label'] . ' (Custom Field)';
        }

        if (in_array('address', $with)) {
            $mainFields = array_merge($mainFields, $addressFields);
        }

        if ($customs && in_array('custom_fields', $with)) {
            $mainFields = array_merge($mainFields, $customs);
        }

        if (in_array('custom_fields_only', $with)) {
            return $customs;
        }

        return $mainFields;

    }

    public function getMergedData($remoteData, $fieldMaps)
    {
        $formattedData = [];
        foreach ($fieldMaps as $fieldMap) {
            if ($fieldMap['will_skip'] == 'yes' || empty($fieldMap['fluentcrm_field']) || empty($fieldMap['remote_tag'])) {
                continue;
            }
            $tagName = $fieldMap['remote_tag'];
            if (empty($remoteData[$tagName])) {
                continue;
            }

            $fluentCrmTag = $fieldMap['fluentcrm_field'];

            if (!empty($fieldMap['date_format'])) {
                $givenValue = $remoteData[$tagName];
                $myDateTime = \DateTime::createFromFormat($fieldMap['date_format'], $givenValue);
                $formattedData[$fluentCrmTag] = $myDateTime->format('Y-m-d');
            } else {
                $formattedData[$fluentCrmTag] = $remoteData[$tagName];
            }
        }

        $formattedData = array_filter($formattedData);

        $customFields = fluentcrm_get_custom_contact_fields();

        if (!$customFields) {
            return $formattedData;
        }

        $keys = array_map(function ($item) {
            return $item['slug'];
        }, $customFields);

        $customs = array_filter(Arr::only($formattedData, $keys));

        foreach ($keys as $key) {
            unset($formattedData[$key]);
        }

        $formattedData['custom_values'] = $customs;

        return $formattedData;

    }

    /**
     * Maybe create field options.
     *
     * When importing multi-checkbox or multi-select data, we can save time by
     * auto-populating the options into the FluentCRM custom field config.
     *
     * @param array $mergeData The loaded custom field data.
     * @since 2.8.34
     *
     */
    public function maybeCreateFieldOptions($mergeData)
    {

        $needsUpdate = false;
        $customFields = fluentcrm_get_custom_contact_fields();

        foreach ($mergeData['custom_values'] as $key => $value) {
            if (is_array($value)) {
                foreach ($customFields as $i => $field) {
                    if ($key === $field['slug'] && in_array($field['type'], ['checkbox', 'select-multi']) && !empty(array_diff($value, $field['options']))) {
                        $value = array_map('sanitize_text_field', $value);
                        $customFields[$i]['options'] = array_merge($field['options'], array_diff($value, $field['options']));
                        $needsUpdate = true;
                    }
                }
            }
        }

        if ($needsUpdate) {
            fluentcrm_update_option('contact_custom_fields', $customFields);
        }

    }

    public function mapTags($tagMappings)
    {
        $formattedMaps = [];

        foreach ($tagMappings as $tagMapping) {
            $remoteId = $tagMapping['remote_id'];
            if ($tagMapping['will_create'] == 'yes') {
                $remoteName = sanitize_text_field($tagMapping['remote_name']);
                if (!$remoteName) {
                    continue;
                }
                $tagMapping['fluentcrm_id'] = $this->getTagId($remoteName);
            }

            if (empty($tagMapping['fluentcrm_id'])) {
                continue;
            }

            $formattedMaps[$remoteId] = (int)$tagMapping['fluentcrm_id'];
        }

        return $formattedMaps;

    }

    public function getTagId($tagName)
    {
        $slug = sanitize_title($tagName);

        $tag = Tag::updateOrCreate(
            ['slug' => sanitize_title($slug, 'display')],
            ['title' => $tagName]
        );

        do_action('fluentcrm_tag_created', $tag->id);

        do_action('fluent_crm/tag_created', $tag);

        return $tag->id;
    }

    public function getListId($listName)
    {
        $slug = sanitize_title($listName);

        $list = Lists::updateOrCreate(
            ['slug' => sanitize_title($slug, 'display')],
            ['title' => $listName]
        );

        do_action('fluentcrm_list_created', $list->id);
        do_action('fluent_crm/list_created', $list);

        return $list->id;
    }
}

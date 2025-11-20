<?php

namespace FluentCrm\App\Services;


use FluentCrm\App\Models\CustomCompanyField;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;

class Sanitize
{
    public static function campaign($data)
    {
        $fieldMaps = [
            'title'            => 'sanitize_text_field',
            'slug'             => 'sanitize_text_field',
            'template_id'      => 'intval',
            'email_subject'    => 'sanitize_text_field',
            'email_pre_header' => 'sanitize_text_field',
            'utm_status'       => 'intval',
            'utm_source'       => 'sanitize_text_field',
            'utm_medium'       => 'sanitize_text_field',
            'utm_campaign'     => 'sanitize_text_field',
            'utm_term'         => 'sanitize_text_field',
            'utm_content'      => 'sanitize_text_field',
            'scheduled_at'     => 'sanitize_text_field',
            'design_template'  => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if ($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }

    public static function contact($data)
    {
        $fieldMaps = [
            'hash'            => 'sanitize_text_field',
            'prefix'          => 'sanitize_text_field',
            'first_name'      => 'sanitize_text_field',
            'last_name'       => 'sanitize_text_field',
            'user_id'         => 'intval',
            'email'           => 'sanitize_email',
            'status'          => 'sanitize_text_field',
            'contact_type'    => 'sanitize_text_field',
            'address_line_1'  => 'sanitize_text_field',
            'address_line_2'  => 'sanitize_text_field',
            'postal_code'     => 'sanitize_text_field',
            'city'            => 'sanitize_text_field',
            'state'           => 'sanitize_text_field',
            'country'         => 'sanitize_text_field',
            'phone'           => 'sanitize_text_field',
            'timezone'        => 'sanitize_text_field',
            'date_of_birth'   => 'sanitize_text_field',
            'source'          => 'sanitize_text_field',
            'life_time_value' => 'sanitize_text_field',
            'last_activity'   => 'sanitize_text_field',
            'total_points'    => 'intval',
            'latitude'        => 'sanitize_text_field',
            'longitude'       => 'sanitize_text_field',
            'ip'              => 'sanitize_text_field',
            'created_at'      => 'sanitize_text_field',
            'updated_at'      => 'sanitize_text_field',
            'avatar'          => 'esc_url_raw',
            'company_id'      => 'intval',
        ];

        foreach ($data as $key => $value) {
            if ($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        if (isset($data['status'])) {
            $status = $data['status'];
            if (!in_array($status, fluentcrm_subscriber_statuses())) {
                unset($data['status']);
            }
        }

        return $data;
    }

    public static function contactNote($data)
    {
        $fieldMaps = [
            'subscriber_id' => 'intval',
            'parent_id'     => 'intval',
            'created_by'    => 'sanitize_text_field',
            'status'        => 'sanitize_text_field',
            'type'          => 'sanitize_text_field',
            'title'         => 'sanitize_text_field',
            'description'   => 'wp_kses_post',
            'created_at'    => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if ($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }

    public static function funnel($data)
    {
        $fieldMaps = [
            'type'         => 'sanitize_text_field',
            'title'        => 'sanitize_text_field',
            'trigger_name' => 'sanitize_text_field',
            'status'       => 'sanitize_text_field',
            'created_by'   => 'intval',
            'updated_at'   => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if ($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }

    public static function company($data)
    {
        $fieldMaps = [
            'name'             => 'sanitize_text_field',
            'description'      => 'wp_kses_post',
            'phone'            => 'sanitize_text_field',
            'email'            => 'sanitize_email',
            'owner_id'         => 'intval',
            'employees_number' => 'intval',
            'industry'         => 'sanitize_text_field',
            'type'             => 'sanitize_text_field',
            'address_line_1'   => 'sanitize_text_field',
            'address_line_2'   => 'sanitize_text_field',
            'postal_code'      => 'sanitize_text_field',
            'city'             => 'sanitize_text_field',
            'state'            => 'sanitize_text_field',
            'country'          => 'sanitize_text_field',
            'website'          => 'esc_url_raw',
            'linkedin_url'     => 'esc_url_raw',
            'facebook_url'     => 'esc_url_raw',
            'twitter_url'      => 'esc_url_raw',
            'logo'             => 'esc_url_raw',
        ];

        foreach ($data as $key => $value) {
            if ($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        if (isset($data['custom_values'])) {
            $customValues = Arr::get($data, 'custom_values', []);

            $customFieldKeys = [];
            $customFields = (new CustomCompanyField())->getGlobalFields()['fields'];

            foreach ($customFields as $field) {
                $customFieldKeys[] = $field['slug'];
            }

            if ($customFieldKeys) {
                if ($customValues) {
                    $customValues = (new CustomCompanyField)->formatCustomFieldValues($customValues);
                }
            }

            $data['custom_values'] = $customValues;
        }

        return $data;
    }

    public static function sanitizeTagIds($inputTagIds, $willCreate = true)
    {
        if (!$inputTagIds) {
            return [];
        }

        $tagIds = [];
        $nonNumericIds = [];

        foreach ($inputTagIds as $tagId) {
            if (is_numeric($tagId)) {
                $tagIds[] = (int)$tagId;
            } else {
                $nonNumericIds[] = $tagId;
            }
        }

        if (!$nonNumericIds) {
            return $tagIds;
        }

        foreach ($nonNumericIds as $maybeNewTag) {
            if (strlen($maybeNewTag) < 3) {
                continue;
            }

            $exit = Tag::where('title', $maybeNewTag)
                ->orWhere('slug', $maybeNewTag)
                ->first();

            if ($exit) {
                $tagIds[] = $exit->id;
                continue;
            }

            if (!$willCreate) {
                continue;
            }

            // Let's create a new
            $tag = Tag::create([
                'title' => $maybeNewTag,
                'slug'  => sanitize_title($maybeNewTag)
            ]);

            $tagIds[] = $tag->id;
            do_action('fluent_crm/tag_created', $tag);
        }

        return $tagIds;
    }

    public static function sanitizeListIds($inputListIds, $willCreate = true)
    {
        if (!$inputListIds) {
            return [];
        }

        $listIds = [];
        $nonNumericIds = [];

        foreach ($inputListIds as $listId) {
            if (is_numeric($listId)) {
                $listIds[] = (int)$listId;
            } else {
                $nonNumericIds[] = $listId;
            }
        }

        if (!$nonNumericIds) {
            return $listIds;
        }

        foreach ($nonNumericIds as $maybeNewList) {
            if (strlen($maybeNewList) < 3) {
                continue;
            }

            $exit = Lists::where('title', $maybeNewList)
                ->orWhere('slug', $maybeNewList)
                ->first();

            if ($exit) {
                $listIds[] = $exit->id;
                continue;
            }

            if (!$willCreate) {
                continue;
            }

            // Let's create a new
            $list = Lists::create([
                'title' => sanitize_text_field($maybeNewList),
                'slug'  => sanitize_title($maybeNewList)
            ]);

            $listIds[] = $list->id;
            do_action('fluent_crm/list_created', $listIds);
        }

        return $listIds;
    }
}

<?php

namespace FluentCrm\App\Services;


class Sanitize
{
    public static function campaign($data)
    {
        $fieldMaps = [
            'title' => 'sanitize_text_field',
            'slug' => 'sanitize_text_field',
            'template_id' => 'intval',
            'email_subject' => 'sanitize_text_field',
            'email_pre_header' => 'sanitize_text_field',
            'utm_status' => 'intval',
            'utm_source'=> 'sanitize_text_field',
            'utm_medium' => 'sanitize_text_field',
            'utm_campaign' => 'sanitize_text_field',
            'utm_term' => 'sanitize_text_field',
            'utm_content' => 'sanitize_text_field',
            'scheduled_at' => 'sanitize_text_field',
            'design_template' => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }

    public static function contact($data)
    {
        $fieldMaps = [
            'hash' => 'sanitize_text_field',
            'prefix' => 'sanitize_text_field',
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'user_id' => 'intval',
            'email' => 'sanitize_email',
            'status' => 'sanitize_text_field',
            'contact_type' => 'sanitize_text_field',
            'address_line_1' => 'sanitize_text_field',
            'address_line_2' => 'sanitize_text_field',
            'postal_code' => 'sanitize_text_field',
            'city' => 'sanitize_text_field',
            'state' => 'sanitize_text_field',
            'country' => 'sanitize_text_field',
            'phone' => 'sanitize_text_field',
            'timezone' => 'sanitize_text_field',
            'date_of_birth' => 'sanitize_text_field',
            'source' => 'sanitize_text_field',
            'life_time_value' => 'sanitize_text_field',
            'last_activity' => 'sanitize_text_field',
            'total_points' => 'intval',
            'latitude' => 'sanitize_text_field',
            'longitude' => 'sanitize_text_field',
            'ip' => 'sanitize_text_field',
            'created_at' => 'sanitize_text_field',
            'updated_at' => 'sanitize_text_field',
            'avatar' => 'sanitize_url'
        ];

        foreach ($data as $key => $value) {
            if($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        if(isset($data['status'])) {
            $status = $data['status'];
            if(!in_array($status, fluentcrm_subscriber_statuses())) {
                unset($data['status']);
            }
        }

        return $data;
    }

    public static function contactNote($data)
    {
        $fieldMaps = [
            'subscriber_id' => 'intval',
            'parent_id' => 'intval',
            'created_by' => 'sanitize_text_field',
            'status' => 'sanitize_text_field',
            'type' => 'sanitize_text_field',
            'title' => 'sanitize_text_field',
            'description' => 'wp_kses_post',
            'created_at'  => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }

    public static function funnel($data)
    {
        $fieldMaps = [
            'type' => 'sanitize_text_field',
            'title' => 'sanitize_text_field',
            'trigger_name' => 'sanitize_text_field',
            'status' => 'sanitize_text_field',
            'created_by' => 'intval',
            'updated_at' => 'sanitize_text_field'
        ];

        foreach ($data as $key => $value) {
            if($value && isset($fieldMaps[$key]) && !is_array($value)) {
                $data[$key] = call_user_func($fieldMaps[$key], $value);
            }
        }

        return $data;
    }
}

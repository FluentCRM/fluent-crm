<?php

namespace FluentCrm\App\Services\Libs\Parser;

use FluentCart\App\Services\ShortCodeParser\SmartCodeParser;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class ShortcodeParser
{
    public function parse($templateString, $data)
    {
        $result = [];
        $isSingle = false;

        if (!is_array($templateString)) {
            $isSingle = true;
        }

        foreach ((array)$templateString as $key => $string) {
            $result[$key] = $this->parseShortcode($string, $data);
        }

        if ($isSingle) {
            return reset($result);
        }

        return $result;
    }

    public function parseCrmValue($templateString, $subscriber)
    {
        return preg_replace_callback('/({{|##)+(.*?)(}}|##)/', function ($matches) use ($subscriber) {
            return $this->replaceExtendedCrmValue($matches, $subscriber);
        }, $templateString);
    }

    public function replaceExtendedCrmValue($matches, $subscriber)
    {
        if (empty($matches[2])) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $matches[2] = trim($matches[2]);

        $matched = explode('.', $matches[2]);

        if (count($matched) <= 1) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $dataKey = trim(array_shift($matched));

        $valueKey = trim(implode('.', $matched));

        if (!$valueKey) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $valueKeys = explode('|', $valueKey);

        $valueKey = $valueKeys[0];
        $defaultValue = '';

        $valueCounts = count($valueKeys);

        if ($valueCounts >= 3) {
            $defaultValue = trim($valueKeys[1]);
        } else if ($valueCounts === 2) {
            $defaultValue = trim($valueKeys[1]);
        }

        return $this->getCrmValue($valueKey, $defaultValue, $subscriber);
    }

    public function parseShortcode($string, $data)
    {
        return preg_replace_callback('/({{|##)+(.*?)(}}|##)/', function ($matches) use ($data) {
            return $this->replace($matches, $data);
        }, $string);
    }

    protected function replace($matches, $subscriber)
    {
        if (empty($matches[2])) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $matches[2] = trim($matches[2]);

        $matched = explode('.', $matches[2]);

        if (count($matched) <= 1) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $dataKey = trim(array_shift($matched));

        $valueKey = trim(implode('.', $matched));

        if (!$valueKey) {
            return apply_filters('fluentcrm_smartcode_fallback', $matches[0], $subscriber);
        }

        $valueKeys = explode('|', $valueKey);

        $valueKey = $valueKeys[0];
        $defaultValue = '';
        $transformer = '';

        $valueCounts = count($valueKeys);

        if ($valueCounts >= 3) {
            $defaultValue = trim($valueKeys[1]);
            $transformer = trim($valueKeys[2]);
        } else if ($valueCounts === 2) {
            $defaultValue = trim($valueKeys[1]);
        }

        if (!$subscriber) {
            return $defaultValue;
        }

        $value = '';

        switch ($dataKey) {
            case 'contact':
                $value = $this->getSubscriberValue($subscriber, $valueKey, $defaultValue);
                break;
            case 'wp':
                $value = $this->getWpValue($valueKey, $defaultValue, $subscriber);
                break;
            case 'crm':
                /*
                 * We need to check this condition. Most probably we are parsing these smartcodes later
                 * I am restricting this for now. Need to check later
                 * @todo: Urgent
                 */
                $urlKeys = [
                    'unsubscribe_url',
                    'manage_subscription_url',
                    'unsubscribe_html',
                    'manage_subscription_html'
                ];

                if (in_array($valueKey, $urlKeys)) {
                    return $matches[0]; //  we will replace these later.
                }

                $value = $this->getCrmValue($valueKey, $defaultValue, $subscriber);
                break;
            case 'user':
                $value = $this->getUserValue($valueKey, $defaultValue, $subscriber);
                break;
            case 'other':
                $value = $this->parseOtherValue($valueKey, $defaultValue, $subscriber);
                break;
            default:
                $value = apply_filters('fluent_crm/smartcode_group_callback_' . $dataKey, $matches[0], $valueKey, $defaultValue, $subscriber);
        }

        if ($transformer && is_string($transformer) && $value) {
            switch ($transformer) {
                case 'trim':
                    return trim($value);
                case 'ucfirst':
                    return ucfirst($value);
                case 'strtolower':
                    return strtolower($value);
                case 'strtoupper':
                    return strtoupper($value);
                case 'ucwords':
                    return ucwords($value);
                case 'concat_first': // usage: {{contact.first_name||concat_first|Hi
                    if (isset($valueKeys[3])) {
                        $value = trim($valueKeys[3] . ' ' . $value);
                    }
                    return $value;
                case 'concat_last': // usage: {{contact.first_name||concat_last|, => FIRST_NAME,
                    if (isset($valueKeys[3])) {
                        $value = trim($value . '' . $valueKeys[3]);
                    }
                    return $value;
                case 'show_if': // usage {{contact.first_name||show_if|First name exist
                    if (isset($valueKeys[3])) {
                        $value = $valueKeys[3];
                    }
                    return $value;
                default:
                    return $value;
            }
        }

        return $value;

    }

    protected function getWpValue($valueKey, $defaultValue, $subscriber = [])
    {
        $value = get_bloginfo($valueKey);
        if (!$value) {
            return $defaultValue;
        }
        return $value;
    }

    protected function getCrmValue($valueKey, $defaultValue = '', $subscriber = [])
    {
        switch ($valueKey) {
            case "unsubscribe_url":
                return add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'unsubscribe',
                    'ce_id'       => $subscriber->email_id,
                    'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
                ]), site_url('/'));
            case "manage_subscription_url":
                return add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'manage_subscription',
                    'ce_id'       => $subscriber->id,
                    'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
                ]), site_url('/'));
            case "unsubscribe_html":
                if (!$defaultValue) {
                    $defaultValue = __('Unsubscribe', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'unsubscribe',
                    'ce_id'       => $subscriber->email_id,
                    'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
                ]), site_url('/'));

                return '<a class="fc_unsub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "manage_subscription_html":
                if (!$defaultValue) {
                    $defaultValue = __('Email Preference', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'manage_subscription',
                    'ce_id'       => $subscriber->id,
                    'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
                ]), site_url('/'));

                return '<a class="fc_msub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "activate_button":
                if (!$defaultValue) {
                    $defaultValue = __('Confirm Subscription', 'fluent-crm');
                }
                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'confirmation',
                    'hash'        => $subscriber->hash,
                    'secure_hash' => $subscriber->getSecureHash()
                ]), site_url('/'));

                return '<a style="color: #ffffff; background-color: #454545; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: normal; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;" href="' . $url . '">' . $defaultValue . '</a>';
            case "business_name":
                $business = fluentcrmGetGlobalSettings('business_settings', []);
                $businessName = Arr::get($business, 'business_name');
                return (!empty($businessName)) ? $businessName : $defaultValue;
            case "business_address":
                $business = fluentcrmGetGlobalSettings('business_settings', []);
                $address = Arr::get($business, 'business_address', $defaultValue);
                return (!empty($address)) ? $address : $defaultValue;
            default:
                return $defaultValue;
        }
    }

    protected function getSubscriberValue($subscriber, $valueKey, $defaultValue)
    {
        if (!$subscriber || !$subscriber instanceof Subscriber) {
            return $defaultValue; // We don't have subscriber
        }

        $valueKeys = explode('.', $valueKey);

        if (count($valueKeys) == 1) {
            $data = $subscriber->toArray();

            if ($valueKey == 'full_name') {
                return $subscriber->full_name;
            }

            $value = Arr::get($data, $valueKey);

            return ($value) ? $value : $defaultValue;
        }

        $customKey = $valueKeys[0];
        $customProperty = $valueKeys[1];

        if ($customKey == 'custom') {
            $existingCustomFields = fluentcrm_get_custom_contact_fields();
            $customValues = $subscriber->custom_fields();

            $value = Arr::get($customValues, $customProperty, $defaultValue);
            if (is_array($value)) {
                return implode(', ', $value);
            }

            $multiLines = preg_split("/\r\n|\n|\r/", $value);

            if (!$multiLines) {
                return $value;
            }

            $formattedValue = implode('<br/> ', $multiLines);

            // Find the custom field
            $fieldKeys = array_column($existingCustomFields, 'slug');
            $customFieldIndex = array_search($customProperty, $fieldKeys);

            if ($customFieldIndex === false) {
                return $formattedValue;
            }

            $matchedObject = $existingCustomFields[$customFieldIndex];

            // Format date or date_time fields
            $timestamp = strtotime($formattedValue);

            if ($timestamp && in_array($matchedObject['type'], ['date', 'date_time'])) {
                $date_format = get_option('date_format');

                if ($matchedObject['type'] === 'date_time') {
                    $time_format = get_option('time_format');
                    $date_format .= ' ' . $time_format; // Append time format
                }

                $formattedValue = date_i18n($date_format, $timestamp);
            }
//            $formattedValue =  htmlspecialchars($formattedValue, ENT_QUOTES, 'UTF-8');
            return $formattedValue;
        }

        if ($customKey == 'company') {
            if (!Helper::isCompanyEnabled()) {
                return $defaultValue;
            }

            $company = $subscriber->company;
            if (!$company) {
                return $defaultValue;
            }

            if ($customProperty == 'address') {
                $address = array_filter([
                    $company->address_line_1,
                    $company->address_line_2,
                    $company->city,
                    $company->state,
                    $company->postal_code,
                    $company->country
                ]);

                if (!$address) {
                    return $defaultValue;
                }

                return implode(', ', $address);
            }

            $acceptedFields = [
                'name',
                'industry',
                'email',
                'timezone',
                'address_line_1',
                'address_line_2',
                'postal_code',
                'city',
                'state',
                'country',
                'employees_number',
                'description',
                'phone',
                'logo',
                'website',
                'linkedin_url',
                'twitter_url',
                'facebook_url',
                'date_of_start',
            ];

            if (!in_array($customProperty, $acceptedFields)) {
                return $defaultValue;
            }

            $companyValue = $company->{$customProperty};

            return ($companyValue) ? $companyValue : $defaultValue;
        }

        if ($customKey == 'tags') {
            $tagsArray = [];
            foreach ($subscriber->tags as $tag) {
                $tagsArray[] = $tag->{$customProperty};
            }

            if ($tagsArray) {
                return implode(', ', $tagsArray);
            }
        } else if ($customKey == 'lists') {
            $tagsArray = [];
            foreach ($subscriber->lists as $tag) {
                $tagsArray[] = $tag->{$customProperty};
            }

            if ($tagsArray) {
                return implode(', ', $tagsArray);
            }
        } else if ($customKey == 'meta') {
            if ($customProperty == '_secure_hash') {
                return fluentCrmGetContactSecureHash($subscriber->id);
            }

            if ($customKey == '_secure_managed_hash') {
                return fluentCrmGetContactManagedHash($subscriber->id);
            }
        }

        return $defaultValue;
    }

    protected function getUserValue($valueKey, $defaultValue, $subscriber)
    {
        if (!$subscriber || !$subscriber instanceof Subscriber) {
            return $defaultValue;
        }

        $wpUser = $subscriber->getWpUser();

        if (!$wpUser) {
            return $defaultValue;
        }

        if ($valueKey == 'password_reset_direct_link') {

            if (defined('FLUENTCRM_PREVIEWING_EMAIL')) {
                return '#pasword_reset_link_will_be_inserted_on_real_email';
            }

            $key = get_password_reset_key($wpUser);
            if (is_wp_error($key)) {
                return $defaultValue;
            }
            return network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($wpUser->user_login), 'login');
        }

        $valueKeys = explode('.', $valueKey);
        if (count($valueKeys) == 1) {
            $value = $wpUser->get($valueKey);
            if (!$value) {
                return $defaultValue;
            }

            if (!is_array($value) || !is_object($value)) {
                return $value;
            }

            return $defaultValue;
        }

        $customKey = $valueKeys[0];
        $customProperty = $valueKeys[1];

        if ($customKey == 'meta') {
            $metaValue = get_user_meta($wpUser->id, $customProperty, true);
            if (!$metaValue) {
                return $defaultValue;
            }

            if (!is_array($metaValue) || !is_object($metaValue)) {
                return $metaValue;
            }

            return $defaultValue;
        }

        return $defaultValue;
    }

    protected function parseOtherValue($valueKey, $defaultValue, $subscriber)
    {
        $valueKeys = explode('.', $valueKey);

        if (count($valueKeys) == 1) {
            return $defaultValue;
        }

        $key = $valueKeys[0];

        $otherKey = $valueKeys[1];

        if (!$otherKey) {
            return $defaultValue;
        }

        if ($key == 'latest_post') {
            // get latest post title
            $posts = get_posts([
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 1,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => 1
            ]);

            if (!count($posts)) {
                return $defaultValue;
            }

            $post = $posts[0];

            if ($otherKey == 'title') {
                return $post->post_title;
            }

            if ($otherKey == 'content') {
                return get_the_content(null, false, $post);
            }

            if ($otherKey == 'excerpt') {
                return get_the_excerpt($post);
            }

            return $post->post_title;
        }

        if ($key == 'date_format') {
            array_shift($valueKeys);
            $formatKey = implode('.', $valueKeys);

            if (!$formatKey) {
                $formatKey = get_option('date_format');
            }

            return date_i18n($formatKey, current_time('timestamp'));
        }

        if ($key == 'date') {
            array_shift($valueKeys);
            array_shift($valueKeys);
            $formatKey = implode('.', $valueKeys);

            if (!$formatKey) {
                $formatKey = get_option('date_format');
            }

            $timeStamp = strtotime($otherKey);

            $timeStamp += (int)(get_option('gmt_offset') * HOUR_IN_SECONDS);

            return date_i18n($formatKey, $timeStamp);
        }

        return $defaultValue;

    }
}

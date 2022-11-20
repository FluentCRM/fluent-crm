<?php

namespace FluentCrm\App\Services\Libs\Parser;

use FluentCrm\App\Models\Subscriber;
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

        $value = '';

        switch ($dataKey) {
            case 'contact':
                $value = $this->getSubscriberValue($subscriber, $valueKey, $defaultValue);
                break;
            case 'wp':
                $value = $this->getWpValue($valueKey, $defaultValue, $subscriber);
                break;
            case 'crm':
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
                $value = apply_filters('fluentcrm_smartcode_fallback_callback_' . $dataKey, $matches[0], $valueKey, $defaultValue, $subscriber);
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
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
                ]), site_url('/'));
            case "manage_subscription_url":
                return add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'manage_subscription',
                    'ce_id'       => $subscriber->id,
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
                ]), site_url('/'));
            case "unsubscribe_html":
                if ($defaultValue) {
                    $defaultValue = __('Unsubscribe', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'unsubscribe',
                    'ce_id'       => $subscriber->email_id,
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
                ]), site_url('/'));

                return '<a class="fc_unsub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "manage_subscription_html":
                if ($defaultValue) {
                    $defaultValue = __('Email Preference', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'manage_subscription',
                    'ce_id'       => $subscriber->id,
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
                ]), site_url('/'));

                return '<a class="fc_msub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "activate_button":
                if (!$defaultValue) {
                    $defaultValue = __('Confirm Subscription', 'fluent-crm');
                }
                $data = $subscriber->toArray();
                $url = add_query_arg(array_filter([
                    'fluentcrm'   => 1,
                    'route'       => 'confirmation',
                    's_id'        => $data['id'],
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
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
            return ''; // We don't have subscriber
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
            $customValues = $subscriber->custom_fields();
            $value = Arr::get($customValues, $customProperty, $defaultValue);
            if (is_array($value)) {
                return implode(', ', $value);
            }
            if ($value) {
                return $value;
            }
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

        if ($key == 'date') {
            return date_i18n(get_option('date_format'), strtotime($otherKey));
        }

        return $defaultValue;

    }
}

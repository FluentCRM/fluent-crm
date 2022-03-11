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
        if (isset($valueKeys[1])) {
            $defaultValue = trim($valueKeys[1]);
        }

        switch ($dataKey) {
            case 'contact':
                return $this->getSubscriberValue($subscriber, $valueKey, $defaultValue);
            case 'wp':
                return $this->getWpValue($valueKey, $defaultValue, $subscriber);
            case 'crm':
                return $this->getCrmValue($valueKey, $defaultValue, $subscriber);
            case 'user':
                return $this->getUserValue($valueKey, $defaultValue, $subscriber);
            default:
                return apply_filters('fluentcrm_smartcode_fallback_callback_' . $dataKey, $matches[0], $valueKey, $defaultValue, $subscriber);
        }

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
                    'fluentcrm' => 1,
                    'route'     => 'unsubscribe',
                    'ce_id'     => $subscriber->email_id,
                    'hash'      => $subscriber->hash
                ]), site_url('/'));
            case "manage_subscription_url":
                return add_query_arg(array_filter([
                    'fluentcrm' => 1,
                    'route'     => 'manage_subscription',
                    'ce_id'     => $subscriber->id,
                    'hash'      => $subscriber->hash
                ]), site_url('/'));
            case "unsubscribe_html":
                if ($defaultValue) {
                    $defaultValue = __('Unsubscribe', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm' => 1,
                    'route'     => 'unsubscribe',
                    'ce_id'     => $subscriber->email_id,
                    'hash'      => $subscriber->hash
                ]), site_url('/'));

                return '<a class="fc_unsub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "manage_subscription_html":
                if ($defaultValue) {
                    $defaultValue = __('Email Preference', 'fluent-crm');
                }

                $url = add_query_arg(array_filter([
                    'fluentcrm' => 1,
                    'route'     => 'manage_subscription',
                    'ce_id'     => $subscriber->id,
                    'hash'      => $subscriber->hash
                ]), site_url('/'));

                return '<a class="fc_msub_url" href="' . $url . '">' . $defaultValue . '</a>';
            case "activate_button":
                if (!$defaultValue) {
                    $defaultValue = __('Confirm Subscription', 'fluent-crm');
                }
                $data = $subscriber->toArray();
                $url = site_url('?fluentcrm=1&route=confirmation&s_id=' . $data['id'] . '&hash=' . $data['hash']);
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
        }

        if ($customKey == 'lists') {
            $tagsArray = [];
            foreach ($subscriber->lists as $tag) {
                $tagsArray[] = $tag->{$customProperty};
            }

            if ($tagsArray) {
                return implode(', ', $tagsArray);
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
            return $value;
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
}

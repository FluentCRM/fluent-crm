<?php

namespace FluentCrm\Includes\Parser;

use FluentCrm\Includes\Helpers\Arr;

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

        if ($dataKey == 'contact') {
            return $this->getSubscriberValue($subscriber, $valueKey, $defaultValue);
        }


        if ($dataKey == 'wp') {
            return $this->getWpValue($valueKey, $defaultValue, $subscriber);
        }

        if ($dataKey == 'crm') {
            return $this->getCrmValue($valueKey, $defaultValue, $subscriber);
        }

        return apply_filters('fluentcrm_smartcode_fallback_callback_' . $dataKey, $matches[0], $valueKey, $defaultValue, $subscriber);
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
        if ($valueKey == 'unsubscribe_url') {
            return add_query_arg(array_filter([
                'fluentcrm' => 1,
                'route' => 'unsubscribe',
                'ce_id' => $subscriber->email_id,
                'hash' => $subscriber->hash
            ]), site_url());
        }

        if ($valueKey == 'manage_subscription_url') {
            return add_query_arg(array_filter([
                'fluentcrm' => 1,
                'route' => 'manage_subscription',
                'ce_id' => $subscriber->id,
                'hash' => $subscriber->hash
            ]), site_url());
        }


        if ($valueKey == 'activate_button') {
            if (!$defaultValue) {
                $defaultValue = 'Confirm Subscription';
            }
            $data = $subscriber->toArray();
            $url = site_url('?fluentcrm=1&route=confirmation&s_id=' . $data['id'] . '&hash=' . $data['hash']);
            return '<a style="color: #ffffff; background-color: #454545; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: normal; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;" href="' . $url . '">' . $defaultValue . '</a>';
        }

        if ($valueKey == 'business_name') {
            $business = fluentcrmGetGlobalSettings('business_settings', []);
            $businessName = Arr::get($business, 'business_name');
            return ($businessName) ? $businessName : $defaultValue;
        }

        if ($valueKey == 'business_address') {
            $business = fluentcrmGetGlobalSettings('business_settings', []);
            $address = Arr::get($business, 'business_address', $defaultValue);
            return ($address) ? $address : $defaultValue;
        }

        return $defaultValue;
    }

    protected function getSubscriberValue($subscriber, $valueKey, $defaultValue)
    {
        $valueKeys = explode('.', $valueKey);

        if (count($valueKeys) == 1) {
            $data = $subscriber->toArray();

            if($valueKey == 'full_name') {
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
            if($value) {
                return $value;
            }
        }

        return $defaultValue;
    }
}

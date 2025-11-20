<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Subscriber;

defined('ABSPATH') || exit;


/**
 * Extend API Wrapper for FluentCRM FluentCrmApi('extend')
 *
 * Contacts API Wrapper Class that can be used as <code>FluentCrmApi('extend')</code> to get the class instance
 *
 * @package FluentCrm\App\Api\Classes
 * @namespace FluentCrm\App\Api\Classes
 *
 * @version 1.0.0
 */
final class Extender
{
    public function addProfileSection($key, $sectionTitle, $callback, $saveCallback = null)
    {
        add_filter('fluentcrm_profile_sections', function ($sections) use ($key, $sectionTitle) {
            $sections[$key] = [
                'name'    => 'fluentcrm_profile_extended',
                'title'   => $sectionTitle,
                'handler' => 'route',
                'query'   => [
                    'handler' => $key
                ]
            ];
            return $sections;
        });

        add_filter('fluencrm_profile_section_' . $key, function ($content, $subscriber) use ($callback) {
            if (is_callable($callback)) {
                return $callback($content, $subscriber);
            }
            return $content;
        }, 10, 2);

        if ($saveCallback) {
            add_filter('fluencrm_profile_section_save_' . $key, function ($response, $data, $subscriber) use ($saveCallback) {
                if (is_callable($saveCallback)) {
                    return $saveCallback($response, $data, $subscriber);
                }
                return $response;
            }, 10, 3);
        }
    }

    public function addCompanyProfileSection($key, $sectionTitle, $callback, $saveCallback = null)
    {
        add_filter('fluent_crm/company_profile_sections', function ($sections) use ($key, $sectionTitle) {
            $sections[$key] = [
                'name'    => 'fluent_crm_company_section_extended',
                'title'   => $sectionTitle,
                'handler' => 'route',
                'query'   => [
                    'handler' => $key
                ]
            ];
            return $sections;
        });

        add_filter('fluent_crm/company_profile_section_' . $key, function ($content, $subscriber) use ($callback) {
            if (is_callable($callback)) {
                return $callback($content, $subscriber);
            }
            return $content;
        }, 10, 2);

        if ($saveCallback) {
            add_filter('fluent_crm/company_profile_section_save_' . $key, function ($response, $data, $company) use ($saveCallback) {
                if (is_callable($saveCallback)) {
                    return $saveCallback($response, $data, $company);
                }
                return $response;
            }, 10, 3);
        }
    }

    public function addSmartCode($key, $title, $shortcodes, $callback)
    {
        $reservedKeys = [
            'crm',
            'other',
            'contact',
            'wp',
            'fluentcrm',
            'user',
            'learndash',
            'tutorlms',
            'aff_wp',
            'edd_customer',
            'lifterlms',
            'woo_customer'
        ];

        if (in_array($key, $reservedKeys)) {
            return;
        }

        /*
         * this is shortcode processor function
         */
        add_filter('fluent_crm/extended_smart_codes', function ($groups) use ($key, $title, $shortcodes) {
            $groups[] = [
                'key'        => $key,
                'title'      => $title,
                'shortcodes' => $this->formatShortcodes($key, $shortcodes)
            ];
            return $groups;
        }, 100);
        /*
         * This is the callback function for the shortcode parser
         */
        add_filter('fluent_crm/smartcode_group_callback_' . $key, function ($code, $valueKey, $defaultValue, $subscriber) use ($callback) {
            if (is_callable($callback)) {
                return $callback($code, $valueKey, $defaultValue, $subscriber);
            }
            return $code; // return the code if no parser function is provided
        }, 10, 4);
    }

    /**
     * @param $groupKey string
     * @param $shortcodes array
     * @return array
     */
    private function formatShortcodes($groupKey, $shortcodes)
    {
        $processed = [];
        foreach ($shortcodes as $key => $title) {
            $processed['{{' . $groupKey . '.' . $key . '}}'] = $title;
        }
        return $processed;
    }

    public function addContactWidget($callback, $priority = 20)
    {
        add_filter('fluent_crm/subscriber_info_widgets', function ($widgets, $subscriber) use ($callback) {
            if (is_callable($callback)) {
                $data = $callback($subscriber);
                if (is_array($data) && isset($data['title']) && isset($data['content'])) {
                    $widgets[] = [
                        'title'   => $data['title'],
                        'content' => $data['content']
                    ];
                }
            }
            return $widgets;
        }, $priority, 2);
    }

    public function getCompaniesByContactEmail($email)
    {
        $subscriber = Subscriber::where('email', $email)->with('companies')->first();
        if (!$subscriber) {
            return [];
        }

        return $subscriber->companies;
    }
}

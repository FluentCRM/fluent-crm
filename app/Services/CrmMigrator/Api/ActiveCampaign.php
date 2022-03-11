<?php

namespace FluentCrm\App\Services\CrmMigrator\Api;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ActiveCampaign
{
    protected $apiUrl = null;

    protected $apiKey = null;

    public function __construct($apiUrl, $apiKey = null)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function default_options()
    {
        return array(
            'api_key'    => $this->apiKey,
            'api_output' => 'json'
        );
    }

    public function make_request($action, $options = array(), $method = 'GET')
    {
        /* Build request options string. */
        $request_options = $this->default_options();
        $request_options['api_action'] = $action;

        if ($request_options['api_action'] == 'contact_edit')
            $request_options['overwrite'] = '0';

        $request_options = http_build_query($request_options);
        $request_options .= ($method == 'GET') ? '&' . http_build_query($options) : null;

        /* Build request URL. */
        $request_url = untrailingslashit($this->apiUrl) . '/admin/api.php?' . $request_options;

        /* Execute request based on method. */
        switch ($method) {

            case 'POST':
                $args = array('body' => $options);
                $response = wp_remote_post($request_url, $args);
                break;

            case 'GET':
                $response = wp_remote_get($request_url, [
                    'timeout' => 10
                ]);
                break;
        }

        /* If WP_Error, die. Otherwise, return decoded JSON. */
        if (is_wp_error($response)) {
            return $response;
        } else {
            $result = json_decode($response['body'], true);

            if ($result && isset($result['result_code']) && $result['result_code'] == 0) {
                $message = __('Invalid API', 'fluent-crm');

                if (!empty($result['message'])) {
                    $message = $result['message'];
                } else if (!empty($result['error'])) {
                    $message = $result['error'];
                }
                return new \WP_Error('API_Error', $message);
            } else if(wp_remote_retrieve_response_code($response) > 300) {
                return new \WP_Error('API_Error', 'ActiveCampaign API Resquest Failed');
            }

            return $result;
        }
    }

    /**
     * Test the provided API credentials.
     *
     * @access public
     * @return bool
     */
    public function auth_test()
    {
        /* Build options string. */
        $request_options = $this->default_options();
        $request_options['api_action'] = 'list_paginator';
        $request_options = http_build_query($request_options);

        /* Setup request URL. */
        $request_url = untrailingslashit($this->apiUrl) . '/admin/api.php?' . $request_options;

        /* Execute request. */
        $response = wp_remote_get($request_url);

        /* If invalid content type, API URL is invalid. */
        if (is_wp_error($response))
            return $response;
        if (strpos($response['headers']['content-type'], 'application/json') != 0 && strpos($response['headers']['content-type'], 'application/json') > 0) {
            return new \WP_Error('error', 'Invalid API URL');
        }

        /* If result code is false, API key is invalid. */
        $response['body'] = json_decode($response['body'], true);
        if ($response['body']['result_code'] == 0) {
            $message = 'Invalid API';
            return new \WP_Error('API_Error', $message);
        }

        return true;
    }

    /**
     * Get all custom list fields.
     *
     * @access public
     * @return array
     */
    public function get_custom_fields()
    {
        return $this->make_request('list_field_view', array('ids' => 'all'));
    }

    public function getContacts($args = ['ids' => 'all', 'full' => 1])
    {
        return $this->make_request('contact_list', $args);
    }

    public function contactPaginator($args = ['limit' => 20, 'public' => 0])
    {
        return $this->make_request('contact_paginator', $args);
    }

    /**
     * Get all lists in the system.
     *
     * @access public
     * @return array
     */
    public function get_lists()
    {
        return $this->make_request('list_list', array('ids' => 'all'));
    }

    public function getTags()
    {
        return $this->make_request('tags_list', array('ids' => 'all'));
    }
}

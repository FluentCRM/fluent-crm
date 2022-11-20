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
        $response = null;
        /* Execute request based on method. */
        switch ($method) {

            case 'POST':
                $args = array(
                    'body' => $options,
                    'timeout' => 30
                );
                $response = wp_remote_post($request_url, $args);
                break;

            case 'GET':
                $response = wp_remote_get($request_url, [
                    'timeout' => 30
                ]);
                break;
        }

        $error = $this->maybeError($response);
        if ($error) {
            return $error;
        }
        return json_decode($response['body'], true);
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

        $error = $this->maybeError($response);
        if ($error) {
            return $error;
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

    public function maybeError($response)
    {
        /* If invalid content type, API URL is invalid. */
        if (is_wp_error($response))
            return $response;
        if (strpos($response['headers']['content-type'], 'application/json') != 0 && strpos($response['headers']['content-type'], 'application/json') > 0) {
            return new \WP_Error('error', 'Invalid API URL');
        }

        if ($response['response']['code'] > 300) {
            return new \WP_Error('API_Error', $response['response']['message'], $response);
        }

        $body = json_decode($response['body'], true);
        if (isset($body['result_code']) && $body['result_code'] == 0) {
            $message = 'Invalid API';
            return new \WP_Error('API_Error', $message, $response);
        }

        return null;
    }
}

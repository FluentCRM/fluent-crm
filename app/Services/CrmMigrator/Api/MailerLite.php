<?php

namespace FluentCrm\App\Services\CrmMigrator\Api;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class MailerLite
{
    protected $apiUrl = 'https://api.mailerlite.com/api/v2/';

    protected $apiKey = null;

    protected $apiSecret = null;

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    public function default_options()
    {
        return [
            'User-Agent'          => 'MailerLite PHP SDK/2.0',
            'X-MailerLite-ApiKey' => $this->apiKey,
            'Content-Type'        => 'application/json'
        ];
    }

    public function make_request($action, $options = array(), $method = 'GET')
    {

        $headers = $this->default_options();
        $endpointUrl = $this->apiUrl . $action;
        $args = [
            'headers' => $headers
        ];

        if ($options && $method == 'POST') {
            $args['body'] = \json_encode($options);
        } else if($method == 'GET' && $options) {
            $endpointUrl = add_query_arg($options, $endpointUrl);
        }

        /* Execute request based on method. */
        switch ($method) {
            case 'POST':
                $response = wp_remote_post($endpointUrl, $args);
                break;

            case 'GET':
                $response = wp_remote_get($endpointUrl, $args);
                break;
        }

        /* If WP_Error, die. Otherwise, return decoded JSON. */
        if (is_wp_error($response)) {
            return new \WP_Error('API_Error', $response->get_error_message());
        } else if ($response && $response['response']['code'] >= 300) {
            return new \WP_Error('API_Error', $response['response']['message']);
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
        return $this->make_request('groups', [], 'GET');
    }

    /**
     * Get all Forms in the system.
     *
     * @access public
     * @return array
     */
    public function getGroups()
    {
        return $this->make_request('groups', array(), 'GET');
    }

    public function getGroupSubscribers($groupId, $args = [])
    {
        return $this->make_request('groups/' . $groupId . '/subscribers', $args, 'GET');
    }

    public function getContactCountByGroup($groupId)
    {
        $result = $this->make_request('groups/' . $groupId . '/subscribers/count', array(), 'GET');

        if (is_wp_error($result)) {
            return $result;
        }

        return $result['count'];
    }

    public function getCustomFields()
    {
        return $this->make_request('fields', array(), 'GET');
    }

}

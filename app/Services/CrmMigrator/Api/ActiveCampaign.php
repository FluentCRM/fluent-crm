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

    public function getContacts($args = ['offset' => 0, 'status' => -1])
    {

        $args = wp_parse_args(
            $args,
            array(
                'offset'  => 0,
                'status'  => $args['status'],
                'include' => 'contactTags,contactLists,fieldValues',
                'limit'   => 100,
                'api_key' => $this->apiKey,
            )
        );

        $subscribers = array();

        $request  = add_query_arg( $args, untrailingslashit( $this->apiUrl ) . '/api/3/contacts' );
        $response = wp_safe_remote_get( $request );

        $error = $this->maybeError( $response );
        if ( $error ) {
            return $error;
        }

        $response = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! empty( $response->contacts ) ) {

            // Base subscriber data.

            foreach ( $response->contacts as $contact ) {

                $subscribers[ $contact->id ] = array(
                    'first_name' => $contact->{'firstName'},
                    'last_name'  => $contact->{'lastName'},
                    'email'      => $contact->{'email'},
                    'phone'      => $contact->{'phone'},
                    'cdate'      => $contact->{'cdate'},
                    'ip'         => $contact->{'ip'},
                    'status'     => 1, // @todo this should be checked based on the list membership.
                    'tags'       => array(),
                    'lists'      => array(),
                    'fields'     => array(),
                );

            }

            // Fields.

            if ( ! empty( $response->{'fieldValues'} ) ) {

                foreach ( $response->{'fieldValues'} as $field ) {

                    if ( false !== strpos( $field->value, '||' ) ) {
                        $type = 'checkbox';
                    } else {
                        $type = 'text';
                    }

                    $subscribers[ $field->contact ]['fields'][] = array(
                        'val'     => $field->value,
                        'perstag' => $field->field,
                        'type'    => $type,
                    );
                }
            }

            // Tags.

            if ( ! empty( $response->{'contactTags'} ) ) {

                foreach ( $response->{'contactTags'} as $tag ) {
                    $subscribers[ $tag->contact ]['tags'][] = $tag->tag;
                }
            }


            // Lists.

            if ( ! empty( $response->{'contactLists'} ) ) {

                foreach ( $response->{'contactLists'} as $list ) {
                    $subscribers[ $list->contact ]['lists'][] = array( 'listid' => $list->list );
                }
            }
        }

        return $subscribers;

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

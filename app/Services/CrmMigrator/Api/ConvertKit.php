<?php

namespace FluentCrm\App\Services\CrmMigrator\Api;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ConvertKit
{
	protected $apiUrl = 'https://api.convertkit.com/v3/';

	protected $apiKey = null;

	protected $apiSecret = null;

	public function __construct( $apiKey = null, $apiSecret = null )
	{
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
	}

	public function default_options()
	{
		return array(
			'api_key'    => $this->apiKey
		);
	}

	public function make_request( $action, $options = array(), $method = 'GET' )
	{
		/* Build request options string. */
		$request_options = $this->default_options();

        $request_options = wp_parse_args($options, $request_options);
		$options_string  = http_build_query( $request_options );

		/* Execute request based on method. */
		switch ( $method ) {
			case 'POST':
				$args = array(
				    'body' => json_encode($options),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ]
                );
				$response = wp_remote_post( $this->apiUrl.$action.'?api_key='.$this->apiKey, $args );
				break;

			case 'GET':
                /* Build request URL. */
                $request_url = $this->apiUrl  . $action.'?' . $options_string;
				$response = wp_remote_get( $request_url );
				break;
		}

		/* If WP_Error, die. Otherwise, return decoded JSON. */
		if ( is_wp_error( $response ) ) {
			return [
			    'error' => __('API_Error', 'fluent-crm'),
                'message' => $response->get_error_message()
            ];
		} else {
			return json_decode( $response['body'], true );
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
	    return $this->make_request('forms', [], 'GET');
	}


	public function subscribe($formId, $data)
    {
        $response = $this->make_request('forms/'.$formId.'/subscribe', $data, 'POST');
        if(!empty($response['error'])) {
            return new \WP_Error('api_error', $response['message']);
        }

        return $response['subscription'];
    }

	/**
	 * Get all Forms in the system.
	 *
	 * @access public
	 * @return array
	 */
	public function getLists()
	{
		$response = $this->make_request( 'forms', array(), 'GET' );
		if(empty($response['error'])) {
		    return $response['forms'];
        }
        return [];
	}

    /**
     * Get all Tags in the system.
     *
     * @access public
     * @return array
     */
    public function getTags()
    {
        $response = $this->make_request( 'tags', array(), 'GET' );
        if(empty($response['error'])) {
            return $response['tags'];
        }

        return false;
    }

    public function getCustomFields()
    {
        $response = $this->make_request( 'custom_fields', array(), 'GET' );
        if(empty($response['error'])) {
            return $response['custom_fields'];
        }

        return false;
    }

    public function getSubscribers($args = [])
    {
        $args['api_secret'] = $this->apiSecret;
        $response = $this->make_request( 'subscribers', $args, 'GET' );

        if(empty($response['error'])) {
            return $response;
        }

        new \WP_Error('api_error', $response['message']);
    }

    public function getTagSubscribers($tagId, $args = [])
    {
        $args['api_secret'] = $this->apiSecret;
        $response = $this->make_request( 'tags/'.$tagId.'/subscriptions', $args, 'GET' );

        if(empty($response['error'])) {
            return $response;
        }

        new \WP_Error('api_error', $response['message']);
    }

    public function getSubscriberTags($contactId)
    {
        $args['api_secret'] = $this->apiSecret;
        $response = $this->make_request( 'subscribers/'.$contactId.'/tags', $args, 'GET' );

        if(empty($response['error'])) {
            return $response['tags'];
        }

        new \WP_Error('api_error', $response['message']);
    }

}

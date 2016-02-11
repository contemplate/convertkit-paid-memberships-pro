<?php

/**
 * ConvertKit API specific functionality
 *
 * @link       http://www.convertkit.com
 * @since      1.0.0
 *
 * @package    ConvertKit_PMP
 * @subpackage ConvertKit_PMP/includes
 */

/**
 * ConvertKit API specific functionality.
 *
 * Handles all API calls.
 *
 * @package    ConvertKit_PMP
 * @subpackage ConvertKit_PMP/includes
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class ConvertKit_PMP_API {

	/** @var  string $api_version */
	protected $api_version = 'v3';

	/** @var  string $api_url */
	protected $api_url = 'https://api.convertkit.com/';

	/** @var  string $api_key The customer's ConvertKit API key */
	protected $api_key;

	/** @var   */
	protected $forms;

	/** @var  array $tags Tags in the customer's account */
	protected $tags;


	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $api_key ) {

		$this->api_key = $api_key;

	}

	/**
	 * Get an array of forms and IDs from the API
	 *
	 * @return mixed
	 */
	public function get_forms(){

		$forms = get_transient( 'convertkit_pmp_form_data' );

		if( false === $forms ) {
			$data = $this->do_api_call( 'forms' );

			if( ! is_wp_error( $data ) ) {

				$forms = $data;
				set_transient( 'convertkit_pmp_form_data', $forms, 24*24 );
			}
		}

		if ( ! empty( $forms ) && isset( $forms['forms'] ) && ! empty( $forms['forms'] ) ) {

			foreach( $forms['forms'] as $key => $form ) {
				$this->forms[ $form['id'] ] = $form['name'];
			}
		}

		return $this->forms;

	}


	/**
	 * Get an array of tags and IDs from the API
	 *
	 * @return mixed
	 */
	public function get_tags(){

		$tags = get_transient( 'convertkit_pmp_tag_data' );

		if( false === $tags ) {
			$data = $this->do_api_call( 'tags' );

			if( ! is_wp_error( $data ) ) {

				$tags = $data;
				set_transient( 'convertkit_pmp_tag_data', $tags, 24*24 );
			}
		}

		if ( ! empty( $tags ) && isset( $tags['tags'] ) && ! empty( $tags['tags'] ) ) {

			foreach( $tags['tags'] as $key => $tag ) {
				$this->tags[ $tag['id'] ] = $tag['name'];
			}
		}

		return $this->tags;

	}



	/**
	 * Make a remote call to ConvertKit's API.
	 *
	 * @param $path
	 * @param array $query_args
	 * @param string $method
	 * @param null $body
	 * @param array $request_args
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function do_api_call( $path, $query_args = array(), $method = 'GET', $body = null, $request_args = array() ) {

		$api_key = $this->api_key;

		if ( '' == $api_key ){
			return array();
		}

		// Setup the URL endpoint
		$request_url = $this->api_url . '/' . $this->api_version . '/' . $path;
		$query_args['api_key'] = $api_key;
		$request_url = add_query_arg( $query_args, $request_url );

		// Setup the request args
		$request_args = array_merge( array(
			'body' => $body,
			'headers' => array(
				'Accept' => 'application/json',
			),
			'method'  => $method,
			'timeout' => 30,

		), $request_args );

		// Do the request
		$response = wp_remote_request( $request_url, $request_args );

		// Handle the response
		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			if( is_null( $response_data ) ) {
				return new WP_Error( 'parse_failed', __('Could not parse response from ConvertKit', 'convertkit-pmp' ) );
			} else if( isset( $response_data['error']) && isset($response_data['message'] ) ) {
				return new WP_Error( $response_data['error'], $response_data['message'] );
			} else {
				return $response_data;
			}

		}

	}

}
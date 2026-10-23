<?php
defined( 'ABSPATH' ) || exit;

/**
 * Generic REST API client using the WordPress HTTP API.
 */
class FluidCheckout_RestApiClient {

	/**
	 * Base URL of the API.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Headers to send with each request.
	 *
	 * @var array
	 */
	private $headers = array();



	/**
	 * Constructor to initialize base URL and default headers.
	 *
	 * @param  string  $base_url  The base URL of the API (e.g. 'https://api.example.com').
	 * @param  array   $headers   Optional default headers (e.g. array( 'Authorization' => 'Bearer YOUR_KEY' )).
	 */
	public function __construct( $base_url, $headers = array() ) {
		// Set base URL
		$this->base_url = untrailingslashit( $base_url );

		// Set default headers
		$this->set_headers( array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		) );

		// Merge custom headers
		$this->set_headers( $headers );
	}

	/**
	 * Set or update headers.
	 *
	 * @param  array  $headers  Headers to merge with defaults.
	 */
	public function set_headers( $headers ) {
		$this->headers = array_merge( $this->headers, $headers );
	}



	/**
	 * Perform a GET request.
	 *
	 * @param   string  $endpoint      The API endpoint (e.g. '/products').
	 * @param   array   $query_params  Optional query parameters.
	 * @return  array                  Response with 'success', 'data', 'status', and 'error'.
	 */
	public function get( $endpoint, $query_params = array() ) {
		$url = $this->build_url( $endpoint, $query_params );
		return $this->request( 'GET', $url );
	}

	/**
	 * Perform a POST request.
	 *
	 * @param   string  $endpoint  The API endpoint.
	 * @param   array   $body      Data to send in the request body.
	 * @return  array              Response.
	 */
	public function post( $endpoint, $body = array() ) {
		$url = $this->build_url( $endpoint );
		return $this->request( 'POST', $url, $body );
	}

	/**
	 * Perform a PUT request.
	 *
	 * @param   string  $endpoint  The API endpoint.
	 * @param   array   $body      Data to send.
	 * @return  array              Response.
	 */
	public function put( $endpoint, $body = array() ) {
		$url = $this->build_url( $endpoint );
		return $this->request( 'PUT', $url, $body );
	}

	/**
	 * Perform a DELETE request.
	 *
	 * @param   string  $endpoint  The API endpoint.
	 * @return  array              Response.
	 */
	public function delete( $endpoint ) {
		$url = $this->build_url( $endpoint );
		return $this->request( 'DELETE', $url );
	}

	/**
	 * Perform a PATCH request.
	 *
	 * @param   string  $endpoint  The API endpoint.
	 * @param   array   $body      Data to send.
	 * @return  array              Response.
	 */
	public function patch( $endpoint, $body = array() ) {
		$url = $this->build_url( $endpoint );
		return $this->request( 'PATCH', $url, $body );
	}



	/**
	 * Build the full URL with query parameters.
	 *
	 * @param   string  $endpoint       The API endpoint.
	 * @param   array   $query_params   Optional query parameters.
	 * @return  string                  Full URL.
	 */
	public function build_url( $endpoint, $query_params = array() ) {
		$url = $this->base_url . '/' . ltrim( $endpoint, '/' );
		if ( ! empty( $query_params ) ) {
			$url .= '?' . http_build_query( $query_params );
		}
		return $url;
	}

	/**
	 * Execute the HTTP request using the WordPress HTTP API.
	 *
	 * @param   string  $method  HTTP method (GET, POST, etc.).
	 * @param   string  $url     Full URL.
	 * @param   array   $body    Optional request body.
	 * @return  array            Response array.
	 */
	public function request( $method, $url, $body = array() ) {
		$args = array(
			'method'  => strtoupper( $method ),
			'headers' => $this->headers,
			'timeout' => 15, // Seconds
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args[ 'body' ] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'status'  => 0,
				'data'    => null,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_content = wp_remote_retrieve_body( $response );
		$data = json_decode( $body_content, true );

		return array(
			'success' => $status_code >= 200 && $status_code < 300,
			'status'  => $status_code,
			'data'    => $data ? $data : $body_content, // Fallback to raw body if not JSON
			'error'   => $status_code >= 400 ? ( is_array( $data ) && isset( $data[ 'error' ] ) ? $data[ 'error' ] : 'Request failed' ) : null,
		);
	}

}

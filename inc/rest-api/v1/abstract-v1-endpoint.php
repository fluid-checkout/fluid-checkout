<?php
defined( 'ABSPATH' ) || exit;

require_once dirname( dirname( __FILE__ ) ) . '/errors.php';

/**
 * REST API endpoint base for `fluid-checkout/v1`.
 *
 * Add-ons and PRO register resource routes by extending this class and implementing get_routes().
 */
abstract class FluidCheckout_REST_API_V1_Endpoint {

	/**
	 * REST API route namespace.
	 */
	const ROUTE_NAMESPACE = 'fluid-checkout/v1';

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		add_action( 'rest_api_init', array( get_called_class(), 'register_routes' ), 10 );
	}

	/**
	 * Register routes.
	 */
	public static function register_routes() {
		// Bail if there are no routes to register.
		if ( ! method_exists( get_called_class(), 'get_routes' ) ) { return; }

		$routes = call_user_func( array( get_called_class(), 'get_routes' ) );
		if ( ! is_array( $routes ) ) { return; }

		// Iterate and register routes.
		foreach ( $routes as $route ) {
			if ( ! is_array( $route ) || empty( $route['endpoint'] ) || empty( $route['methods'] ) || empty( $route['callback'] ) ) {
				continue;
			}

			register_rest_route(
				self::ROUTE_NAMESPACE,
				$route['endpoint'],
				array(
					'methods'             => $route['methods'],
					'callback'            => $route['callback'],
					'permission_callback' => $route['permission_callback'] ?? array( get_called_class(), 'check_permissions' ),
					'args'                => ( isset( $route['args'] ) && is_array( $route['args'] ) ) ? $route['args'] : array(),
				)
			);
		}
	}

	/**
	 * Check permissions for current request.
	 *
	 * Default: logged-in user, optional user_id targets own data or callers with edit_user.
	 *
	 * @param   WP_REST_Request  $request  Request object.
	 *
	 * @return  WP_Error|bool              Whether request is allowed.
	 */
	public static function check_permissions( $request ) {
		// Bail when user is not authenticated.
		if ( ! is_user_logged_in() ) {
			return FluidCheckout_RestApi_Errors::make_error( FluidCheckout_RestApi_Errors::FORBIDDEN );
		}

		// Get target user id from request.
		$target_user_id  = self::get_target_user_id_from_request( $request );
		$current_user_id = get_current_user_id();
		if ( ! get_userdata( $target_user_id ) ) {
			return FluidCheckout_RestApi_Errors::make_error( FluidCheckout_RestApi_Errors::USER_NOT_FOUND );
		}

		// Allow own-records operations. When target user id is the same as the current user id.
		if ( $target_user_id === $current_user_id ) {
			return true;
		}

		// Allow delegated/cross-user operations when the caller can edit the target user's profile.
		if ( current_user_can( 'edit_user', $target_user_id ) ) {
			return true;
		}

		return FluidCheckout_RestApi_Errors::make_error( FluidCheckout_RestApi_Errors::FORBIDDEN );
	}



	/**
	 * Resolve target user from request.
	 *
	 * @param   WP_REST_Request  $request  Request object.
	 *
	 * @return  int                         Target user ID.
	 */
	public static function get_target_user_id_from_request( $request ) {
		// Get current user id.
		$current_user_id = get_current_user_id();
		$target_user_id = absint( $request->get_param( 'user_id' ) );

		// Default to current user when user_id is missing.
		if ( 0 === $target_user_id ) {
			$target_user_id = $current_user_id;
		}

		return $target_user_id;
	}



	/**
	 * Map a feature-layer WP_Error to a REST `fc_api_*` error.
	 *
	 * Uses `fc_rest_api_feature_error_code_map` registered by the resource plugin.
	 *
	 * @param   WP_Error|mixed  $error  Error object from the feature layer.
	 *
	 * @return  WP_Error|mixed
	 */
	protected static function normalize_rest_error( $error ) {
		// Bail if value is not a WP_Error
		if ( ! is_wp_error( $error ) ) {
			return $error;
		}

		return FluidCheckout_RestApi_Errors::normalize_feature_error( $error );
	}

}

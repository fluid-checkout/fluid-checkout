<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared REST API error codes for `fluid-checkout/v1` (Lite, PRO, and fc-* add-ons).
 *
 * Wire codes use the `fc_api_*` prefix. Plugins with their own namespace keep a
 * separate prefix (for example fc-licenses).
 */
class FluidCheckout_RestApi_Errors {

	/**
	 * Caller is not allowed to perform the request.
	 */
	const FORBIDDEN = 'fc_api_forbidden';

	/**
	 * Target user id does not exist.
	 */
	const USER_NOT_FOUND = 'fc_api_user_not_found';

	/**
	 * Required feature or dependency is not available.
	 */
	const UNAVAILABLE = 'fc_api_unavailable';

	/**
	 * Required address field is missing.
	 */
	const REQUIRED_FIELD_MISSING = 'fc_api_required_field_missing';

	/**
	 * Postcode or ZIP is invalid for the country.
	 */
	const INVALID_POSTCODE = 'fc_api_invalid_postcode';

	/**
	 * Phone number is invalid.
	 */
	const INVALID_PHONE = 'fc_api_invalid_phone';

	/**
	 * State or county is invalid for the country.
	 */
	const INVALID_STATE = 'fc_api_invalid_state';

	/**
	 * Country code is unknown or has no address fields.
	 */
	const INVALID_COUNTRY = 'fc_api_invalid_country';

	/**
	 * Fallback when a feature-layer error has no REST mapping.
	 */
	const INVALID_REQUEST = 'fc_api_invalid_request';



	/**
	 * Get REST error definitions (message and default HTTP status).
	 */
	public static function get_definitions() {
		$definitions = array(
			self::FORBIDDEN              => array(
				'message' => __( 'Forbidden access.', 'fluid-checkout' ),
				'status'  => 403,
			),
			self::USER_NOT_FOUND         => array(
				'message' => __( 'Target user was not found.', 'fluid-checkout' ),
				'status'  => 404,
			),
			self::UNAVAILABLE            => array(
				'message' => __( 'This feature is not available.', 'fluid-checkout' ),
				'status'  => 503,
			),
			self::REQUIRED_FIELD_MISSING => array(
				'message' => __( 'A required address field is missing.', 'fluid-checkout' ),
				'status'  => 400,
			),
			self::INVALID_POSTCODE       => array(
				'message' => __( 'Postcode or ZIP is invalid.', 'fluid-checkout' ),
				'status'  => 400,
			),
			self::INVALID_PHONE          => array(
				'message' => __( 'Phone number is invalid.', 'fluid-checkout' ),
				'status'  => 400,
			),
			self::INVALID_STATE          => array(
				'message' => __( 'State or county is invalid.', 'fluid-checkout' ),
				'status'  => 400,
			),
			self::INVALID_COUNTRY        => array(
				'message' => __( 'Country is invalid or has no address fields.', 'fluid-checkout' ),
				'status'  => 400,
			),
			self::INVALID_REQUEST        => array(
				'message' => __( 'The request could not be processed.', 'fluid-checkout' ),
				'status'  => 400,
			),
		);

		/**
		 * Filter REST API error definitions for `fluid-checkout/v1`.
		 *
		 * Add-ons register resource-specific messages (for example address book CRUD).
		 *
		 * @param array $definitions Catalog id => array( message, status ).
		 */
		return apply_filters( 'fc_rest_api_error_definitions', $definitions );
	}



	/**
	 * Map feature-layer WP_Error codes to REST `fc_api_*` codes.
	 */
	public static function get_feature_error_code_map() {
		$map = array();

		/**
		 * Filter feature-layer error code => REST `fc_api_*` code map.
		 *
		 * @param array $map Feature error code => REST catalog id.
		 */
		return apply_filters( 'fc_rest_api_feature_error_code_map', $map );
	}



	/**
	 * Build a WP_Error from a catalog id.
	 *
	 * Pass `$data['message']` to override the catalog message (for example validation text from WooCommerce).
	 *
	 * @param   string  $id            Catalog id (usually a `fc_api_*` constant).
	 * @param   array   $data          Optional error data (for example status).
	 * @param   array   $message_args  Optional sprintf replacements for the catalog message.
	 *
	 * @return  WP_Error
	 */
	public static function make_error( $id, $data = array(), $message_args = array() ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( ! is_array( $message_args ) ) {
			$message_args = array();
		}

		$definitions = self::get_definitions();
		$code        = $id;

		if ( isset( $data['message'] ) ) {
			$message = $data['message'];
			unset( $data['message'] );
		}
		// Otherwise, use the catalog message for this error ID
		else {
			$message = isset( $definitions[ $id ]['message'] ) ? $definitions[ $id ]['message'] : '';

			if ( ! empty( $message_args ) ) {
				$message = vsprintf( $message, $message_args );
			}
		}

		if ( '' === $message ) {
			$message = isset( $definitions[ self::INVALID_REQUEST ]['message'] ) ? $definitions[ self::INVALID_REQUEST ]['message'] : '';
		}

		if ( ! isset( $data['status'] ) && isset( $definitions[ $id ]['status'] ) ) {
			$data['status'] = (int) $definitions[ $id ]['status'];
		}

		return new WP_Error( $code, $message, $data );
	}



	/**
	 * Convert a feature-layer WP_Error to a REST `fc_api_*` error.
	 *
	 * Preserves the feature message and HTTP status when present.
	 *
	 * @param   WP_Error  $error  Feature or handler error.
	 *
	 * @return  WP_Error
	 */
	public static function normalize_feature_error( WP_Error $error ) {
		$feature_code = $error->get_error_code();

		// Bail if the error is already a REST API error
		if ( 0 === strpos( $feature_code, 'fc_api_' ) ) {
			return $error;
		}

		$data = $error->get_error_data();
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$message_override = $error->get_error_message();
		$map              = self::get_feature_error_code_map();

		if ( isset( $map[ $feature_code ] ) ) {
			$data['message'] = $message_override;

			return self::make_error( $map[ $feature_code ], $data );
		}

		$data['message'] = $message_override;

		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 400;
		}

		return self::make_error( self::INVALID_REQUEST, $data );
	}

}

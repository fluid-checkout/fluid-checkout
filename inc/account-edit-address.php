<?php
defined( 'ABSPATH' ) || exit;

/**
 * Features for account edit address endpoints.
 */
class FluidCheckout_AccountEditAddress extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Check whether the feature is enabled or not.
	 */
	public function is_feature_enabled() {
		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Body Class
		remove_filter( 'body_class', array( $this, 'add_body_class' ) );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on account address edit page
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $classes; }

		// Initialize variables
		$add_classes = array();

		// Add extra class to enable form fields font-size styles
		if ( true === apply_filters( 'fc_fix_zoom_in_form_fields_mobile_devices', ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_fix_zoom_in_form_fields_mobile_devices' ) ) ) ) {
			$add_classes[] = 'has-form-field-font-size-fix';
		}

		return array_merge( $classes, $add_classes );
	}

}

FluidCheckout_AccountEditAddress::instance();

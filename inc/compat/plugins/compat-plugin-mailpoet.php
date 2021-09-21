<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Integration Rede for WooCommerce (by MarcosAlexandre).
 */
class FluidCheckout_Mailpoet extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_option_checkbox_hide_optional_field_skip_list' ), 10 );
	}



	/**
	 * Adds delivery forecast after method name.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function add_option_checkbox_hide_optional_field_skip_list( $skip_fields ) {
        $skip_fields[] = 'mailpoet_woocommerce_checkout_optin_present';
        return $skip_fields;
	}

}

FluidCheckout_Mailpoet::instance();

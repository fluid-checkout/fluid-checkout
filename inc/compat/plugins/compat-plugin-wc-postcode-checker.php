<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce NL Postcode Checker (by WP Overnight).
 */
class FluidCheckout_WCPostcodeChecker extends FluidCheckout {

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
		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10, 2 );
	}



	/**
	 * Adds custom fields from the postcode checker plugin to the list of optional fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_field_keys ) {
		$fields_keys = array(
			'address_1',
			'address_2',
			'street_name',
			'house_number',
			'house_number_suffix',

			'shipping_address_1',
			'shipping_address_2',
			'shipping_street_name',
			'shipping_house_number',
			'shipping_house_number_suffix',

			'billing_address_1',
			'billing_address_2',
			'billing_street_name',
			'billing_house_number',
			'billing_house_number_suffix',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}

}

FluidCheckout_WCPostcodeChecker::instance();

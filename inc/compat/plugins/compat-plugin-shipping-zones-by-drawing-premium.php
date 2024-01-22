<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Shipping Zones by Drawing Premium for WooCommerce (by Arosoft.se).
 */
class FluidCheckout_ShippingZonesByDrawingPremium extends FluidCheckout {

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
	 * Adds fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_field_keys ) {
		$fields_keys = array(
			'szbd-picked',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}

}

FluidCheckout_ShippingZonesByDrawingPremium::instance();

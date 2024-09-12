<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Loyalty Program for WooCommerce (for Advanced Coupons) (by Rymera Web Co).
 */
class FluidCheckout_LoyaltyProgramForWooCommerce extends FluidCheckout {

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
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_store_credits' ), 10 );
	}


	
	/**
	 * Prevent hiding optional field for the store credits behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_store_credits( $skip_list ) {
		$skip_list[] = 'lpfw_redeem_loyalty_points';
		return $skip_list;
	}

}

FluidCheckout_LoyaltyProgramForWooCommerce::instance();

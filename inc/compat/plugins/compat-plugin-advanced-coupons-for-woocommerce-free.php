<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Advanced Coupons for WooCommerce Free (by Rymera Web Co).
 */
class FluidCheckout_AdvancedCouponsForWooCommerceFree extends FluidCheckout {

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

		// Checkout block
		add_filter( 'acfw_filter_is_current_page_using_cart_checkout_block', '__return_false', 10 );

		// Add section position hooks
		$this->section_position_hooks();
		
	}



	/**
	 * Prevent hiding optional field for the store credits behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_store_credits( $skip_list ) {
		$skip_list[] = 'acfw_redeem_store_credit';
		return $skip_list;
	}



	/**
	 * Remove section position hooks.
	 */
	public function section_position_hooks() {
		// Define class name
		$class_name = 'ACFWF\Models\Checkout';

		// Bail if class is not available
		if ( ! class_exists( $class_name ) ) { return; }

		// Get class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if class object or function is not available
		if ( ! $class_object ) { return; }

		// Remove section
		remove_action( 'woocommerce_checkout_order_review', array( $class_object, 'display_checkout_tabbed_box' ), 11 );
	}


}

FluidCheckout_AdvancedCouponsForWooCommerceFree::instance();
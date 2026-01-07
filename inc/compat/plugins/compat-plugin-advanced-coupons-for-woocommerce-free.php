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
		// Checkout hooks
		$this->checkout_hooks();

	}



	/**
	 * Add or remove checkout hooks.
	 */
	public function checkout_hooks() {
		// Bail if class is not available
		$class_name = 'ACFWF\Models\Checkout';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class object is not found
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		if ( ! $class_object ) { return; }

		// Move Advanced Coupons box to the fixed position before the checkout steps.
		remove_action( 'woocommerce_checkout_order_review', array( $class_object, 'display_checkout_tabbed_box' ), 11 );
		add_action( 'fc_checkout_before_steps', array( $class_object, 'display_checkout_tabbed_box' ), 5 );

		// Prevent hiding store credits behind a link button
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_store_credits' ), 10 );

		// Prevent Advanced Coupons from using the checkout block
		add_filter( 'acfw_filter_is_current_page_using_cart_checkout_block', '__return_false', 10 );
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
}

FluidCheckout_AdvancedCouponsForWooCommerceFree::instance();
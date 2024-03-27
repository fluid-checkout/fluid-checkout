<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Acowebs Woocommerce Dynamic Pricing (by Acowebs).
 */
class FluidCheckout_AcoWooDynamicPricing extends FluidCheckout {

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
		// Coupon codes
		add_action ( 'woocommerce_before_calculate_totals', array( $this, 'maybe_remove_discount_rules'), 999, 1 );
	}



	/**
	 * Remove discount rules.
	 *
	 * @param WC_Cart $cart
	 */
	public function maybe_remove_discount_rules( $cart ) {
		// Get plugin option
		$disable_discount   = get_option( 'awdp_disable_discount' ) ? get_option( 'awdp_disable_discount' ) : '';

		// Bail if should not disable discount rules when a normal coupon is applied
		if ( ! $disable_discount ) { return; }

		// Get discount rules coupon information
		$coupon             = get_option( 'awdp_fee_label' ) ? get_option( 'awdp_fee_label' ) : 'Discount';
		$coupon_code        = apply_filters( 'woocommerce_coupon_code', $coupon );
		$coupon_code        = wc_format_coupon_code( $coupon_code );

		// Get applied coupons
		$applied_coupons    = $cart->get_applied_coupons();

		// Bail if only one or zero coupon is currently applied
		if ( count( $applied_coupons ) <= 1 ) {
			return;
		}

		// Maybe remove discount rules coupon
		$position = array_search( $coupon_code, array_map( 'wc_format_coupon_code', $applied_coupons ), true );
		if ( false !== $position ) {
			// Manipulates directly in the cart object to avoid infinite loops
			// and undesired cart notices in the frontend.
			unset( $applied_coupons[ $position ] );
			$cart->set_applied_coupons( $applied_coupons );
		}
	}

}

FluidCheckout_AcoWooDynamicPricing::instance();

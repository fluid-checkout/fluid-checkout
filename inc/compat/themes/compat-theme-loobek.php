<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Loobek (by Theme Sky Team).
 */
class FluidCheckout_ThemeCompat_Loobek extends FluidCheckout {

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
		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Coupon bar
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 20 );

		// Quantity buttons
		remove_action( 'woocommerce_before_quantity_input_field', 'loobek_before_quantity_input_field', 1 );
		remove_action( 'woocommerce_before_quantity_input_field', 'loobek_after_quantity_input_field', 99 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme functions isn't available
		if ( ! function_exists( 'loobek_get_theme_options' ) || ! function_exists( 'loobek_get_theme_options' ) ) { return $attributes; }

		$is_sticky = loobek_get_theme_options( 'ts_enable_sticky_header' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.ts-header.is-sticky';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Loobek::instance();
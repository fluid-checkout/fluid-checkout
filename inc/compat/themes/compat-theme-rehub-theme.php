<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Rehub theme (by Wpsoul).
 */
class FluidCheckout_ThemeCompat_RehubTheme extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Order review section layout
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'rehub_woo_order_checkout', 10 );
		remove_action( 'woocommerce_checkout_after_order_review', 'rehub_woo_after_order_checkout', 10 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if required theme function is not available
		if ( ! function_exists( 'rehub_option' ) ) { return $attributes; }

		$is_sticky = rehub_option( 'rehub_sticky_nav' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.re-stickyheader';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_RehubTheme::instance();

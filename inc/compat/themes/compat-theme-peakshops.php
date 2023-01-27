<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: PeakShops (by fuelthemes).
 */
class FluidCheckout_ThemeCompat_PeakShops extends FluidCheckout {

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
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'add_content_section_class' ), 10 );

		// Checkout Page Layout
		remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'thb_checkout_after_customer_details', 30 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thb_checkout_after_order_review', 30 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		// Maybe add the container class
		$class = $class . ' row align-middle';

		return $class;
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		// Bail if fixed header option is disabled
		if ( ! function_exists( 'ot_get_option' ) || 'on' !== ot_get_option( 'fixed_header', 'on' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header.fixed';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_PeakShops::instance();

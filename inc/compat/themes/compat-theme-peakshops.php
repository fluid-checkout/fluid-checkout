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
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		// Maybe add the container class
		$class = $class . ' row align-middle';

		return $class;
	}

}

FluidCheckout_ThemeCompat_PeakShops::instance();

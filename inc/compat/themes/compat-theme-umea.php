<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Umea (by Edge Themes).
 */
class FluidCheckout_ThemeCompat_Umea extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Maybe remove elements from theme
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			remove_action( 'woocommerce_before_checkout_form', 'umea_add_main_woo_page_holder', 5 );
			remove_action( 'woocommerce_after_checkout_form', 'umea_add_main_woo_page_holder_end', 20 );
		}
	}

}

FluidCheckout_ThemeCompat_Umea::instance();

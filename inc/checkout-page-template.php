<?php
defined( 'ABSPATH' ) || exit;

/**
 * Load the plugin's template file for the checkout page.
 */
class FluidCheckout_CheckoutPageTemplate extends FluidCheckout {

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
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );
	}

	/**
	 * Replace the checkout page template with our own file.
	 *
	 * @param   String  $template  Template file path.
	 */
	public function checkout_page_template( $template ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $template; }

		// Locate new checkout page template
		$template_name = 'fc/page-checkout.php';
		$plugin_path  = self::$directory_path . 'templates/';
		$new_template = $this->locate_template( $template, $template_name, $plugin_path );

		// Check if th file exists
		if ( file_exists( $new_template ) ) {
			$template = $new_template;
		}

		return $template;
	}

}

FluidCheckout_CheckoutPageTemplate::instance();

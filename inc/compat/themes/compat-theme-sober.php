<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Sober (by Uixthemes).
 */
class FluidCheckout_ThemeCompat_Sober extends FluidCheckout {

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
		// Set class name from the theme
		$class_name = 'Sober_WooCommerce';

		// Bail if class is not available.
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		// Get class object
		$class_object = call_user_func( array( $class_name, 'instance' ) );

		// Remove hooks from theme
		remove_filter( 'woocommerce_checkout_before_customer_details', array( $class_object, 'billing_title' ) );
		remove_filter( 'woocommerce_loop_add_to_cart_link', array( $class_object, 'add_to_cart_catalog_button' ), 10, 3 );
		remove_filter( 'woocommerce_cart_item_quantity', array( $class_object, 'cart_item_quantity' ), 10, 3 );

		// Hooks
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => 'auto',
				'--fluidcheckout--field--padding-left' => '0',
				'--fluidcheckout--field--font-size' => '16px',

				// Border styles
				// ! Can not set the border bottom only? I need to set the border top, left, right and bottom.
				'--fluidcheckout--field--border-color' => '#e4e6eb',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--border-style' => 'solid',
				
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Sober::instance();

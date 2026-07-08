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
		// Late hooks
		add_action( 'wp', array( $this, 'late_hooks' ), 150 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Define class name
		$class_name = 'Sober_WooCommerce';

		// Bail if class is not available
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class method is not available
		if ( ! method_exists( $class_name, 'instance' ) ) { return; }

		// Get class object
		$class_object = call_user_func( array( $class_name, 'instance' ) );

		// Remove hooks from theme
		remove_action( 'woocommerce_checkout_before_customer_details', array( $class_object, 'billing_title' ), 10 );
		remove_filter( 'woocommerce_loop_add_to_cart_link', array( $class_object, 'add_to_cart_catalog_button' ), 10, 3 );
		remove_filter( 'woocommerce_cart_item_quantity', array( $class_object, 'cart_item_quantity' ), 10, 3 );
		remove_filter( 'woocommerce_form_field_args', array( $class_object, 'form_field_args' ), 10, 2 );
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
				'--fluidcheckout--field--text-color' => '#23232c',

				// Border styles
				'--fluidcheckout--field--border-color' => '#e4e6eb',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--border-style' => 'solid',

			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Sober::instance();

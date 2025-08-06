<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Templatemela Core.
 */
class FluidCheckout_TemplatemelaCore extends FluidCheckout {

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
		// Early hooks to remove Templatemela Core checkout hooks
		add_action( 'init', array( $this, 'remove_templatemela_checkout_hooks' ), 5 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Remove Templatemela Core checkout hooks that interfere with Fluid Checkout.
	 */
	public function remove_templatemela_checkout_hooks() {
		// Define class name
		$class_name = 'TemplateMelaCore_WooCommerce';

		// Bail if class is not available
		if ( ! class_exists( $class_name ) ) { return; }

		// Get class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if class object is not available
		if ( ! is_object( $class_object ) ) { return; }

		// Remove the problematic hooks that interfere with Fluid Checkout
		remove_action( 'woocommerce_checkout_before_order_review', array( $class_object, 'open_checkout_order_review' ), 0 );
		remove_action( 'woocommerce_checkout_after_order_review', array( $class_object, 'close_checkout_order_review' ), 99 );
		
		// Remove the filter that adds duplicate product images to cart item names
		remove_filter( 'woocommerce_cart_item_name', array( $class_object, 'review_product_name_html' ), 10 );

		// Removes your order review title
		remove_action( 'woocommerce_checkout_order_review', array( $class_object, 'add_before_order_review' ), 1 );
	}


	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			// Dark mode
			':root body.color-switch-dark' => FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_TemplatemelaCore::instance();

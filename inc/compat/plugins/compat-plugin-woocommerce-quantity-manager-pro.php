<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Quantity Discounts, Rules & Swatches (by StudioWombat).
 */
class FluidCheckout_WooCommerceQuantityManagerPro extends FluidCheckout {

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
		// Quantity input attributes
		$this->remove_action_for_class( 'woocommerce_quantity_input_args', array( 'SW_WQM_PRO\Includes\Controllers\Quantity_Controller', 'add_attributes_to_quantity_field' ), 10 );
		add_action( 'woocommerce_quantity_input_args', array( $this, 'add_attributes_to_quantity_field' ), 10, 2 );
	}



	/**
	 * Change quantity input attributes.
	 * 
	 * COPIED FROM the WooCommerce Quantity Discounts, Rules & Swatches plugin.
	 * @see SW_WQM_PRO\Includes\Controllers\Quantity_Controller::add_attributes_to_quantity_field()
	 */
	public function add_attributes_to_quantity_field( $args, $product ) {
		// CHANGE: Add bail statements
		if ( ! method_exists( 'SW_WQM_PRO\Includes\Services\Product_Service', 'get_quantity_settings' ) || ! method_exists( 'SW_WQM_PRO\Includes\Services\Product_Service', 'create_quantity_input_model' ) ) { return $args; }
		if ( ! method_exists( 'SW_WQM_PRO\Includes\Classes\Helper', 'merge_defaults' ) ) { return $args; }

		// CHANGE: Check for mini cart differently since the plugin's private property is not accessible
		$is_mini_cart = did_action( 'woocommerce_before_mini_cart' ) !== did_action( 'woocommerce_after_mini_cart' );

		$quantity_settings = SW_WQM_PRO\Includes\Services\Product_Service::get_quantity_settings( $product );
		if ( empty( $quantity_settings ) ) { return $args; }

		$new_args = SW_WQM_PRO\Includes\Classes\Helper::merge_defaults( $args, SW_WQM_PRO\Includes\Services\Product_Service::create_quantity_input_model( $product ) );

		// CHANGE: Change condition for resetting min and max values
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() || FluidCheckout_Steps::instance()->is_cart_page_or_fragment() || $is_mini_cart ) {
			$args['min_value'] = $new_args['min_value'];
			$args['max_value'] = $new_args['max_value'];

			return $args;
		}

		return $new_args;
	}

}

FluidCheckout_WooCommerceQuantityManagerPro::instance();

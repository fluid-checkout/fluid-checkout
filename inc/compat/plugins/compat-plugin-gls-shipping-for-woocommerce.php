<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: GLS Shipping for WooCommerce (by Inchoo).
 */
class FluidCheckout_GLSShippingForWooCommerce extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'gls_shipping_method';



	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'GLS_Shipping_Checkout';



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
		// Shipping methods hooks
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Move shipping method hooks
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( $class_object, 'add_gls_button_to_shipping_method' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );
	}



	/**
	 * Check whether the shipping method ID is GLS Shipping method.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_gls_shipping( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Maybe get selected shipping method object if it matches the target method.
	 */
	public function maybe_get_selected_shipping_method() {
		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get available shipping methods
			$available_methods = $package['rates'];

			// Check if target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_gls_shipping( $chosen_method ) && ! empty( $available_methods[ $chosen_method ] ) ) {
				// Return the selected shipping method object
				return $available_methods[ $chosen_method ];
			}
		}

		return false;
	}



	/**
	 * Output the pickup point selection UI from the plugin.
	 */
	public function output_pickup_point_selection_ui() {
		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if plugin's class object is not available
		if ( ! $class_object ) { return; }

		// Bail if plugin's class method is not available
		if ( ! method_exists( $class_object, 'add_gls_button_to_shipping_method' ) ) { return; }

		// Get selected shipping method object
		$method = $this->maybe_get_selected_shipping_method();

		// Bail if selected shipping method object is not available
		if ( ! is_object( $method ) ) { return; }

		// Set default value
		$label = '';

		// Get the pickup point selection UI
		ob_start();
		echo $class_object->add_gls_button_to_shipping_method( $label, $method );
		$html = ob_get_clean();

		// Output the pickup point selection UI
		echo $html;
	}

}

FluidCheckout_GLSShippingForWooCommerce::instance();

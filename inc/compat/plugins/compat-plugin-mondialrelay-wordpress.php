<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mondial Relay - WordPress (by Kasutan).
 */
class FluidCheckout_MondialRelayWordpress extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'mondialrelay';



	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'class_MRWP_public';



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
		remove_action( 'woocommerce_review_order_after_shipping', array( $class_object, 'modaal_link' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );

		// Mondial Relay logo from order overview
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( 'MRWP_Shipping_Method', 'embellish_label' ), 10, 2 );
	}



	/**
	 * Check whether the shipping method ID is Mondial Relay.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_mondial_relay( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Check whether Mondial Relay is selected as a shipping method.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if a Mondial Relay shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_mondial_relay( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Maybe change the pickup point substep text to display the Mondial Relay information.
	 */
	public function output_pickup_point_selection_ui() {
		// Bail if selected shipping method is not a Mondial Relay shipping method
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if plugin's class object is not available
		if ( ! $class_object ) { return; }

		// Bail if plugin's class method is not available
		if ( ! method_exists( $class_object, 'modaal_link' ) ) { return; }

		// Get the pickup point selection UI
		ob_start();
		$class_object->modaal_link();
		$html = ob_get_clean();

		// Replace table elements with `div`
		$replace = array(
			'<tr' => '<div',
			'</tr' => '</div',
			'<td' => '<div',
			'</td' => '</div',
		);
		$html = str_replace( array_keys( $replace ), array_values( $replace ), $html );

		// Output
		echo $html;
	}

}

FluidCheckout_MondialRelayWordpress::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce FedEx Shipping Pro (by Techspawn).
 */
class FluidCheckout_WooCommerce_Shipping_Pro_FedEx extends FluidCheckout {	

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

		// Init hooks
		$this->shipping_init_hooks();
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Shipping method hooks
		$this->shipping_method_hooks();
	}

	/**
	 * Add or remove shipping init hooks.
	 */
	public function shipping_init_hooks() {
		// Initialize variables
		$class_name = 'WSPF_WC_Shipping';
		$class_object = null;

		// Maybe set class object
		if ( class_exists( $class_name ) ) {
			// Get object
			$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		}

		// Bail if class object is not set
		if ( ! $class_object ) { return; }

		// Move shipping method initialization to init hook to make it available earlier
		remove_action( 'woocommerce_shipping_init', array( $class_object, 'wspf_fedex_method_init' ) );
		add_action( 'init', array( $class_object, 'wspf_fedex_method_init' ), 100 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_method_hooks() {
		// Initialize variables
		$class_name = 'WS_Shipping_FedEx';
		$class_object = null;

		// Maybe set class object
		if ( class_exists( $class_name ) ) {
			// Get object
			$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		}

		// Bail if class object is not set
		if ( ! $class_object ) { return; }

		// Remove delivery time filter
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( $class_object, 'wspf_add_delivery_time' ), 10, 2 );
		add_filter( 'fc_shipping_method_option_description', array( $this, 'add_method_delivery_time' ), 10, 2 );
	}



	/**
	 * Add delivery time to shipping method option markup.
	 */
	public function add_method_delivery_time( $description, $method ) {
		// Bail if not target method
		if ( 'wspf_fedex' !== $method->method_id ) { return $description; }

		// Initialize variables
		$class_name = 'WS_Shipping_FedEx';
		$class_object = null;

		// Maybe set class object
		if ( class_exists( $class_name ) ) {
			// Get object
			$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		}

		// Bail if class object is not set
		if ( ! $class_object ) { return $description; }

		// Get delivery time from shipping method
		$description = $class_object->wspf_add_delivery_time( $description, $method );

		// Remove first extra line break, and `small` tags
		$description = preg_replace( '/<br\s*\/?>/', '', $description, 1 );
		$description = preg_replace( '/<small\s*\/?>/', '', $description );

		return $description;
	}

}

FluidCheckout_WooCommerce_Shipping_Pro_FedEx::instance();

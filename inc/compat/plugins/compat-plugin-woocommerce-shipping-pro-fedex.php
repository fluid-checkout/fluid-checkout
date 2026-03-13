<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce FedEx Shipping Pro (by Techspawn).
 */
class FluidCheckout_WooCommerce_Shipping_Pro_FedEx extends FluidCheckout {	

	/**
	 * Class name for hooks from the plugin.
	 */
	private $class_name = 'WSPF_WC_Shipping';

	/**
	 * Class object for hooks from the plugin.
	 */
	private $class_object;



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
		// Init hooks
		$this->init_hooks();
	}

	/**
	 * Add or remove late hooks.
	 */
	public function init_hooks() {
		// Maybe set class object
		if ( class_exists( $this->class_name ) ) {
			// Get object
			$this->class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $this->class_name );
		}

		// Move shipping method initialization to the init hook
		remove_action( 'woocommerce_shipping_init', array( $this->class_object, 'wspf_fedex_method_init' ) );
		add_action( 'init', array( $this->class_object, 'wspf_fedex_method_init' ), 100 );
	}

}

FluidCheckout_WooCommerce_Shipping_Pro_FedEx::instance();

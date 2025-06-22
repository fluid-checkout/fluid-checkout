<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Bambora Gateway (by WooCommerce).
 */
class FluidCheckout_WooCommerceGatewayBeanstream extends FluidCheckout {

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
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}

	/**
	 * Add or remove checkout page hooks.
	 */
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Settings
		add_filter( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_gateway_assets' ), 10 );
	}



	/**
	 * Enqueue the gateway-specific assets if present, including JS, CSS, and localized script params.
	 * 
	 * COPIED AND ADAPTED FROM: SV_WC_Payment_Gateway::enqueue_gateway_assets().
	 */
	public function maybe_enqueue_gateway_assets() {
		// Get the class object for the payment gateway
		$class_name = 'SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway';
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if class object or its methods are not available
		if ( ! is_object( $class_object ) || ! method_exists( $class_object, 'get_plugin' ) || ! method_exists( $class_object, 'get_id' ) ) { return; }

		$handle    = 'wc-' . $class_object->get_plugin()->get_id_dasherized();
		$js_path   = $class_object->get_plugin()->get_plugin_path() . '/assets/js/frontend/' . $handle . '.min.js';
		$css_path  = $class_object->get_plugin()->get_plugin_path() . '/assets/css/frontend/' . $handle . '.min.css';
		$version   = $class_object->get_plugin()->get_assets_version( $class_object->get_id() );

		// JS
		if ( is_readable( $js_path ) ) {
			$js_url = $class_object->get_plugin()->get_plugin_url() . '/assets/js/frontend/' . $handle . '.min.js';

			/**
			 * Concrete Payment Gateway JS URL
			 *
			 * Allow actors to modify the URL used when loading a concrete
			 * payment gateway's javascript.
			 *
			 * @since 2.0.0
			 * @param string $js_url JS asset URL
			 * @return string
			 */
			$js_url = apply_filters( 'wc_payment_gateway_' . $class_object->get_plugin()->get_id() . '_javascript_url', $js_url );

			wp_enqueue_script( $handle, $js_url, array(), $version, array( 'in_footer' => true ) );
		}

		// CSS
		if ( is_readable( $css_path ) ) {
			$css_url = $class_object->get_plugin()->get_plugin_url() . '/assets/css/frontend/' . $handle . '.min.css';

			/**
			 * Concrete Payment Gateway CSS URL
			 *
			 * Allow actors to modify the URL used when loading a concrete payment
			 * gateway's CSS.
			 *
			 * @since 4.3.0
			 * @param string $css_url CSS asset URL
			 * @return string
			 */
			$css_url = apply_filters( 'wc_payment_gateway_' . $class_object->get_plugin()->get_id() . '_css_url', $css_url );

			wp_enqueue_style( $handle, $css_url, array(), $version );
		}
	}

}

FluidCheckout_WooCommerceGatewayBeanstream::instance();

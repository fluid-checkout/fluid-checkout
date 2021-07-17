<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Correios (by Claudio Sanches).
 */
class FluidCheckout_WooCommerceCorreios extends FluidCheckout {

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
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Move shipping delivery forecast to the shipping method description
		add_filter( 'fc_shipping_method_option_description', array( $this, 'shipping_delivery_forecast' ), 10, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		$this->remove_action_for_class( 'woocommerce_after_shipping_rate', array( 'WC_Correios_Cart', 'shipping_delivery_forecast' ), 100 );
	}



	/**
	 * Adds delivery forecast after method name.
	 *
	 * @param WC_Shipping_Rate $shipping_method Shipping method data.
	 */
	public function shipping_delivery_forecast( $method_description, $shipping_method ) {
		$meta_data = $shipping_method->get_meta_data();
		$total     = isset( $meta_data['_delivery_forecast'] ) ? intval( $meta_data['_delivery_forecast'] ) : 0;

		if ( $total ) {
			/* translators: %d: days to delivery */
			return esc_html( sprintf( _n( 'Delivery within %d working day', 'Delivery within %d working days', $total, 'woocommerce-correios' ), $total ) );
		}

		return $method_description;
	}

}

FluidCheckout_WooCommerceCorreios::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Order factory for preview requests, which also gets the dummy orders that exist in memory only.
 * Replaces the WooCommerce order factory, as stores using custom order tables skip order IDs missing from the database before the `woocommerce_order_class` filter.
 */
class FluidCheckout_Admin_Preview_Order_Factory extends WC_Order_Factory {

	/**
	 * Dummy orders by order ID, built once per request, as orders are loaded many times while a page renders and the cart they are built from can be emptied meanwhile.
	 *
	 * @var array
	 */
	private static $dummy_orders = array();



	/**
	 * Get an order, or a dummy order for the dummy order IDs.
	 *
	 * @param   mixed  $order_id  Order ID, or an order or post object. Defaults to the current order or post.
	 *
	 * @return  WC_Order|bool  The order, or `false` when not found.
	 */
	public static function get_order( $order_id = false ) {
		// Get the order ID, also when an order or post object is passed
		$id = self::get_order_id( $order_id );

		// Bail if not a dummy order, with the order from WooCommerce
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $id ) ) { return parent::get_order( $order_id ); }

		// Bail if the preview cart is not loaded yet, as dummy orders are built from it
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) { return false; }

		// Maybe build the dummy order once per request, returning `false` to plugins that load it while it is built
		if ( ! array_key_exists( $id, self::$dummy_orders ) ) {
			self::$dummy_orders[ $id ] = false;
			self::$dummy_orders[ $id ] = new FluidCheckout_Admin_Preview_Dummy_Order( $id );
		}

		return self::$dummy_orders[ $id ];
	}

}

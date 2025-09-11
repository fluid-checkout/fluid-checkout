<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Min Max Step Quantity Limits Manager for WooCommerce (by WPFactory).
 */
class FluidCheckout_ProductQuantityForWooCommerce extends FluidCheckout {

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
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}

	/**
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if required class is not available
		$class_name = 'Alg_WC_PQ_Core';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Quantity input args
		remove_filter( 'woocommerce_quantity_input_args', array( $class_object, 'set_quantity_input_args' ), PHP_INT_MAX - 100, 2 ); // Use the same priority as in the plugin
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'set_quantity_input_args' ), PHP_INT_MAX - 100, 2 ); // Use the same priority as in the plugin
	}



	/**
	 * Set quantity input args.
	 * COPIED AND ADAPTED FROM: Alg_WC_PQ_Core::set_quantity_input_args().
	 *
	 * @param  array       $args     Quantity input arguments.
	 * @param  WC_Product  $product  Product object.
	 */
	public function set_quantity_input_args( $args, $product ) {
		global $wp_query;

		// Bail if required class is not available
		$class_name = 'Alg_WC_PQ_Core';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		$category_name = '';
		if ( isset( $wp_query->query_vars[ 'product_cat' ] ) ) {
			$category_name = $wp_query->query_vars[ 'product_cat' ];
		}

		if ( empty( $product ) ) {
			return $args;
		}

		if ( 'yes' === get_option( 'alg_wc_pq_min_section_enabled', 'no' ) ) {
			$args[ 'min_value' ] = $class_object->set_quantity_input_min( $args[ 'min_value' ], $product );

			// CHANGE: Remove `is_cart()` check as it's no longer needed
			if ( $product->managing_stock() && $product->get_stock_quantity() === $args[ 'min_value' ] ) {
				$args[ 'min_value' ] = 0;
				$args[ 'readonly' ]  = true;
			}
		} 
		elseif ( 'yes' === get_option( 'alg_wc_pq_force_cart_min_enabled', 'no' ) ) {
			$args[ 'min_value' ] = 1;
		}
		if ( 'yes' === get_option( 'alg_wc_pq_max_section_enabled', 'no' ) ) {
			$args[ 'max_value' ] = $class_object->set_quantity_input_max( $args[ 'max_value' ], $product );
		}
		if ( 'yes' === get_option( 'alg_wc_pq_step_section_enabled', 'no' ) ) {
			$args[ 'step' ] = $class_object->set_quantity_input_step( $args[ 'step' ], $product );
		}

		// CHANGE: Remove the part for setting the "Fixed quantity" for product archives and single product pages
		// CHANGE: Remove the part for product archives with false positive conditions on pages where fragment updates are used
		// CHANGE: Remove the part for non-WooCommerce pages
		// CHANGE: Remove the part for single product pages
		// CHANGE: Remove the part for product archives

		$args[ 'product_id' ] = ( $product ? $product->get_id() : 0 );

		if ( isset( $_SERVER[ 'REQUEST_METHOD' ] ) && $_SERVER[ 'REQUEST_METHOD' ] != 'POST' ) {
			if ( isset( $args[ 'input_value' ] ) && empty( $args[ 'input_value' ] ) ) {
				$args[ 'input_value' ] = 1;
			}
		}

		if ( isset( $args[ 'step' ] ) && empty( $args[ 'step' ] ) ) {
			$args[ 'step' ] = 1;
		}

		return $args;
	}

}

FluidCheckout_ProductQuantityForWooCommerce::instance();

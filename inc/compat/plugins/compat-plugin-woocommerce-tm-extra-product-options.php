<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Extra Product Options & Add-Ons for WooCommerce (by ThemeComplete).
 */
class FluidCheckout_WooCommerceTMExtraProductOption extends FluidCheckout {

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

		// Maybe set flags
		add_action( 'woocommerce_init', array( $this, 'maybe_set_plugin_flags' ), 1 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {		
		// Bail if class or object doesn't exist
		if ( ! class_exists( 'THEMECOMPLETE_EPO_Associated_Products' ) || ! THEMECOMPLETE_EPO_Associated_Products::instance() ) { return; }

		// Replace hooks
		remove_filter( 'woocommerce_cart_item_quantity', array( THEMECOMPLETE_EPO_Associated_Products::instance(), 'associated_woocommerce_cart_item_quantity' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'associated_woocommerce_cart_item_quantity' ), 10, 2 );
	}



	/**
	 * Maybe set plugin flags.
	 */
	public function maybe_set_plugin_flags() {
		// Bail if class or object doesn't exist
		if ( ! class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) || ! THEMECOMPLETE_Extra_Product_Options::instance() ) { return; }
		
		// Set flags
		THEMECOMPLETE_Extra_Product_Options::instance()->wc_vars[ 'is_checkout' ] = FluidCheckout_Steps::instance()->is_checkout_page_or_fragment();
		THEMECOMPLETE_Extra_Product_Options::instance()->wc_vars[ 'is_cart' ] = FluidCheckout_Steps::instance()->is_cart_page_or_fragment();
	}



	/**
	 * Sync associated products quantity input
	 *
	 * @param integer $quantity The product quantity.
	 * @param string  $cart_item_key The cart item key.
	 * @since 5.0
	 */
	public function associated_woocommerce_cart_item_quantity( $quantity, $cart_item_key ) {
		// CHANGE: Copied from THEMECOMPLETE_EPO_Associated_Products::associated_woocommerce_cart_item_quantity
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];

		if ( isset( $cart_item['associated_parent'] ) && ! empty( $cart_item['associated_parent'] ) ) {

			$parent        = WC()->cart->cart_contents[ $cart_item['associated_parent'] ];
			$associated_id = array_search( $cart_item_key, $parent['associated_products'], true );

			if ( false === $associated_id ) {
				return $quantity;
			}

			if ( $cart_item['tmproducts'][ $associated_id ]['quantity_min'] === $cart_item['tmproducts'][ $associated_id ]['quantity_max'] ) {

				// CHANGED: Add "x" (times char) before the quantity
				$quantity = '<span class="fc-tc-associated-product-quantity">&times;&nbsp;' . esc_html( $cart_item['quantity'] ) . '</span>';

			} else {

				$parent_quantity = 1;
				if ( THEMECOMPLETE_EPO()->tm_epo_global_product_element_quantity_sync === 'yes' ) {
					$parent_quantity = $parent['quantity'];
				}
				$max_stock = $cart_item['data']->managing_stock() && ! $cart_item['data']->backorders_allowed() ? $cart_item['data']->get_stock_quantity() : '';
				$max_stock = null === $max_stock ? '' : $max_stock;

				if ( '' !== $max_stock ) {
					$max_qty = '' !== $cart_item['tmproducts'][ $associated_id ]['quantity_max'] ? min( $max_stock, $parent_quantity * $cart_item['tmproducts'][ $associated_id ]['quantity_max'] ) : $max_stock;
				} else {
					$max_qty = '' !== $cart_item['tmproducts'][ $associated_id ]['quantity_max'] ? $parent_quantity * $cart_item['tmproducts'][ $associated_id ]['quantity_max'] : '';
				}

				$min_qty = floatval( $parent_quantity ) * floatval( $cart_item['tmproducts'][ $associated_id ]['quantity_min'] );

				if ( ( $max_qty > $min_qty || '' === $max_qty ) && ! $cart_item['data']->is_sold_individually() ) {

					$quantity = woocommerce_quantity_input(
						[
							'input_name'  => 'cart[' . $cart_item_key . '][qty]',
							'input_value' => $cart_item['quantity'],
							'min_value'   => $min_qty,
							'max_value'   => $max_qty,
							'step'        => $parent_quantity,
						],
						$cart_item['data'],
						false
					);

				} else {
					// CHANGED: Add "x" (times char) before the quantity
					$quantity = '<span class="fc-tc-associated-product-quantity">&times;&nbsp;' . esc_html( $cart_item['quantity'] ) . '</span>';
				}
			}
		}

		return $quantity;
		// CHANGE: END - Copied from THEMECOMPLETE_EPO_Associated_Products::associated_woocommerce_cart_item_quantity
	}

}

FluidCheckout_WooCommerceTMExtraProductOption::instance();

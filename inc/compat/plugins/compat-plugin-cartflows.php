<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows).
 */
class FluidCheckout_Cartflows extends FluidCheckout {

	/**
	 * Whether the CartFlows checkout field hooks were already removed for this request.
	 *
	 * @var bool
	 */
	private $checkout_field_hooks_removed = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		if ( did_action( 'cartflows_loaded' ) ) {
			$this->remove_checkout_field_hooks();
		} else {
			add_action( 'cartflows_loaded', array( $this, 'remove_checkout_field_hooks' ), 20 );
		}

		// Very late hook as a fallback.
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Remove CartFlows filters that modify the checkout fields.
		$this->remove_checkout_field_hooks();
	}

	/**
	 * Remove CartFlows filters that modify the checkout fields.
	 */
	public function remove_checkout_field_hooks() {
		// Bail if CartFlows checkout markup class is not available.
		if ( ! class_exists( 'Cartflows_Checkout_Markup' ) || ! class_exists( 'Cartflows_Modern_Checkout' ) ) { return; }

		// Removes CartFlows hooks for extra coupon fields, styles and scripts that conflict with Fluid Checkout.
		remove_action( 'wp', array( Cartflows_Checkout_Markup::get_instance(), 'shortcode_load_data' ), 999 );

		// Removes CartFlows hooks for modifying the order summary
		remove_filter( 'woocommerce_cart_item_name', array( Cartflows_Checkout_Markup::get_instance(), 'modify_order_review_item_summary' ), 10 );
		remove_action( 'woocommerce_before_calculate_totals', array( Cartflows_Checkout_Markup::get_instance(), 'custom_price_to_cart_item' ), 9999 );
		remove_filter( 'woocommerce_update_order_review_fragments', array( Cartflows_Checkout_Markup::get_instance(), 'add_updated_cart_price' ), 10 );

		// Removes changes to billing and shipping fields and changes layout of the checkout page.
		remove_action( 'cartflows_checkout_form_before', array( Cartflows_Modern_Checkout::get_instance(), 'modern_checkout_layout_actions' ), 10 );
		remove_filter( 'woocommerce_checkout_fields', array( Cartflows_Modern_Checkout::get_instance(), 'unset_fields_for_modern_checkout' ), 10 );
	}
}

FluidCheckout_Cartflows::instance();


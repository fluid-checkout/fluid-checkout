<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Shop as Client for WooCommerce (by Naked Cat Plugins).
 */
class FluidCheckout_ShopAsClien extends FluidCheckout {

	/**
	 * Stored Shop as Client checkout fields.
	 *
	 * @var array
	 */
	protected $shop_as_client_fields = array();

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
		// Late hooks.
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Move Shop as Client fields to the contact step.
		add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_shop_as_client_fields' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		$this->replace_checkout_fields_hook();
	}



	/**
	 * Replace the default checkout fields hook from the Shop as Client plugin.
	 */
	public function replace_checkout_fields_hook() {
		// Bail if the Shop as Client plugin is not active.
		if ( ! function_exists( 'shop_as_client_init_woocommerce_checkout_fields' ) ) { return; }

		// Remove the default checkout fields hook from the Shop as Client plugin.
		remove_filter( 'woocommerce_checkout_fields', 'shop_as_client_init_woocommerce_checkout_fields', PHP_INT_MAX );

		// Add a new checkout fields hook to capture Shop as Client fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'capture_shop_as_client_fields' ), PHP_INT_MAX );
	}



	/**
	 * Capture Shop as Client fields while preventing them from rendering in the billing section.
	 *
	 * @param array $fields Checkout fields.
	 */
	public function capture_shop_as_client_fields( $fields ) {
		// Bail if the Shop as Client plugin is not active.
		if ( ! function_exists( 'shop_as_client_init_woocommerce_checkout_fields' ) ) { return $fields; }

		// Initialize Shop as Client fields.
		$fields = shop_as_client_init_woocommerce_checkout_fields( $fields );

		// Store Shop as Client fields.
		foreach ( array( 'billing_shop_as_client', 'billing_shop_as_client_create_user' ) as $field_key ) {
			if ( isset( $fields['billing'][ $field_key ] ) ) {
				$this->shop_as_client_fields[ $field_key ] = $fields['billing'][ $field_key ];
				unset( $fields['billing'][ $field_key ] );
			}
		}

		return $fields;
	}



	/**
	 * Output Shop as Client fields at the contact step.
	 */
	public function output_shop_as_client_fields() {
		// Bail if the Shop as Client plugin is not active or the user cannot checkout as a client.
		if ( ! function_exists( 'shop_as_client_init_woocommerce_checkout_fields' ) || ! function_exists( 'shop_as_client_can_checkout' ) ) { return; }

		// Get checkout object.
		$checkout = WC()->checkout();

		// Output Shop as Client fields.
		foreach ( $this->shop_as_client_fields as $field_key => $field_args ) {
			woocommerce_form_field( $field_key, $field_args, $checkout->get_value( $field_key ) );
		}
	}

}

FluidCheckout_ShopAsClien::instance();
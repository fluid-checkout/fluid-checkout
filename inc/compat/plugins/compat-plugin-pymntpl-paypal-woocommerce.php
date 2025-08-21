<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Payment Plugins for PayPal WooCommerce (by Payment Plugins).
 */
class FluidCheckout_PymntplPayPalWooCommerce extends FluidCheckout {

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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Add hidden fields
		add_action( 'woocommerce_checkout_billing', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Prevent saving field values to session
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'add_custom_field_to_persisted_data_skip_fields' ), 10, 2 );
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'maybe_add_shipping_and_billing_fields_to_persisted_data_skip_fields' ), 10, 2 );

		// Customer address properties
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'maybe_reset_customer_address_props_on_process_checkout' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_reset_customer_address_props_on_paypal_rest_request' ), 20 );

		// Saved address
		add_filter( 'fc_save_new_address_data_shipping_skip_update', array( $this, 'maybe_prevent_update_saved_address' ), 10, 2 );
		add_filter( 'fc_save_new_address_data_billing_skip_update', array( $this, 'maybe_prevent_update_saved_address' ), 10, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Customer address data
		add_filter( 'fc_skip_change_customer_address_field_value_from_checkout_data', array( $this, 'maybe_skip_change_customer_address_field_value' ), 10, 2 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout events
		wp_register_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/pymntpl-paypal-woocommerce/paypal-checkout-events' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout', 'window.addEventListener("load",function(){PaymentPluginsPayPalCheckoutEvents.init();})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// The field value indicates if the shipping and billing fields could be potentially altered by the plugin's `frontend-commons.js` script.
		echo '<input type="hidden" id="pymntpl-paypal-woocommerce-fields__altered" name="pymntpl-paypal-woocommerce-fields__altered" value="">';
	}



	/**
	 * Add custom field to the persisted data skip list.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function add_custom_field_to_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		$fields_keys = array(
			'pymntpl-paypal-woocommerce-fields__altered',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}

	/**
	 * Maybe add shipping and billing address fields to the persisted data skip list.
	 * Required to prevent saving wrong field values while the `update_checkout` AJAX event is running.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function maybe_add_shipping_and_billing_fields_to_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $skip_field_keys; }

		// Bail if custom field value hasn't been changed
		if ( empty( $parsed_posted_data[ 'pymntpl-paypal-woocommerce-fields__altered' ] ) ) { return $skip_field_keys; }

		// Shipping and billing fields keys
		$fields_keys = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_country',
			'billing_email',
			'billing_phone',

			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'shipping_country',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}



	/**
	 * Check if REST request from the plugin is being made.
	 * 
	 * @param  string  $request_uri  The request URI to check against.
	 */
	public function is_rest_request_from_plugin( $request_uri = 'ppcp' ) {
		$is_rest_request = false;

		// Bail if not a REST request
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) { return $is_rest_request; }

		// Bail if not a request from the plugin
		if ( ! isset( $_SERVER[ 'REQUEST_URI' ] ) || false === strpos( $_SERVER[ 'REQUEST_URI' ], $request_uri ) ) { return $is_rest_request; }

		// Set the flag to true
		$is_rest_request = true;

		return $is_rest_request;
	}



	/**
	 * Maybe reset customer address properties after the plugin's REST request handling.
	 * Required to set the properties back to the saved address values after the plugin sets 
	 * the customer data to the field values in CartShipping::update_shipping_address().
	 *
	 * @param  WC_Customer  $customer    The customer object.
	 * @param  array        $posted_data  The posted data.
	 */
	public function maybe_reset_customer_address_props_on_process_checkout( $customer, $posted_data ) {
		// Bail if custom field value hasn't been changed
		if ( empty( $_POST[ 'pymntpl-paypal-woocommerce-fields__altered' ] ) ) { return; }

		// Reset properties to the saved values
		$this->reset_customer_address_props( $customer );
	}

	/**
	 * Maybe reset customer address properties after the plugin's REST request handling.
	 * Required to set the properties back to the saved address values after WooCommerce 
	 * sets the customer data to the field values in WC_AJAX::update_order_review().
	 *
	 * @param  WC_Cart  $cart  The cart object.
	 */
	public function maybe_reset_customer_address_props_on_paypal_rest_request( $cart ) {
		// Bail if not a REST request from the plugin
		if ( ! $this->is_rest_request_from_plugin( 'cart/shipping' ) ) { return; }

		// Bail if customer object not available
		if ( ! function_exists( 'WC' ) || null === WC()->customer ) { return; }

		// Bail if custom field value hasn't been changed
		if ( empty( $_POST[ 'pymntpl-paypal-woocommerce-fields__altered' ] ) ) { return; }

		// Reset properties to the saved values
		$this->reset_customer_address_props( WC()->customer );
	}

	/**
	 * Reset customer address properties to the saved values.
	 *
	 * @param  WC_Customer  $customer  The customer object.
	 */
	public function reset_customer_address_props( $customer ) {
		// Get all supported address field keys
		$customer_supported_field_keys = FluidCheckout_Steps::instance()->get_supported_customer_property_field_keys();

		// Loop through each supported field
		foreach ( $customer_supported_field_keys as $field_key ) {
			// Only act on shipping or billing fields
			if ( strpos( $field_key, 'shipping_' ) !== 0 && strpos( $field_key, 'billing_' ) !== 0 ) { continue; }

			// Replace `shipping_` and `billing_` prefixes with `save_shipping_` and `save_billing_`
			$save_field_key = preg_replace( '/^(shipping_|billing_)/', 'save_$1', $field_key );

			// Retrieve the original value from the session
			$new_field_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $save_field_key );

			// Get the name of the setter method for the customer property
			$setter = "set_$field_key";

			// Check if the customer object has this setter method
			if ( is_callable( array( $customer, $setter ) ) ) {
				// If we have an original value, set it back on the customer object
				if ( null !== $new_field_value ) {
					$customer->{$setter}( $new_field_value );
				}
			}
		}
	}



	/**
	 * Maybe skip updating the saved address values.
	 *
	 * @param  bool  $skip_update  Whether to skip the update.
	 */
	public function maybe_prevent_update_saved_address( $skip_update, $posted_data ) {
		// Bail if custom field value hasn't been changed
		if ( empty( $posted_data[ 'pymntpl-paypal-woocommerce-fields__altered' ] ) ) { return $skip_update; }

		// Skip updating address data
		$skip_update = true;

		return $skip_update;
	}



	/**
	 * Maybe skip changing customer address field value.
	 * Required to prevent Fluid Checkout altering field values used by PayPal.
	 * 
	 * @param  bool  $skip  Whether to skip changing the customer address field value.
	 */
	public function maybe_skip_change_customer_address_field_value( $skip ) {
		// Bail if not on front end
		if ( is_admin() ) { return $skip; }

		// Get posted chosen payment method
		// Avoid using `FluidCheckout_Steps::instance()->get_selected_payment_method()` to prevent recursion and infinite loop.
		$chosen_payment_method = '';
		if ( isset( $_POST[ 'payment_method' ] ) ) {
			$chosen_payment_method = $_POST[ 'payment_method' ];
		}

		// Skip if this is a REST request or if the checkout process has started
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || doing_action( 'woocommerce_before_checkout_process' ) || did_action( 'woocommerce_before_checkout_process' ) && 'ppcp' === $chosen_payment_method ) {
			$skip = true;
		}

		return $skip;
	}

}

FluidCheckout_PymntplPayPalWooCommerce::instance();

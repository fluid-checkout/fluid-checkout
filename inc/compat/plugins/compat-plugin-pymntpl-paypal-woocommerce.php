<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Payment Plugins for Stripe WooCommerce (by Payment Plugins).
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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Add hidden fields
		add_action( 'woocommerce_checkout_billing', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Prevent saving field values to session
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'add_persisted_data_skip_fields' ), 10, 2 );
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'maybe_add_shipping_and_billing_fields_to_persisted_data_skip_fields' ), 10, 2 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		$this->maybe_replace_customer_address_data_hooks();
	}



	/**
	 * Maybe replace customer address data hooks.
	 * Required to prevent Fluid Checkout altering field values used in PayPal popup window.
	 */
	public function maybe_replace_customer_address_data_hooks() {
		// Bail if PayPal is not selected as a payment method
		if ( 'ppcp' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Undo default customer address data hooks
		FluidCheckout_Steps::instance()->undo_customer_address_data_hooks();

		// Re-add customer address data hooks
		$this->customer_address_data_hooks();
	}

	/**
	 * Add or remove hooks for the customer address data.
	 */
	public function customer_address_data_hooks() {
		// Define fields to add hooks to, even if the fields are not available at checkout.
		$field_keys = array(
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
			'shipping_phone',
		);

		// Iterate fields and add hook
		foreach ( $field_keys as $field_key ) {
			add_filter( 'woocommerce_customer_get_' . $field_key, array( $this, 'maybe_change_customer_address_field_value_from_checkout_data' ), 10, 2 );
		}
	}



	/**
	 * Maybe change the customer address field value to get data saved to the checkout session.
	 *
	 * @param   mixed        $value      The field value.
	 * @param   WC_Customer  $customer   The customer object.
	 */
	public function maybe_change_customer_address_field_value_from_checkout_data( $value, $customer ) {
		// Bail if this is a REST API request or if the checkout process has already started
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || did_action( 'woocommerce_before_checkout_process' ) ) { return $value; }

		// Get name of the current filter hook running this function
		$hook_name = current_filter();

		// Bail if the hook name is not supported
		if ( strpos( $hook_name, 'woocommerce_customer_get_' ) !== 0 ) { return $value; }

		// Get field key
		$field_key = str_replace( 'woocommerce_customer_get_', '', $hook_name );

		// Get checkout session value
		$session_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( $field_key );

		// Maybe set new value from session value
		if ( ! empty( $session_value ) ) {
			$value = $session_value;
		}

		// Return new value
		return $value;
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
		// Output custom hidden fields
		echo '<div id="pymntpl-paypal-woocommerce-custom_checkout_fields" class="form-row fc-no-validation-icon pymntpl-paypal-woocommerce-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		// The field value indicates if the shipping and billing fields could be potentially altered by the plugin's `frontend-commons.js` script.
		echo '<input type="hidden" id="pymntpl-paypal-woocommerce-fields_altered" name="pymntpl-paypal-woocommerce-fields_altered" value="">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Add "altered fields" indicator to the persisted data skip list.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function add_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		$fields_keys = array(
			'pymntpl-paypal-woocommerce-fields_altered',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}

	/**
	 * Maybe add shipping and billing fields to the persisted data skip list.
	 * Required to prevent saving wrong field values while keeping the `update_checkout` event running.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function maybe_add_shipping_and_billing_fields_to_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $skip_field_keys; }

		// Bail if custom field value hasn't been changed
		if ( empty( $parsed_posted_data[ 'pymntpl-paypal-woocommerce-fields_altered' ] ) ) { return $skip_field_keys; }

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

}

FluidCheckout_PymntplPayPalWooCommerce::instance();

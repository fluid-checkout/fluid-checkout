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

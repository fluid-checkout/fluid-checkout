<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce PayPal Payments (by WooCommerce).
 */
class FluidCheckout_WooCommercePayPalPayments extends FluidCheckout {

	public $smart_button_module;

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

		// Set checkout context
		add_filter( 'woocommerce_paypal_payments_context' , array( $this, 'maybe_change_context_checkout' ), 300 );
		
		// Payment methods
		add_filter( 'fc_checkout_update_on_visibility_change', array( $this, 'disable_update_on_visibility_change' ), 100 );

		// Place order
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'change_paypal_button_hook_name' ), 100 );
		add_filter( 'woocommerce_paypal_payments_checkout_dcc_renderer_hook', array( $this, 'change_paypal_button_hook_name' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Advanced credit card fields
		$this->maybe_replace_advanced_credit_card_button();
	}



	/**
	 * Maybe replace the advanced credit card button output.
	 */
	public function maybe_replace_advanced_credit_card_button() {
		// Bail if class is not available
		$class_name = 'WooCommerce\PayPalCommerce\Button\Assets\SmartButton';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if object or its method is not available
		if ( ! $class_object || ! method_exists( $class_object, 'dcc_renderer' ) ) { return; }

		// Use plugin's hook since the getter method is private
		$hook = apply_filters( 'woocommerce_paypal_payments_checkout_dcc_renderer_hook', 'woocommerce_review_order_after_submit' );

		// Replace button rendering method
		remove_action( $hook, array( $class_object, 'dcc_renderer' ), 11 );
		add_action( $hook, array( $this, 'render_modified_dcc_button' ), 11 );
	}



	/**
	 * Render the modified advanced credit card button.
	 */
	public function render_modified_dcc_button() {
		// Bail if class or its method is not available
		$class_name = 'WooCommerce\PayPalCommerce\PPCP';
		if ( ! class_exists( $class_name ) || ! method_exists( $class_name, 'container' ) ) { return; }

		// Bail if container object or required method is not available
		$container_object = WooCommerce\PayPalCommerce\PPCP::container();
		if ( ! method_exists( $container_object, 'get' ) ) { return; }

		// Get button object
		$button_object = $container_object->get( 'button.smart-button' );

		// Bail if button object or its method is not available
		if ( ! $button_object || ! method_exists( $button_object, 'dcc_renderer' ) ) { return; }

		// Get button HTML
		ob_start();
		$button_object->dcc_renderer();
		$html = ob_get_clean();

		// Get current checkout step
		$current_step = FluidCheckout_Steps::instance()->get_current_step();

		// Maybe disable the place order button if not in the last step
		if ( false !== $current_step && 'yes' === apply_filters( 'fc_checkout_maybe_disable_place_order_button', 'yes' ) && FluidCheckout_Steps::instance()->is_checkout_layout_multistep() ) {
			$current_step_index = array_keys( $current_step )[0];
			$current_step_id = $current_step[ $current_step_index ][ 'step_id' ];

			$last_step = FluidCheckout_Steps::instance()->get_last_step();
			$last_step_index = array_keys( $last_step )[0];
			$last_step_id = $last_step[ $last_step_index ][ 'step_id' ];

			if ( $current_step_id !== $last_step_id ) {
				// Disable button
				$html = str_replace( '<button id="place_order"', '<button disabled id="place_order"', $html );
			}
		}

		// Add Fluid Checkout class to the place order button HTML
		$button_class = esc_attr( apply_filters( 'fc_place_order_button_classes', 'button alt' ) ) . ' fc-place-order-button';
		$html = str_replace( 'ppcp-dcc-order-button', 'ppcp-dcc-order-button ' . $button_class, $html );

		// Output modified HTML
		echo $html;
	}



	/**
	 * Maybe set the context to the plugin.
	 *
	 * @param   string  $context  The current context.
	 */
	public function maybe_change_context_checkout( $context ) {
		// Bail if not target context
		if ( 'checkout-block' !== $context ) { return $context; }
		
		// Otherwise, change the context
		$context = 'checkout';
		return $context;
	}



	/**
	 * Disable update on visibility change.
	 */
	public function disable_update_on_visibility_change( $update_enabled ) {
		// Get available payment methods
		$available_payment_methods = WC()->payment_gateways->get_available_payment_gateways();

		// Check if PayPal Payments is available.
		if ( isset( $available_payment_methods[ 'ppcp-credit-card-gateway' ] ) ) {
			$update_enabled = 'no';
		}

		return $update_enabled;
	}



	/**
	 * Change the position of the PayPal payment buttons to the payment step.
	 */
	public function change_paypal_button_hook_name( $hook_name ) {
		return 'fc_place_order_custom_buttons';
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();

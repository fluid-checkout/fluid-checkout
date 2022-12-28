<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: PaysonCheckout for WooCommerce (by Krokedil).
 */
class FluidCheckout_KrokedilPaysonCheckout20ForWooCommerce extends FluidCheckout {

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
		add_action( 'wp', array( $this, 'maybe_remove_shipping_phone_hooks' ), 5 );
		add_action( 'wp', array( $this, 'very_late_hooks' ), 300 );

		// Persisted data
		add_filter( 'fc_checkout_update_before_unload', array( $this, 'disable_updated_before_unload' ), 10 );
	}

	/**
	 * Maybe remove shipping phone field hooks.
	 */
	public function maybe_remove_shipping_phone_hooks() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if Payson is not the selected payment method
		if ( 'paysoncheckout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Shipping phone
		if ( class_exists( 'FluidCheckout_CheckoutShippingPhoneField' ) ) {
			FluidCheckout_CheckoutShippingPhoneField::instance()->undo_hooks();
		}
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if Payson is not the selected payment method
		if ( 'paysoncheckout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Switch payment method button
		remove_action( 'pco_wc_before_snippet', 'pco_wc_show_another_gateway_button', 20 );
		add_action( 'pco_wc_after_order_review', 'pco_wc_show_another_gateway_button', 20 );

		// Undo enqueue hooks
		if ( class_exists( 'FluidCheckout_Enqueue' ) ) {
			FluidCheckout_Enqueue::instance()->undo_hooks();
		}

		// Undo hooks from Fluid Checkout classes
		$features_list = FluidCheckout::instance()->get_features_list();
		$skip_undo_hooks_classes = array( 'FluidCheckout_CheckoutPageTemplate', 'FluidCheckout_CheckoutWidgetAreas' );
		foreach ( $features_list as $class_name => $args ) {
			// Skip some classes
			if ( in_array( $class_name, $skip_undo_hooks_classes ) ) { continue; }

			// Skip classes that don't exist or do not have the undo hooks function
			if ( ! class_exists( $class_name ) || ! method_exists( $class_name::instance(), 'undo_hooks' ) ) { continue; }

			// Run undo hooks
			$class_name::instance()->undo_hooks();
		}
	}



	/**
	 * Disable the update before unload the checkout page when there are unsaved changes.
	 */
	public function disable_updated_before_unload( $update_before_unload ) {
		return 'no';
	}

}

FluidCheckout_KrokedilPaysonCheckout20ForWooCommerce::instance();

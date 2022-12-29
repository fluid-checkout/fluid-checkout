<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Klarna Checkout for WooCommerce (by Krokedil).
 */
class FluidCheckout_KlarnaCheckoutForWooCommerce extends FluidCheckout {

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
		// Undo hooks
		add_action( 'wp', array( $this, 'maybe_undo_hooks_early' ), 5 ); // Before very late hooks
		add_action( 'wp', array( $this, 'maybe_undo_hooks' ), 300 ); // After very late hooks

		// Persisted data
		add_filter( 'fc_checkout_update_before_unload', array( $this, 'disable_updated_before_unload' ), 10 );
	}

	/**
	 * Maybe undo hooks early.
	 */
	public function maybe_undo_hooks_early() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if Klarna is not the selected payment method
		if ( 'kco' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Undo hooks from Fluid Checkout classes
		$features_list = FluidCheckout::instance()->get_features_list();
		$skip_undo_hooks_classes = array( 'FluidCheckout_CheckoutPageTemplate', 'FluidCheckout_CheckoutWidgetAreas' );
		foreach ( $features_list as $class_name => $args ) {
			// Skip some classes
			if ( in_array( $class_name, $skip_undo_hooks_classes ) ) { continue; }

			// Skip classes that don't exist or do not have the undo hooks function
			if ( ! class_exists( $class_name ) || ! method_exists( $class_name::instance(), 'undo_hooks_early' ) ) { continue; }

			// Run undo hooks
			$class_name::instance()->undo_hooks_early();
		}
	}

	/**
	 * Maybe undo hooks.
	 */
	public function maybe_undo_hooks() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if Klarna is not the selected payment method
		if ( 'kco' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Undo enqueue hooks
		if ( class_exists( 'FluidCheckout_PRO_Enqueue' ) ) {
			FluidCheckout_PRO_Enqueue::instance()->undo_hooks();
		}

		// Undo hooks from Fluid Checkout classes
		$features_list = FluidCheckout::instance()->get_features_list();
		$skip_undo_hooks_classes = array( 'FluidCheckout_CheckoutPageTemplate' );
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

FluidCheckout_KlarnaCheckoutForWooCommerce::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Svea Checkout for WooCommerce (by The Generation AB).
 */
class FluidCheckout_SveaCheckoutForWooCommerce extends FluidCheckout {

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
		// Checkout fragments
		add_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_request_as_checkout_fragment' ), 10 );

		// Undo hooks
		add_action( 'wp', array( $this, 'maybe_undo_hooks_early' ), 5 ); // Before very late hooks
		add_action( 'wp', array( $this, 'maybe_undo_hooks' ), 300 ); // After very late hooks

		// Persisted data
		add_filter( 'fc_checkout_update_before_unload', array( $this, 'disable_updated_before_unload' ), 10 );
	}



	/**
	 * Maybe set the current request as a checkout fragment when Svea Checkout requests to update the checkout fragments.
	 */
	public function maybe_set_request_as_checkout_fragment( $is_checkout_fragment ) {
		global $wp_query;

		// Get AJAX action
		$ajax_action = ! empty( $wp_query ) ? $wp_query->get( 'wc-ajax' ) : '';
		if ( empty( $ajax_action ) && array_key_exists( 'wc-ajax', $_GET ) ) {
			$ajax_action = sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) );
		}

		// Bail if not a Svea Checkout request to update the checkout fragments
		if ( ! in_array( $ajax_action, array( 'refresh_sco_snippet', 'update_sco_order_nshift_information' ), true ) ) { return $is_checkout_fragment; }

		return true;
	}



	/**
	 * Get classes to skip undo early hooks.
	 */
	public function get_skip_classes_undo_hooks_early_list() {
		$skip_undo_hooks_classes = apply_filters( 'fc_compat_klarna_checkout_skip_undo_hooks_early_classes', array( 'FluidCheckout_CheckoutWidgetAreas' ) );
		return $skip_undo_hooks_classes;
	}

	/**
	 * Get classes to skip undo hooks.
	 */
	public function get_skip_classes_undo_hooks_list() {
		$skip_undo_hooks_classes = apply_filters( 'fc_compat_klarna_checkout_skip_undo_hooks_classes', array() );
		return $skip_undo_hooks_classes;
	}



	/**
	 * Maybe undo hooks early.
	 */
	public function maybe_undo_hooks_early() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if this payment method is not currently selected
		if ( 'svea_checkout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Undo hooks from feature classes
		$features_list = FluidCheckout::instance()->get_features_list();
		$skip_undo_hooks_classes = $this->get_skip_classes_undo_hooks_early_list();
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

		// Bail if this payment method is not currently selected
		if ( 'svea_checkout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return; }

		// Undo enqueue hooks
		if ( class_exists( 'FluidCheckout_Enqueue' ) ) {
			FluidCheckout_Enqueue::instance()->undo_hooks();
		}

		// Undo hooks from feature classes
		$features_list = FluidCheckout::instance()->get_features_list();
		$skip_undo_hooks_classes = $this->get_skip_classes_undo_hooks_list();
		foreach ( $features_list as $class_name => $args ) {
			// Skip some classes
			if ( in_array( $class_name, $skip_undo_hooks_classes ) ) { continue; }

			// Skip classes that don't exist or do not have the undo hooks function
			if ( ! class_exists( $class_name ) || ! method_exists( $class_name::instance(), 'undo_hooks' ) ) { continue; }

			// Run undo hooks
			$class_name::instance()->undo_hooks();
		}

		// Remove payment section from positions where Svea does not remove it,
		// otherwise Svea scripts reload the page indefinitely when a payment method is selected
		// on the Svea checkout page.
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'woocommerce_checkout_shipping', 'woocommerce_checkout_payment', 20 );
	}



	/**
	 * Disable the update before unload the checkout page when there are unsaved changes.
	 */
	public function disable_updated_before_unload( $update_before_unload ) {
		return 'no';
	}

}

FluidCheckout_SveaCheckoutForWooCommerce::instance();

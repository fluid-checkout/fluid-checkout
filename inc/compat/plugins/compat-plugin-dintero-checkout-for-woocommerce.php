<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Dintero Checkout for WooCommerce (by Krokedil).
 */
class FluidCheckout_DinteroCheckoutForWooCommerce extends FluidCheckout {

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
		add_action( 'wp', array( $this, 'maybe_undo_place_order_hooks' ), 300 ); // After very late hooks

		// Body class
		add_filter( 'body_class', array( $this, 'maybe_add_body_class_hide_place_order' ), 10, 2 );

		// Persisted data
		add_filter( 'fc_checkout_update_before_unload', array( $this, 'disable_updated_before_unload' ), 10 );
	}



	/**
	 * Determine if should undo hooks for the selected payment method from this plugin.
	 */
	public function should_undo_hooks() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return false; }

		// Bail if Dintero is not selected at checkout
		if ( 'dintero_checkout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return false; }

		// Bail if this payment method is not currently selected
		$settings = get_option( 'woocommerce_dintero_checkout_settings' );
		if ( ! is_array( $settings ) || ! array_key_exists( 'checkout_flow', $settings ) ) { return false; }

		// Define checkout flow options that require undo hooks
		$target_checkout_flow_options = array(
			'express_popout',
			'express_embedded',
		);

		// Bail if checkout flow option should not undo hooks
		if ( ! in_array( $settings[ 'checkout_flow' ], $target_checkout_flow_options ) ) { return false; }

		// Should undo hooks.
		return true;
	}

	/**
	 * Determine if should hide the place order section for the selected payment method from this plugin.
	 */
	public function should_hide_place_order() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return false; }

		// Bail if Dintero is not selected at checkout
		if ( 'dintero_checkout' !== FluidCheckout_Steps::instance()->get_selected_payment_method() ) { return false; }

		// Bail if this payment method is not currently selected
		$settings = get_option( 'woocommerce_dintero_checkout_settings' );
		if ( ! is_array( $settings ) || ! array_key_exists( 'checkout_flow', $settings ) ) { return false; }

		// Define checkout flow options that require undo hooks
		$target_checkout_flow_options = array(
			'checkout_popout',
			'checkout_embedded',
		);

		// Bail if checkout flow option should not undo hooks
		if ( ! in_array( $settings[ 'checkout_flow' ], $target_checkout_flow_options ) ) { return false; }

		// Should hide place order.
		return true;
	}



	/**
	 * Get classes to skip undo early hooks.
	 */
	public function get_skip_classes_undo_hooks_early_list() {
		$skip_undo_hooks_classes = apply_filters( 'fc_compat_dintero_checkout_skip_undo_hooks_early_classes', array( 'FluidCheckout_CheckoutWidgetAreas' ) );
		return $skip_undo_hooks_classes;
	}

	/**
	 * Get classes to skip undo hooks.
	 */
	public function get_skip_classes_undo_hooks_list() {
		$skip_undo_hooks_classes = apply_filters( 'fc_compat_dintero_checkout_skip_undo_hooks_classes', array() );
		return $skip_undo_hooks_classes;
	}



	/**
	 * Maybe undo hooks for place order.
	 */
	public function maybe_undo_place_order_hooks() {
		// Bail if should not undo hooks
		if ( ! $this->should_undo_hooks() ) { return; }

		// Place order
		remove_action( 'fc_place_order', array( FluidCheckout_Steps::instance(), 'output_checkout_place_order' ), 10 );
		remove_action( 'fc_place_order', array( FluidCheckout_Steps::instance(), 'output_checkout_place_order_custom_buttons' ), 20 );
		remove_action( 'woocommerce_order_button_html', array( FluidCheckout_Steps::instance(), 'add_place_order_button_wrapper_and_attributes' ), 10 );

		// Place order placeholder
		remove_action( 'fc_checkout_end_step', array( FluidCheckout_Steps::instance(), 'maybe_output_checkout_place_order_placeholder_for_substep' ), 100 );
		remove_action( 'fc_checkout_after_order_review_inside', array( FluidCheckout_Steps::instance(), 'output_checkout_place_order_placeholder' ), 1 );

		// Widget areas
		remove_action( 'fc_place_order', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 50 );
	}

	/**
	 * Maybe undo hooks early.
	 */
	public function maybe_undo_hooks_early() {
		// Bail if should not undo hooks
		if ( ! $this->should_undo_hooks() ) { return; }

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
		// Bail if should not undo hooks
		if ( ! $this->should_undo_hooks() ) { return; }

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
	}



	/**
	 * Disable the update before unload the checkout page when there are unsaved changes.
	 */
	public function disable_updated_before_unload( $update_before_unload ) {
		return 'no';
	}



	/**
	 * Add body class to hide the place order section.
	 */
	public function maybe_add_body_class_hide_place_order( $classes, $class ) {
		// Bail if not at checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $classes; }

		// Bail if should not hide place order for the selected payment method
		if ( ! $this->should_hide_place_order() ) { return $classes; }

		// Add class
		$classes[] = 'has-dintero-place-order';

		return $classes;
	}

}

FluidCheckout_DinteroCheckoutForWooCommerce::instance();

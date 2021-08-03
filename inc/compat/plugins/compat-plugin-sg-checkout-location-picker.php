<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Sg Checkout Location Picker for WooCommerce (by Sevengits).
 */
class FluidCheckout_SGCheckoutLocationPicker extends FluidCheckout {

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
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'Sg_Checkout_Location_Picker_Public' ) ) {
			if ( get_option('sg_enable_picker') == 'enable' ) {
				// Remove hooks
				$this->remove_action_for_class( 'woocommerce_after_checkout_billing_form', array( 'Sg_Checkout_Location_Picker_Public', 'showBillingMap' ), 100 );
				$this->remove_action_for_class( 'woocommerce_after_checkout_shipping_form', array( 'Sg_Checkout_Location_Picker_Public', 'showshippingMap' ), 100 );
				
				// Re-add hooks in different position
				$plugin_public = new Sg_Checkout_Location_Picker_Public( 'sg-checkout-location-picker', SG_CHECKOUT_LOCATION_PICKER_VERSION );
				add_action( 'fc_after_substep_billing_address', array( $plugin_public, 'showBillingMap' ), 10 );
				add_action( 'fc_after_substep_shipping_address', array( $plugin_public, 'showshippingMap' ), 10 );
			}
		}
	}

}

FluidCheckout_SGCheckoutLocationPicker::instance();

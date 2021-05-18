<?php

/**
 * Checkout admin options.
 */
class FluidCheckout_AdminCheckout extends FluidCheckout {

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
		// WooCommerce Settings
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_checkout_settings_tab' ), 50 );
		// TODO: Add "Checkout > General" sub tab
	}



	/**
	 * Add the Checkout settings tab.
	 *
	 * @param   array  $settings_tabs  List of the WooCommerce settings tabs.
	 *
	 * @return  array                  Modified list of the WooCommerce settings tabs.
	 */
	public function add_checkout_settings_tab( $settings_tabs ) {
		// Get token position (after "Payments" tab)
		$position_index = array_search( 'checkout', array_keys( $settings_tabs ) ) + 1;

		// Insert at token position
		$new_settings_tabs  = array_slice( $settings_tabs, 0, $position_index );
		$new_settings_tabs[ 'wfc_checkout' ] = __( 'Checkout', 'woocommerce-fluid-checkout' );
		$new_settings_tabs = array_merge( $new_settings_tabs, array_slice( $settings_tabs, $position_index, count( $settings_tabs ) ) );

		return $new_settings_tabs;
	}

}

FluidCheckout_AdminCheckout::instance();

<?php
/**
 * Fluid Checkout Settings Page.
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Checkout', false ) ) {
	return new WC_Settings_FluidCheckout_Checkout();
}

/**
 * WC_Settings_FluidCheckout_Checkout.
 */
class WC_Settings_FluidCheckout_Checkout extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'fc_checkout';
		$this->label = __( 'Fluid Checkout', 'fluid-checkout' );

		parent::__construct();
	}



	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		/**
		 * Filters the sections of the Fluid Checkout WooCommerce settings tab.
		 *
		 * The dynamic portion of the hook name, `$this->id`, is the settings tab ID.
		 * Possible hook names include:
		 *
		 * - `woocommerce_get_sections_fc_checkout`
		 *
		 * @since 1.2.0
		 *
		 * @param array $value Value to filter. Default empty array.
		 */
		return apply_filters( 'woocommerce_get_sections_' . $this->id, array() );
	}



	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}



	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			/**
			 * Fires when a Fluid Checkout settings section is saved.
			 *
			 * The dynamic portions of the hook name are the settings tab ID (`$this->id`,
			 * `fc_checkout`) and the current section slug. The action only runs when a section slug is
			 * present. Additional sections registered on the tab create further hook names.
			 * Possible hook names include:
			 *
			 * - `woocommerce_update_options_fc_checkout_checkout`
			 * - `woocommerce_update_options_fc_checkout_cart`
			 * - `woocommerce_update_options_fc_checkout_order_pay`
			 * - `woocommerce_update_options_fc_checkout_order_received`
			 * - `woocommerce_update_options_fc_checkout_integrations`
			 * - `woocommerce_update_options_fc_checkout_tools`
			 * - `woocommerce_update_options_fc_checkout_license_keys`
			 *
			 * @since 1.2.0
			 */
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}



	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		/**
		 * Filters the settings fields for the Fluid Checkout WooCommerce settings tab.
		 *
		 * The dynamic portion of the hook name, `$this->id`, is the settings tab ID.
		 * Possible hook names include:
		 *
		 * - `woocommerce_get_settings_fc_checkout`
		 *
		 * @since 1.2.0
		 *
		 * @param array  $value           Value to filter. Default empty array.
		 * @param string $current_section Current settings section slug. An empty string is the dashboard section.
		 */
		return apply_filters( 'woocommerce_get_settings_' . $this->id, array(), $current_section );
	}

}

return new WC_Settings_FluidCheckout_Checkout();

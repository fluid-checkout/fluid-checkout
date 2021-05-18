<?php
/**
 * WooCommerce Checkout Settings
 *
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$this->id    = 'wfc_checkout';
		$this->label = __( 'Checkout', 'woocommerce-fluid-checkout' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''             => __( 'General', 'woocommerce-fluid-checkout' ),
			'advanced'     => __( 'Advanced', 'woocommerce-fluid-checkout' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
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
		if ( 'advanced' === $current_section ) {
			$settings = apply_filters(
				'wfc_checkout_advanced_settings',
				array(
					array(
						'title' => __( 'Advanced', 'woocommerce' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wfc_checkout_advanced_options',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'wfc_checkout_advanced_options',
					),
				)
			);
		}
		else {
			$settings = apply_filters(
				'wfc_checkout_general_settings',
				array(
					array(
						'title' => __( 'Layout', 'woocommerce' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wfc_checkout_layout_options',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'wfc_checkout_layout_options',
					),
				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}
}

return new WC_Settings_FluidCheckout_Checkout();

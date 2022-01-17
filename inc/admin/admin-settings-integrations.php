<?php
/**
 * Fluid Checkout Integration Settings
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Integrations_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Integrations_Settings();
}

/**
 * WC_Settings_FluidCheckout_Integrations_Settings.
 */
class WC_Settings_FluidCheckout_Integrations_Settings extends WC_Settings_Page {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->id = 'fc_checkout';
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Sections
		add_filter( 'woocommerce_get_sections_fc_checkout', array( $this, 'add_sections' ), 10 );

		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public function add_sections( $sections ) {
		// Define sections to insert
		$insert_sections = array(
			'integrations' => __( 'Integrations', 'fluid-checkout' ),
		);

		// Get token position
		$position_index = count( $sections );
		for ( $index = 0; $index < count( $sections ); $index++ ) {
			if ( 'advanced' == array_keys( $sections )[ $index ] ) {
				$position_index = $index;
			}
		}

		// Insert at token position
		$new_sections = array_slice( $sections, 0, $position_index );
		$new_sections = array_merge( $new_sections, $insert_sections );
		$new_sections = array_merge( $new_sections, array_slice( $sections, $position_index, count( $sections ) ) );
		
		return $new_sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'integrations' === $current_section ) {

			$settings_new = array(
				array(
					'title' => _x( 'Integrations', 'Settings section title', 'fluid-checkout' ),
					'type'  => 'title',
					'id'    => 'fc_integrations',
				),
			);

			$settings_add = apply_filters( 'fc_'.$current_section.'_settings_add', array(), $current_section );

			// Maybe add notice when no integrations are available
			if ( 0 == count( $settings_add ) ) {
				$settings_add[] = array(
					'type'        => 'fc_paragraph',
					'desc'        => __( 'No integrations available at the moment on this section. The options related to each plugin will only appear here if that plugin is activated.', 'fluid-checkout' ),
					'id'          => 'fc_no_integrations',
				);
			}

			$settings_new = array_merge( $settings_new, $settings_add, array(
				array(
					'type' => 'sectionend',
					'id'   => 'fc_integrations',
				),
			) );

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings_new, $current_section );
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Integrations_Settings();

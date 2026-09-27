<?php
/**
 * Fluid Checkout Integration Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_Integrations', false ) ) {
	FluidCheckout_Settings_Integrations::hooks();
	return;
}

/**
 * FluidCheckout_Settings_Integrations.
 */
class FluidCheckout_Settings_Integrations {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
		// Define sections to insert
		$insert_sections = array(
			'integrations' => __( 'Integrations', 'fluid-checkout' ),
		);

		// Get token position
		$position_index = count( $sections );
		for ( $index = 0; $index < count( $sections ); $index++ ) {
			if ( 'tools' == array_keys( $sections )[ $index ] ) {
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
	public static function add_settings( $settings, $current_section ) {
		if ( 'integrations' === $current_section ) {

			// Get settings for each integration, each with its own settings section
			$settings_new = apply_filters( 'fc_'.$current_section.'_settings_add', array(), $current_section );

			// Maybe add a section with a notice when no integrations are available
			if ( 0 === count( $settings_new ) ) {
				$settings_new = array(
					array(
						'title' => _x( 'Integrations', 'Settings section title', 'fluid-checkout' ),
						'type'  => 'title',
						'id'    => 'fc_integrations',
					),

					array(
						'type'        => 'fc_paragraph',
						'desc'        => __( 'No integrations available at the moment on this section. The options related to each plugin and theme will only appear here when that plugin or theme is activated.', 'fluid-checkout' ),
						'id'          => 'fc_no_integrations',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_integrations',
					),
				);
			}

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings_new, $current_section );
		}

		return $settings;
	}

}

FluidCheckout_Settings_Integrations::hooks();

<?php
/**
 * Fluid Checkout Tools Settings
 *
 * @package fluid-checkout
 * @version 1.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Tools_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Tools_Settings();
}

/**
 * WC_Settings_FluidCheckout_Tools_Settings.
 */
class WC_Settings_FluidCheckout_Tools_Settings extends WC_Settings_Page {

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
			'tools' => __( 'Tools', 'fluid-checkout' ),
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
		if ( 'tools' === $current_section ) {

			$settings = array(

				array(
					'title' => __( 'Troubleshooting', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_advanced_debug_options',
				),

				array(
					'title'            => __( 'Debug options', 'fluid-checkout' ),
					'desc'             => __( 'Debug mode', 'fluid-checkout' ),
					'desc_tip'         => __( 'Using debug mode affects the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
					'id'               => 'fc_debug_mode',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_debug_mode' ),
					'checkboxgroup'    => 'start',
					'show_if_checked'  => 'option',
					'autoload'         => false,
				),
				array(
					'desc'             => __( 'Load unminified assets', 'fluid-checkout' ),
					'id'               => 'fc_load_unminified_assets',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_load_unminified_assets' ),
					'checkboxgroup'    => 'end',
					'show_if_checked'  => 'yes',
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Enhanced select fields', 'fluid-checkout' ),
					'desc'             => __( 'Replace <code>select2</code> dropdown components with <code>TomSelect</code>', 'fluid-checkout' ),
					'desc_tip'         => __( 'TomSelect is a simpler dropdown selection component which is less prone to errors than Select2, while offering the same features that are actually used on WooCommerce checkout pages.', 'fluid-checkout' ),
					'id'               => 'fc_use_enhanced_select_components',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_use_enhanced_select_components' ),
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Fix automatic zoom-in on form fields', 'fluid-checkout' ),
					'desc'             => __( 'Set <code>font-size</code> inside form fields to 16px', 'fluid-checkout' ),
					'desc_tip'         => __( 'When the font size inside form fields is smaller than 16px, Safari and other browsers might automatically zoom in on mobile devices to make the text easier to read. When this option is enabled, it will set the font size for inside the form fields to 16px on pages optimized by Fluid Checkout to avoid it zooming in.', 'fluid-checkout' ),
					'id'               => 'fc_fix_zoom_in_form_fields_mobile_devices',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_fix_zoom_in_form_fields_mobile_devices' ),
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_advanced_debug_options',
				),

				array(
					'title' => __( 'Backup & reset', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => __( 'Export, import, reset, or restore Fluid Checkout settings, including related WooCommerce and integration options. License keys, API keys, and troubleshooting options are skipped on export and import; license and API keys also stay on reset. Import can update matching settings or replace all after a review. A backup is created before import or reset.', 'fluid-checkout' ),
					'id'    => 'fc_checkout_settings_tools',
				),

				array(
					'title'       => __( 'Reset settings', 'fluid-checkout' ),
					'desc'        => __( 'Delete saved settings so defaults are used again.', 'fluid-checkout' ),
					'type'        => 'fc_settings_tools',
					'id'          => 'fc_settings_tools_reset',
					'tool_action' => 'reset',
					'is_option'   => false,
				),

				array(
					'title'       => __( 'Restore previous settings', 'fluid-checkout' ),
					'desc'        => __( 'Undo the last import or reset.', 'fluid-checkout' ),
					'type'        => 'fc_settings_tools',
					'id'          => 'fc_settings_tools_restore',
					'tool_action' => 'restore',
					'is_option'   => false,
				),

				array(
					'title'       => __( 'Export settings', 'fluid-checkout' ),
					'desc'        => __( 'Download current settings as JSON.', 'fluid-checkout' ),
					'type'        => 'fc_settings_tools',
					'id'          => 'fc_settings_tools_export',
					'tool_action' => 'export',
					'is_option'   => false,
				),

				array(
					'title'       => __( 'Import settings', 'fluid-checkout' ),
					'desc'        => __( 'Logo and linked page IDs are site-specific; re-select them after import if needed.', 'fluid-checkout' ),
					'type'        => 'fc_settings_tools',
					'id'          => 'fc_settings_tools_import',
					'tool_action' => 'import',
					'is_option'   => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_settings_tools',
				),

			);

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings, $current_section );
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Tools_Settings();

<?php
/**
 * Fluid Checkout Tools Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_Tools', false ) ) {
	FluidCheckout_Settings_Tools::hooks();
	return;
}

/**
 * FluidCheckout_Settings_Tools.
 */
class FluidCheckout_Settings_Tools {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );

		// Site report settings
		add_action( 'fc_admin_settings_saved', array( __CLASS__, 'maybe_sync_telemetry_cron_on_fc_settings_saved' ), 10 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
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
	public static function add_settings( $settings, $current_section ) {
		if ( 'tools' === $current_section ) {

			$telemetry_data_groups = FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_data_groups', FluidCheckout_Settings::instance()->get_option_default( 'fc_telemetry_data_groups' ) );

			// Fallback when the stored option is not an array
			if ( ! is_array( $telemetry_data_groups ) ) {
				$telemetry_data_groups = array( 'basic_environment' );
			}

			$telemetry_data_groups_conditional = array(
				'data-conditional-id'    => 'fc_telemetry_enabled',
				'data-conditional-value' => 'yes',
			);

			$settings = array(

				array(
					'title' => __( 'Usage tracking', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => __( 'Send non-sensitive site environment reports to help Fluid Checkout improve compatibility. Reports are not sent until enabled in the options below.', 'fluid-checkout' ),
					'id'    => 'fc_checkout_telemetry_options',
				),

				array(
					'title'           => __( 'Usage tracking', 'fluid-checkout' ),
					'desc'            => __( 'Send usage tracking reports to Fluid Checkout', 'fluid-checkout' ),
					'desc_tip'        => __( 'These reports help us improve compatibility and support for your site and are sent weekly when enabled.', 'fluid-checkout' ) . '<br>' .
									 __( 'No customer, admin user, or any other sensitive data is included in the reports.', 'fluid-checkout' ),
					'id'              => 'fc_telemetry_enabled',
					'type'            => 'fc_telemetry_enable',
					'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_telemetry_enabled' ),
					'autoload'        => false,
				),
				array(
					'title'             => '',
					'desc'              => __( 'Basic environment info', 'fluid-checkout' ),
					'desc_tip'          => __( 'WordPress, PHP, WooCommerce, theme, and plugin list data. Always included when reporting is enabled. Helps us understand the environment your site is running in.', 'fluid-checkout' ),
					'id'                => 'fc_telemetry_data_groups_basic_environment',
					'field_name'        => 'fc_telemetry_data_groups',
					'checkbox_value'    => 'basic_environment',
					'type'              => 'checkbox',
					'value'             => $telemetry_data_groups,
					'control_disabled'  => true,
					'is_option'         => false,
					'custom_attributes' => $telemetry_data_groups_conditional,
					'autoload'          => false,
				),
				array(
					'title'             => '',
					'desc'              => __( 'Plugin settings', 'fluid-checkout' ),
					'desc_tip'          => __( 'Sends Fluid Checkout plugin settings to help with support requests and helps us understand how you are using our plugins.', 'fluid-checkout' ),
					'id'                => 'fc_telemetry_data_groups_plugin_settings',
					'field_name'        => 'fc_telemetry_data_groups',
					'checkbox_value'    => 'plugin_settings',
					'type'              => 'checkbox',
					'value'             => $telemetry_data_groups,
					'badge'             => __( 'Coming soon', 'fluid-checkout' ),
					'control_disabled'  => true,
					'is_option'         => false,
					'custom_attributes' => $telemetry_data_groups_conditional,
					'autoload'          => false,
				),
				array(
					'title'             => '',
					'desc'              => __( 'Sales metrics', 'fluid-checkout' ),
					'desc_tip'          => __( 'Monthly order count and total sales for one year prior to installing Fluid Checkout up to today. Helps us understand if you are making more money with our plugins installed. No customer or user data is ever sent.', 'fluid-checkout' ),
					'id'                => 'fc_telemetry_data_groups',
					'field_name'        => 'fc_telemetry_data_groups',
					'checkbox_value'    => 'woocommerce_sales_metrics',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_telemetry_data_groups' ),
					'custom_attributes' => $telemetry_data_groups_conditional,
					'autoload'          => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_telemetry_options',
				),

				array(
					'title' => __( 'Utilities', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_utilities_options',
				),

				array(
					'title'            => __( 'Enhanced select fields', 'fluid-checkout' ),
					'desc'             => __( 'Use <code>TomSelect</code> dropdown components instead of <code>select2</code>', 'fluid-checkout' ),
					'desc_tip'         => __( 'TomSelect is a simpler dropdown selection component which is less prone to errors than Select2, while offering the same functionality.', 'fluid-checkout' ),
					'id'               => 'fc_use_enhanced_select_components',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_use_enhanced_select_components' ),
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Fix automatic zoom-in on form fields', 'fluid-checkout' ),
					'desc'             => __( 'Set <code>font-size</code> inside form fields to 16px', 'fluid-checkout' ),
					'desc_tip'         => __( 'Sets the font size inside form fields to 16px on pages optimized by this plugin to avoid automatically zooming in when interacting with form fields on small devices.', 'fluid-checkout' ) . '<br><br>' . __( 'Safari and other browsers might automatically zoom in on mobile devices to make the text easier to read when the font size is smaller than 16px.', 'fluid-checkout' ),
					'id'               => 'fc_fix_zoom_in_form_fields_mobile_devices',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_fix_zoom_in_form_fields_mobile_devices' ),
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_utilities_options',
				),

				array(
					'title' => __( 'Troubleshooting', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_advanced_debug_options',
				),

				array(
					'title'            => __( 'Debug options', 'fluid-checkout' ),
					'desc'             => __( 'Debug mode', 'fluid-checkout' ),
					'desc_tip'         => __( 'Enable script processing tracking on the browser console.', 'fluid-checkout' ) . '<br><br>' . __( 'Using debug mode affects the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
					'id'               => 'fc_debug_mode',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_debug_mode' ),
					'autoload'         => false,
				),
				array(
					'title'            => __( 'Unminified assets', 'fluid-checkout' ),
					'desc'             => __( 'Load unminified assets', 'fluid-checkout' ),
					'id'               => 'fc_load_unminified_assets',
					'type'             => 'checkbox',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_load_unminified_assets' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_debug_mode',
						'data-conditional-value' => 'yes',
					),
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_advanced_debug_options',
				),

			);

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings, $current_section );
		}

		return $settings;
	}



	/**
	 * Sanitize site report settings on save.
	 *
	 * @param mixed $value     Sanitized option value.
	 * @param array $option    Option definition.
	 * @param mixed $raw_value Raw option value.
	 */
	public static function sanitize_telemetry_settings( $value, $option, $raw_value ) {
		if ( empty( $option['id'] ) || 'fc_telemetry_data_groups' !== $option['id'] ) {
			return $value;
		}

		// Preserve stored groups when reporting is disabled and the field is hidden.
		if ( 'no' === FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_enabled', 'no' ) ) {
			return self::normalize_telemetry_data_groups( FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_data_groups', array( 'basic_environment' ) ) );
		}

		$groups = is_array( $raw_value ) ? $raw_value : array();

		return self::normalize_telemetry_data_groups( $groups );
	}



	/**
	 * Normalize selected site report data groups.
	 *
	 * @param mixed $groups Raw or sanitized group values.
	 */
	public static function normalize_telemetry_data_groups( $groups ) {
		$allowed = array( 'basic_environment', 'plugin_settings', 'woocommerce_sales_metrics' );

		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		$groups = array_values(
			array_intersect(
				array_map( 'sanitize_key', $groups ),
				$allowed
			)
		);

		if ( empty( $groups ) ) {
			return array( 'basic_environment' );
		}

		if ( ! in_array( 'basic_environment', $groups, true ) ) {
			$groups[] = 'basic_environment';
		}

		$dependent_groups = array( 'plugin_settings', 'woocommerce_sales_metrics' );

		if ( array_intersect( $groups, $dependent_groups ) && ! in_array( 'basic_environment', $groups, true ) ) {
			$groups[] = 'basic_environment';
		}

		return array_values( array_unique( $groups ) );
	}



	/**
	 * Schedule or clear the site report cron when Tools settings are saved.
	 */
	public static function maybe_sync_telemetry_cron_on_settings_saved() {
		// Bail if not saving Fluid Checkout Tools settings
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) || 'fc_checkout' !== wp_unslash( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['section'] ) || 'tools' !== wp_unslash( $_GET['section'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		self::sync_telemetry_cron();
	}

	/**
	 * Schedule or clear the site report cron when Tools settings are saved on the Fluid Checkout settings page.
	 *
	 * @param  string  $tab  Settings tab slug.
	 */
	public static function maybe_sync_telemetry_cron_on_fc_settings_saved( $tab ) {
		// Bail if not saving the Tools tab
		if ( 'tools' !== $tab ) { return; }

		self::sync_telemetry_cron();
	}

	/**
	 * Schedule or clear the site report cron based on the site report settings.
	 */
	public static function sync_telemetry_cron() {
		// Bail if telemetry client is not available
		if ( ! class_exists( 'FC_Telemetry_Client' ) ) { return; }

		$api_url = FluidCheckout::get_telemetry_api_url();
		$config  = FC_Telemetry_Client::get_telemetry_config( $api_url );

		// Maybe schedule telemetry cron if enabled
		if ( FC_Telemetry_Client::is_telemetry_enabled( $api_url ) ) {
			FC_Telemetry_Client::schedule_telemetry_cron( $api_url );
			return;
		}

		// Otherwise clear the site report cron
		wp_clear_scheduled_hook( $config['cron_hook'] );
	}

}

FluidCheckout_Settings_Tools::hooks();

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

		// Site report settings
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_site_report_settings' ), 10, 3 );
		add_action( 'woocommerce_settings_saved', array( $this, 'maybe_sync_site_report_cron_on_settings_saved' ), 10 );
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
					'title' => __( 'Site environment reports', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => __( 'Send non-sensitive site environment reports to help Fluid Checkout improve compatibility. Reports are not sent until enabled in the options below.', 'fluid-checkout' ),
					'id'    => 'fc_checkout_site_report_options',
				),

				array(
					'title'           => __( 'Site environment reports', 'fluid-checkout' ),
					'desc'            => __( 'Enable sending site environment reports to Fluid Checkout', 'fluid-checkout' ),
					'desc_tip'        => __( 'Reports are sent weekly when enabled and help us improve compatibility and support for your site.', 'fluid-checkout' ) . '<br>' .
									 __( 'No customer, user, or sensitive data are included in the reports.', 'fluid-checkout' ),
					'id'              => 'fc_enable_site_report',
					'type'            => 'fc_site_report_enable',
					'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_site_report' ),
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
					'autoload'        => false,
				),
				array(
					'title'             => __( 'Data to share', 'fluid-checkout' ),
					'desc'              => __( 'Choose which optional data groups to include in site reports.', 'fluid-checkout' ),
					'id'                => 'fc_site_report_data_groups',
					'type'              => 'fc_checkboxgroup',
					'options'           => array(
						'basic_environment'         => array(
							'label'       => __( 'Basic environment info', 'fluid-checkout' ),
							'description' => __( 'WordPress, PHP, WooCommerce, theme, and plugin list data. Always included when reporting is enabled.', 'fluid-checkout' ),
						),
						'woocommerce_sales_metrics' => array(
							'label'       => __( 'WooCommerce sales metrics', 'fluid-checkout' ),
							'description' => __( 'Order count and total sales for the last closed calendar month. No customer data is included.', 'fluid-checkout' ),
						),
						'plugin_settings'           => array(
							'label'       => __( 'Plugin settings', 'fluid-checkout' ),
							'description' => __( 'Fluid Checkout plugin settings to help with support requests. Coming soon.', 'fluid-checkout' ),
						),
					),
					'required_options'  => array( 'basic_environment' ),
					'disabled_options'  => array( 'basic_environment', 'plugin_settings' ),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_site_report_data_groups' ),
					'checkboxgroup'     => 'end',
					'show_if_checked'   => 'yes',
					'autoload'          => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_site_report_options',
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
	public function sanitize_site_report_settings( $value, $option, $raw_value ) {
		if ( empty( $option['id'] ) || 'fc_site_report_data_groups' !== $option['id'] ) {
			return $value;
		}

		// Preserve stored groups when reporting is disabled and the field is hidden.
		if ( 'no' === get_option( 'fc_enable_site_report', 'no' ) ) {
			return $this->normalize_site_report_data_groups( get_option( 'fc_site_report_data_groups', array( 'basic_environment' ) ) );
		}

		$groups = is_array( $raw_value ) ? $raw_value : array();

		return $this->normalize_site_report_data_groups( $groups );
	}



	/**
	 * Normalize selected site report data groups.
	 *
	 * @param mixed $groups Raw or sanitized group values.
	 */
	public function normalize_site_report_data_groups( $groups ) {
		$allowed = array( 'basic_environment', 'woocommerce_sales_metrics', 'plugin_settings' );

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

		$dependent_groups = array( 'woocommerce_sales_metrics', 'plugin_settings' );

		if ( array_intersect( $groups, $dependent_groups ) && ! in_array( 'basic_environment', $groups, true ) ) {
			$groups[] = 'basic_environment';
		}

		return array_values( array_unique( $groups ) );
	}



	/**
	 * Schedule or clear the site report cron when Tools settings are saved.
	 */
	public function maybe_sync_site_report_cron_on_settings_saved() {
		// Bail if not saving Fluid Checkout Tools settings
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) || 'fc_checkout' !== wp_unslash( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['section'] ) || 'tools' !== wp_unslash( $_GET['section'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		$this->load_license_manager_class();

		// Bail if license manager class is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) { return; }

		if ( FC_Licenses_Client::is_site_report_enabled() ) {
			FC_Licenses_Client::schedule_site_report_cron( FluidCheckout::$plugin_slug, FluidCheckout::SITE_REPORT_CRON_HOOK );
			return;
		}

		wp_clear_scheduled_hook( FluidCheckout::SITE_REPORT_CRON_HOOK );
	}



	/**
	 * Load the shared plugin license manager class.
	 */
	private function load_license_manager_class() {
		if ( class_exists( 'FC_Licenses_Client', false ) ) { return; }

		require_once FluidCheckout::$directory_path . 'inc/admin/fc-licenses-client.php';
	}

}

return new WC_Settings_FluidCheckout_Tools_Settings();

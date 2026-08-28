<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report telemetry opt-in prompts (admin notice + dashboard banner).
 */
class FluidCheckout_Admin_SiteReportTelemetry extends FluidCheckout {

	/**
	 * Admin notice name and dismiss option suffix.
	 */
	const NOTICE_NAME = 'site_report_telemetry';

	/**
	 * Nonce action for enabling telemetry from a prompt.
	 */
	const ENABLE_NONCE_ACTION = 'enable-site-report-telemetry';



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
		add_action( 'admin_init', array( $this, 'maybe_handle_enable_request' ), 10 );
	}



	/**
	 * Whether the telemetry opt-in prompt should be shown.
	 */
	public function should_show_site_report_telemetry_prompt() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return false; }

		// Bail if site reporting is already enabled
		if ( 'yes' === get_option( 'fc_enable_site_report', 'no' ) ) { return false; }

		// Bail if the prompt was dismissed
		if ( $this->is_dismissed() ) { return false; }

		$install_date = (int) get_option( 'fc_plugin_activation_time', 0 );

		// Bail if install date is missing
		if ( $install_date <= 0 ) { return false; }

		$past_date = strtotime( '-3 days' );

		// Bail if 3 days have not passed since installation
		if ( $past_date < $install_date ) { return false; }

		return true;
	}



	/**
	 * Check whether the telemetry prompt was dismissed.
	 */
	public function is_dismissed() {
		return (bool) get_option( 'fc_dismissed_notice_' . self::NOTICE_NAME, false );
	}



	/**
	 * Whether the current request is the Fluid Checkout Dashboard settings screen.
	 */
	public function is_fc_checkout_dashboard_screen() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
				return false;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) || 'wc-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) || 'fc_checkout' !== sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		return '' === $section;
	}



	/**
	 * Get the Fluid Checkout Tools settings URL.
	 */
	public function get_tools_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=fc_checkout&section=tools' );
	}



	/**
	 * Get the URL to enable site report telemetry.
	 */
	public function get_enable_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'fc_action' => 'enable_site_report_telemetry',
				),
				admin_url( 'admin.php' )
			),
			self::ENABLE_NONCE_ACTION
		);
	}



	/**
	 * Get the URL to dismiss the telemetry prompt.
	 */
	public function get_dismiss_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'fc_action' => 'dismiss_notice',
					'fc_notice' => self::NOTICE_NAME,
				),
				admin_url( 'admin.php' )
			),
			'dismiss-notice'
		);
	}



	/**
	 * Enable site report telemetry and schedule the weekly cron.
	 */
	public function enable_site_report_telemetry() {
		update_option( 'fc_enable_site_report', 'yes' );

		$this->load_license_manager_class();

		if ( class_exists( 'Fluidweb_PluginLicenseManager', false ) ) {
			Fluidweb_PluginLicenseManager::schedule_site_report_cron();
		}
	}



	/**
	 * Dismiss the telemetry opt-in prompt.
	 */
	public function dismiss_site_report_telemetry_prompt() {
		update_option( 'fc_dismissed_notice_' . self::NOTICE_NAME, 1 );
	}



	/**
	 * Maybe enable telemetry from an admin request.
	 */
	public function maybe_handle_enable_request() {
		// Bail if not an enable request
		if ( ! array_key_exists( 'fc_action', $_GET ) || 'enable_site_report_telemetry' !== sanitize_text_field( wp_unslash( $_GET['fc_action'] ) ) ) { return; }

		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::ENABLE_NONCE_ACTION ) ) { return; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		$this->enable_site_report_telemetry();
		$this->dismiss_site_report_telemetry_prompt();

		$redirect_url = wp_get_referer();

		if ( empty( $redirect_url ) ) {
			$redirect_url = admin_url( 'admin.php?page=wc-settings&tab=fc_checkout' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}



	/**
	 * Load the shared plugin license manager class.
	 */
	private function load_license_manager_class() {
		if ( class_exists( 'Fluidweb_PluginLicenseManager', false ) ) { return; }

		require_once FluidCheckout::$directory_path . 'vendor/fluidweb/fluidweb-updater/plugin-license-manager.php';
	}

}

FluidCheckout_Admin_SiteReportTelemetry::instance();

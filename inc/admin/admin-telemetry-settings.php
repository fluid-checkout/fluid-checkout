<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report telemetry opt-in prompts (admin notice + dashboard banner).
 */
class FluidCheckout_Admin_TelemetrySettings extends FluidCheckout {

	/**
	 * Admin notice name and dismiss option suffix.
	 */
	const NOTICE_NAME = 'telemetry';

	/**
	 * Nonce action for enabling telemetry from a prompt.
	 */
	const ENABLE_NONCE_ACTION = 'enable-telemetry';



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
	public function should_show_telemetry_prompt() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return false; }

		// Bail if site reporting is already enabled
		if ( 'yes' === get_option( 'fc_telemetry_enabled', 'no' ) ) { return false; }

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
		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return false; }

		return FluidCheckout_Admin_Settings_Page::instance()->is_settings_page( 'dashboard' );
	}



	/**
	 * Get the Fluid Checkout Tools settings URL.
	 */
	public function get_tools_settings_url() {
		return admin_url( 'admin.php?page=fluid-checkout&tab=tools' );
	}



	/**
	 * Get the URL to enable site report telemetry.
	 */
	public function get_enable_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'fc_action' => 'enable_telemetry',
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
	public function enable_telemetry() {
		// Bail if telemetry client class is not available
		if ( ! class_exists( 'FC_Telemetry_Client' ) ) { return; }

		// Enable telemetry
		update_option( 'fc_telemetry_enabled', 'yes' );

		// Schedule the site report cron
		FC_Telemetry_Client::schedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
	}



	/**
	 * Dismiss the telemetry opt-in prompt.
	 */
	public function dismiss_telemetry_prompt() {
		update_option( 'fc_dismissed_notice_' . self::NOTICE_NAME, 1 );
	}



	/**
	 * Maybe enable telemetry from an admin request.
	 */
	public function maybe_handle_enable_request() {
		// Bail if not an enable request
		if ( ! array_key_exists( 'fc_action', $_GET ) || 'enable_telemetry' !== sanitize_text_field( wp_unslash( $_GET['fc_action'] ) ) ) { return; }

		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::ENABLE_NONCE_ACTION ) ) { return; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		$this->enable_telemetry();
		$this->dismiss_telemetry_prompt();

		$redirect_url = wp_get_referer();

		if ( empty( $redirect_url ) ) {
			$redirect_url = admin_url( 'admin.php?page=fluid-checkout&tab=dashboard' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

}

FluidCheckout_Admin_TelemetrySettings::instance();

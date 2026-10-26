<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: site report telemetry opt-in and enable/dismiss handling.
 */
class FluidCheckout_AdminNotices_Telemetry extends FluidCheckout {

	/**
	 * Admin notice name and dismiss option suffix.
	 */
	const NOTICE_NAME = 'telemetry';

	/**
	 * Nonce action for enabling telemetry from a prompt.
	 */
	const ENABLE_NONCE_ACTION = 'enable-telemetry';

	/**
	 * Time after updating to Fluid Checkout 5.0 before showing the opt-in notice (`strtotime`-relative, e.g. `2 weeks`).
	 */
	const NOTICE_DELAY = '2 weeks';



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
		add_filter( 'fc_admin_notices', array( $this, 'add_admin_notice' ), 10 );
	}



	/**
	 * Whether the telemetry opt-in prompt should be shown.
	 */
	public function should_show_telemetry_prompt() {
		// Bail if usage tracking is turned off in PHP constant, whether or not the notice was dismissed
		if ( FC_Telemetry_Client::is_disabled_by_constant() ) { return false; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return false; }

		// Bail if telemetry is already enabled
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_enabled', 'no' ) ) { return false; }

		// Bail if the prompt was dismissed
		if ( $this->is_dismissed() ) { return false; }

		// Bail if settings class is not available
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) { return false; }

		$five_oh_time = absint( FluidCheckout_Settings::instance()->get_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, 0 ) );

		// Bail if the store has not reached Fluid Checkout 5.0 settings yet
		if ( $five_oh_time < 1 ) { return false; }

		// Bail if the notice delay has not passed since the Lite or PRO 5.0 update
		if ( time() < strtotime( '+' . self::NOTICE_DELAY, $five_oh_time ) ) { return false; }

		return true;
	}



	/**
	 * Check whether the telemetry prompt was dismissed.
	 */
	public function is_dismissed() {
		return (bool) get_option( 'fc_dismissed_notice_' . self::NOTICE_NAME, false );
	}



	/**
	 * Whether the current request is any Fluid Checkout settings screen.
	 */
	public function is_fc_settings_screen() {
		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return false; }

		return FluidCheckout_Admin_Settings_Page::instance()->is_settings_page();
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
	 * Register the telemetry opt-in admin notice.
	 *
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_admin_notice( $notices = array() ) {
		// Bail if the telemetry prompt should not be shown
		if ( ! $this->should_show_telemetry_prompt() ) { return $notices; }

		// Bail on Fluid Checkout settings screens (usage tracking is available under Tools / Getting started)
		if ( $this->is_fc_settings_screen() ) { return $notices; }

		$description  = '<p>' . esc_html( __( 'Usage tracking is optional and OFF by default. It helps us improve compatibility and support. You can preview the exact data before it is sent, and turn it off at any time.', 'fluid-checkout' ) ) . '</p>';
		$description .= '<p>' . esc_html( __( 'When you turn it on, Fluid Checkout receives your site domain and basic environment details: WordPress, PHP and WooCommerce versions, language, store country, currency and timezone, your active theme, and your installed plugins and their versions. You can also choose to include monthly order counts and sales totals. No customer or user data is ever sent.', 'fluid-checkout' ) ) . '</p>';

		$notices[] = array(
			'name'            => self::NOTICE_NAME,
			'title'           => __( 'Usage tracking', 'fluid-checkout' ),
			'description'     => $description,
			'paragraph_wrap'  => false,
			'dismiss_label'   => __( 'Keep it OFF', 'fluid-checkout' ),
			'actions'         => array(
				sprintf(
					'<a href="%s" class="button button-secondary">%s</a>',
					esc_url( $this->get_tools_settings_url() ),
					esc_html__( 'Review usage tracking', 'fluid-checkout' )
				),
				sprintf(
					'<a href="%s" class="button button-secondary">%s</a>',
					esc_url( $this->get_enable_url() ),
					esc_html__( 'Turn ON usage tracking', 'fluid-checkout' )
				),
			),
		);

		return $notices;
	}



	/**
	 * Enable site report telemetry and schedule the weekly cron.
	 */
	public function enable_telemetry() {
		// Bail without changing the saved choice when usage tracking is turned off in wp-config.php
		if ( FC_Telemetry_Client::is_disabled_by_constant() ) { return; }

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
		// Bail if usage tracking is turned off in wp-config.php
		if ( FC_Telemetry_Client::is_disabled_by_constant() ) { return; }

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

FluidCheckout_AdminNotices_Telemetry::instance();

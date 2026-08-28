<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: ask permission to send site report telemetry.
 */
class FluidCheckout_AdminNotices_SiteReportTelemetry extends FluidCheckout {

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
		add_action( 'fc_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Add notice.
	 *
	 * @param array $notices Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		$telemetry = FluidCheckout_Admin_SiteReportTelemetry::instance();

		// Bail if the telemetry prompt should not be shown
		if ( ! $telemetry->should_show_site_report_telemetry_prompt() ) { return $notices; }

		// Bail on the Dashboard settings screen where the inline prompt is shown instead
		if ( $telemetry->is_fc_checkout_dashboard_screen() ) { return $notices; }

		$notices[] = array(
			'name'        => FluidCheckout_Admin_SiteReportTelemetry::NOTICE_NAME,
			'title'       => __( 'Help us improve Fluid Checkout', 'fluid-checkout' ),
			'description' => __( 'Share anonymous site environment reports with Fluid Checkout to help us improve compatibility and measure impact. No customer, user, or sensitive data are included. You can choose what is shared anytime from the Tools settings.', 'fluid-checkout' ),
			'actions'     => array(
				sprintf(
					'<a href="%s" class="button button-primary">%s</a>',
					esc_url( $telemetry->get_enable_url() ),
					esc_html__( 'Enable site environment reports', 'fluid-checkout' )
				),
				sprintf(
					'<a href="%s" class="button button-secondary">%s</a>',
					esc_url( $telemetry->get_tools_settings_url() ),
					esc_html__( 'Review settings', 'fluid-checkout' )
				),
			),
		);

		return $notices;
	}

}

FluidCheckout_AdminNotices_SiteReportTelemetry::instance();

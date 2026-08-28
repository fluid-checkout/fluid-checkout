<?php
defined( 'ABSPATH' ) || exit;

/**
 * Dashboard telemetry opt-in prompt field.
 */
class FluidCheckout_Admin_SettingType_SiteReportTelemetryPrompt extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_site_report_telemetry_prompt', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param array $value Admin settings args values.
	 */
	public function output_field( $value ) {
		$telemetry = FluidCheckout_Admin_SiteReportTelemetry::instance();

		// Bail if the telemetry prompt should not be shown
		if ( ! $telemetry->should_show_site_report_telemetry_prompt() ) { return; }
		?>

		<tr valign="top" class="fc-dashboard-section__row fc-dashboard-section--telemetry-prompt">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<div class="fc-dashboard-telemetry-prompt">
					<h3 class="fc-dashboard-section-title"><?php esc_html_e( 'Help us improve Fluid Checkout', 'fluid-checkout' ); ?></h3>
					<p class="fc-dashboard-section__subtitle"><?php esc_html_e( 'Share anonymous site environment reports to help us improve compatibility and measure impact.', 'fluid-checkout' ); ?></p>
					<p><?php esc_html_e( 'No customer, user, or sensitive data are included. You can choose what is shared anytime from the Tools settings.', 'fluid-checkout' ); ?></p>
					<p class="fc-dashboard-telemetry-prompt__actions">
						<a href="<?php echo esc_url( $telemetry->get_enable_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Enable site environment reports', 'fluid-checkout' ); ?></a>
						<a href="<?php echo esc_url( $telemetry->get_tools_settings_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Review settings', 'fluid-checkout' ); ?></a>
						<a href="<?php echo esc_url( $telemetry->get_dismiss_url() ); ?>" class="fc-dashboard-telemetry-prompt__dismiss"><?php esc_html_e( 'Don\'t show this again', 'fluid-checkout' ); ?></a>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_SiteReportTelemetryPrompt::instance();

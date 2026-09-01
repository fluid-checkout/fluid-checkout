<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report admin preview and send-now actions.
 */
class FluidCheckout_Admin_SiteReport extends FluidCheckout {

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
		add_action( 'wp_ajax_fc_site_report_preview', array( $this, 'ajax_preview_site_report' ) );
		add_action( 'wp_ajax_fc_site_report_send_now', array( $this, 'ajax_send_site_report_now' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_styles' ), 10 );
		add_action( 'admin_footer', array( $this, 'output_modal_markup' ), 10 );
	}



	/**
	 * Register scripts and styles for the site report tools UI.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function register_scripts_styles( $hook ) {
		if ( ! $this->is_tools_settings_screen( $hook ) ) { return; }

		wp_register_script(
			'fc-admin-site-report',
			FluidCheckout_Enqueue::instance()->get_script_url( '/js/admin/admin-site-report' ),
			array( 'jquery' ),
			null,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_script( 'fc-admin-site-report' );

		wp_localize_script(
			'fc-admin-site-report',
			'fcAdminSiteReportSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'fc_site_report_admin' ),
				'i18n'    => array(
					'modalTitle'           => __( 'Site report preview', 'fluid-checkout' ),
					'modalDescription'     => __( 'This is the data that would be included in the next site environment report based on your current settings.', 'fluid-checkout' ),
					'loading'              => __( 'Loading report preview...', 'fluid-checkout' ),
					'loadError'            => __( 'Could not load the site report preview. Try again.', 'fluid-checkout' ),
					'sendError'            => __( 'Could not send the site report. Try again.', 'fluid-checkout' ),
					'sendSuccess'          => __( 'Site report sent successfully.', 'fluid-checkout' ),
					'sendNow'              => __( 'Send now', 'fluid-checkout' ),
					'enableAndSendNow'     => __( 'Enable and send now', 'fluid-checkout' ),
					'close'                => __( 'Close', 'fluid-checkout' ),
					'inProgress'           => __( 'A site report request is already in progress. Try again in a moment.', 'fluid-checkout' ),
					'disabled'             => __( 'Site environment reporting is disabled.', 'fluid-checkout' ),
					'emptyPayload'         => __( 'No site report data is available to send.', 'fluid-checkout' ),
					'rateLimited'          => __( 'A site report was sent recently. Try again later.', 'fluid-checkout' ),
					'requestFailed'        => __( 'The site report could not be sent. Try again later.', 'fluid-checkout' ),
				),
			)
		);
	}



	/**
	 * Output modal markup for the site report preview.
	 */
	public function output_modal_markup() {
		if ( ! $this->is_tools_settings_screen() ) { return; }
		?>
		<div id="fc-site-report-modal" class="fc-site-report-modal" aria-hidden="true">
			<div class="fc-site-report-modal__backdrop" data-fc-site-report-close></div>
			<div class="fc-site-report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fc-site-report-modal-title">
				<div class="fc-site-report-modal__header">
					<h2 id="fc-site-report-modal-title"><?php esc_html_e( 'Site report preview', 'fluid-checkout' ); ?></h2>
					<button type="button" class="fc-site-report-modal__close" data-fc-site-report-close aria-label="<?php esc_attr_e( 'Close', 'fluid-checkout' ); ?>">&times;</button>
				</div>
				<div class="fc-site-report-modal__body">
					<p class="description"><?php esc_html_e( 'This is the data that would be included in the next site environment report based on your current settings.', 'fluid-checkout' ); ?></p>
					<pre class="fc-site-report-modal__payload" aria-live="polite"></pre>
					<p class="fc-site-report-modal__feedback is-hidden"></p>
				</div>
				<div class="fc-site-report-modal__footer">
					<button type="button" class="button" data-fc-site-report-close><?php esc_html_e( 'Close', 'fluid-checkout' ); ?></button>
					<button type="button" class="button button-primary fc-site-report-modal__send-button is-hidden"><?php esc_html_e( 'Send now', 'fluid-checkout' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * AJAX handler for site report preview.
	 */
	public function ajax_preview_site_report() {
		$this->verify_ajax_request();

		$groups = $this->get_request_data_groups();

		// Bail if license manager class is not available or does not support site report preview
		if ( ! class_exists( 'FC_Licenses_Client' ) || ! method_exists( 'FC_Licenses_Client', 'build_site_report_payload' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Site environment reporting is not available.', 'fluid-checkout' ),
				),
				500
			);
		}

		$payload = FC_Licenses_Client::build_site_report_payload( $groups );

		if ( empty( $payload ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No site report data is available for the selected settings.', 'fluid-checkout' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'payload'      => $payload,
				'payload_json' => wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
				'is_enabled'   => 'yes' === $this->get_request_enable_value(),
			)
		);
	}



	/**
	 * AJAX handler for sending a site report immediately.
	 */
	public function ajax_send_site_report_now() {
		$this->verify_ajax_request();

		$groups             = $this->get_request_data_groups();
		$enable_if_disabled = 'yes' !== $this->get_request_enable_value();

		// Bail if license client does not support sending site reports
		if ( ! class_exists( 'FC_Licenses_Client' ) || ! method_exists( 'FC_Licenses_Client', 'send_site_report_now' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Site environment reporting is not available.', 'fluid-checkout' ),
				),
				500
			);
		}

		$result = FC_Licenses_Client::send_site_report_now( $groups, $enable_if_disabled, false, self::$plugin_slug, self::SITE_REPORT_API_URL, self::SITE_REPORT_CRON_HOOK );

		if ( empty( $result['success'] ) ) {
			$error_code = $result['error_code'] ?? 'request_failed';

			if ( 'request_failed' === $error_code && 429 === (int) ( $result['response_code'] ?? 0 ) ) {
				$error_code = 'rate_limited';
			}

			wp_send_json_error(
				array(
					'message'       => $this->get_send_error_message( $result ),
					'error_code'    => $error_code,
					'response_code' => (int) ( $result['response_code'] ?? 0 ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Site report sent successfully.', 'fluid-checkout' ),
				'is_enabled' => ! empty( $result['is_enabled'] ),
			)
		);
	}



	/**
	 * Verify AJAX permissions and nonce.
	 */
	private function verify_ajax_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to manage site reports.', 'fluid-checkout' ),
				),
				403
			);
		}

		check_ajax_referer( 'fc_site_report_admin', 'nonce' );
	}



	/**
	 * Get the enable checkbox value from the AJAX request.
	 */
	private function get_request_enable_value() {
		$enabled = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : 'no';

		return 'yes' === $enabled ? 'yes' : 'no';
	}



	/**
	 * Get normalized data groups from the AJAX request.
	 */
	private function get_request_data_groups() {
		$groups = array( 'basic_environment' );

		if ( empty( $_POST['data_groups'] ) || ! is_array( $_POST['data_groups'] ) ) {
			return $groups;
		}

		$groups = array_map( 'sanitize_key', wp_unslash( $_POST['data_groups'] ) );

		if ( ! class_exists( 'FC_Licenses_Client' ) || ! method_exists( 'FC_Licenses_Client', 'normalize_site_report_data_groups' ) ) {
			return $groups;
		}

		return FC_Licenses_Client::normalize_site_report_data_groups( $groups );
	}



	/**
	 * Map PLM send error codes to user-facing messages.
	 *
	 * @param array $result Send result from PLM.
	 */
	private function get_send_error_message( $result ) {
		$messages = array(
			'in_progress'    => __( 'A site report request is already in progress. Try again in a moment.', 'fluid-checkout' ),
			'disabled'       => __( 'Site environment reporting is disabled.', 'fluid-checkout' ),
			'empty_payload'  => __( 'No site report data is available to send.', 'fluid-checkout' ),
			'rate_limited'   => __( 'A site report was sent recently. Try again later.', 'fluid-checkout' ),
			'request_failed' => __( 'The site report could not be sent. Try again later.', 'fluid-checkout' ),
		);

		$error_code = $result['error_code'] ?? 'request_failed';

		if ( 'request_failed' === $error_code && 429 === (int) ( $result['response_code'] ?? 0 ) ) {
			$error_code = 'rate_limited';
		}

		return $messages[ $error_code ] ?? $messages['request_failed'];
	}



	/**
	 * Whether the current request is the Fluid Checkout Tools settings screen.
	 *
	 * @param string $hook_suffix Optional admin page hook suffix.
	 */
	private function is_tools_settings_screen( $hook_suffix = '' ) {
		if ( ! empty( $hook_suffix ) && 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return false;
		}

		if ( empty( $hook_suffix ) && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
				return false;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) || 'fc_checkout' !== sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['section'] ) || 'tools' !== sanitize_text_field( wp_unslash( $_GET['section'] ) ) ) {
			return false;
		}

		return true;
	}

}

FluidCheckout_Admin_SiteReport::instance();

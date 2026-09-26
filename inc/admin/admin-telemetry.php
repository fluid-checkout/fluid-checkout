<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report admin preview and send-now actions.
 */
class FluidCheckout_Admin_Telemetry extends FluidCheckout {

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
		add_action( 'wp_ajax_fc_telemetry_preview', array( $this, 'ajax_preview_telemetry' ) );
		add_action( 'wp_ajax_fc_telemetry_send_now', array( $this, 'ajax_send_telemetry_now' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_styles' ), 10 );
		add_action( 'admin_footer', array( $this, 'output_modal_markup' ), 10 );
	}



	/**
	 * Register scripts and styles for the site report tools UI.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function register_scripts_styles( $hook ) {
		if ( ! $this->is_settings_screen( $hook ) ) { return; }

		// Scripts
		wp_register_script( 'fc-admin-telemetry', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-telemetry' ), array( 'jquery', 'fc-utils' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-telemetry', 'window.addEventListener("load",function(){FCAdminTelemetry.init(fcAdminTelemetrySettings);});' );
		wp_localize_script(
			'fc-admin-telemetry',
			'fcAdminTelemetrySettings',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'fc_telemetry_admin' ),
				'isEnabledSaved'  => 'yes' === get_option( 'fc_telemetry_enabled', 'no' ),
				'i18n'            => array(
					'modalTitle'       => __( 'Site report preview', 'fluid-checkout' ),
					'modalDescription' => __( 'This is the data that would be included in the next site environment report based on your current settings.', 'fluid-checkout' ),
					'loading'          => __( 'Loading report preview...', 'fluid-checkout' ),
					'loadError'        => __( 'Could not load the site report preview. Try again.', 'fluid-checkout' ),
					'sendError'        => __( 'Could not send the site report. Try again.', 'fluid-checkout' ),
					'sendSuccess'      => __( 'Site report sent successfully.', 'fluid-checkout' ),
					'sendNow'          => __( 'Send now', 'fluid-checkout' ),
					'enableAndSendNow' => __( 'Enable and send now', 'fluid-checkout' ),
					'close'            => __( 'Close', 'fluid-checkout' ),
					'inProgress'       => __( 'A site report request is already in progress. Try again in a moment.', 'fluid-checkout' ),
					'disabled'         => __( 'Site environment reporting is disabled.', 'fluid-checkout' ),
					'emptyPayload'     => __( 'No site report data is available to send.', 'fluid-checkout' ),
					'ineligibleDomain' => __( 'This site domain cannot send environment reports (local or development domains are excluded).', 'fluid-checkout' ),
					'rateLimited'      => __( 'A site report was sent recently. Try again later.', 'fluid-checkout' ),
					'requestFailed'    => __( 'The site report could not be sent. Try again later.', 'fluid-checkout' ),
				),
			)
		);

		wp_enqueue_script( 'fc-admin-telemetry' );
	}



	/**
	 * Output modal markup for the site report preview.
	 */
	public function output_modal_markup() {
		if ( ! $this->is_settings_screen() ) { return; }
		?>
		<div id="fc-telemetry-modal" class="fc-telemetry-modal" aria-hidden="true">
			<div class="fc-telemetry-modal__backdrop" data-fc-telemetry-close></div>
			<div class="fc-telemetry-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fc-telemetry-modal-title">
				<div class="fc-telemetry-modal__header">
					<h2 id="fc-telemetry-modal-title"><?php esc_html_e( 'Site report preview', 'fluid-checkout' ); ?></h2>
					<button type="button" class="fc-telemetry-modal__close" data-fc-telemetry-close aria-label="<?php esc_attr_e( 'Close', 'fluid-checkout' ); ?>">&times;</button>
				</div>
				<div class="fc-telemetry-modal__body">
					<p class="description"><?php esc_html_e( 'This is the data that would be included in the next site environment report based on your current settings.', 'fluid-checkout' ); ?></p>
					<pre class="fc-telemetry-modal__payload" aria-live="polite"></pre>
					<p class="fc-telemetry-modal__feedback is-hidden"></p>
				</div>
				<div class="fc-telemetry-modal__footer">
					<button type="button" class="button" data-fc-telemetry-close><?php esc_html_e( 'Close', 'fluid-checkout' ); ?></button>
					<button type="button" class="button button-primary fc-telemetry-modal__send-button is-hidden"><?php esc_html_e( 'Send now', 'fluid-checkout' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * AJAX handler for site report preview.
	 */
	public function ajax_preview_telemetry() {
		$this->verify_ajax_request();

		$groups = $this->get_request_data_groups();

		// Bail if telemetry client class is not available or does not support site report preview
		if ( ! class_exists( 'FC_Telemetry_Client' ) || ! method_exists( 'FC_Telemetry_Client', 'build_telemetry_payload' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Site environment reporting is not available.', 'fluid-checkout' ),
				),
				500
			);
		}

		// Build payload including local/dev domains so the JSON preview is always inspectable.
		$payload = FC_Telemetry_Client::build_telemetry_payload( $groups, null, self::get_telemetry_api_url() );

		if ( empty( $payload ) ) {
			wp_send_json_error(
				array(
					'message'    => __( 'No site report data is available for the selected settings.', 'fluid-checkout' ),
					'error_code' => 'empty_payload',
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
	public function ajax_send_telemetry_now() {
		$this->verify_ajax_request();

		$groups = $this->get_request_data_groups();

		// Admin send-now always enables reporting when currently disabled, then sends.
		// Covers "Enable and send now" and an unsaved checked enable checkbox.
		$enable_if_disabled = true;

		// Bail if telemetry client class is not available or does not support sending site reports
		if ( ! class_exists( 'FC_Telemetry_Client' ) || ! method_exists( 'FC_Telemetry_Client', 'send_telemetry_now' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Site environment reporting is not available.', 'fluid-checkout' ),
				),
				500
			);
		}

		$result = FC_Telemetry_Client::send_telemetry_now( $groups, $enable_if_disabled, false, self::get_telemetry_api_url() );

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
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to manage site reports.', 'fluid-checkout' ),
				),
				403
			);
		}

		check_ajax_referer( 'fc_telemetry_admin', 'nonce' );
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

		if ( ! class_exists( 'FC_Telemetry_Client' ) || ! method_exists( 'FC_Telemetry_Client', 'normalize_telemetry_data_groups' ) ) {
			return $groups;
		}

		return FC_Telemetry_Client::normalize_telemetry_data_groups( $groups );
	}



	/**
	 * Map PLM send error codes to user-facing messages.
	 *
	 * @param array $result Send result from PLM.
	 */
	private function get_send_error_message( $result ) {
		$messages = array(
			'in_progress'        => __( 'A site report request is already in progress. Try again in a moment.', 'fluid-checkout' ),
			'disabled'           => __( 'Site environment reporting is disabled.', 'fluid-checkout' ),
			'empty_payload'      => __( 'No site report data is available to send.', 'fluid-checkout' ),
			'ineligible_domain'  => __( 'This site domain cannot send environment reports (local or development domains are excluded).', 'fluid-checkout' ),
			'rate_limited'       => __( 'A site report was sent recently. Try again later.', 'fluid-checkout' ),
			'request_failed'     => __( 'The site report could not be sent. Try again later.', 'fluid-checkout' ),
		);

		$error_code = $result['error_code'] ?? 'request_failed';

		if ( 'request_failed' === $error_code && 429 === (int) ( $result['response_code'] ?? 0 ) ) {
			$error_code = 'rate_limited';
		}

		return $messages[ $error_code ] ?? $messages['request_failed'];
	}



	/**
	 * Whether the current request is a Fluid Checkout settings screen.
	 * Scripts are loaded on every settings tab because the admin UI behaves like an SPA.
	 *
	 * @param string $hook_suffix Optional admin page hook suffix.
	 */
	private function is_settings_screen( $hook_suffix = '' ) {
		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return false; }

		return FluidCheckout_Admin_Settings_Page::instance()->is_settings_page();
	}

}

FluidCheckout_Admin_Telemetry::instance();

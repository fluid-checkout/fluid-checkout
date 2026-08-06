<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handle admin actions for settings export, import, and reset.
 */
class FluidCheckout_Admin_Settings_Tools_Actions extends FluidCheckout {

	/**
	 * Transient key for admin notices after redirect.
	 *
	 * @var string
	 */
	const NOTICE_TRANSIENT = 'fc_settings_tools_notice';



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
		// Admin actions
		add_action( 'admin_post_fc_settings_export', array( $this, 'handle_export' ), 10 );
		add_action( 'admin_post_fc_settings_import', array( $this, 'handle_import_preview' ), 10 );
		add_action( 'admin_post_fc_settings_import_apply', array( $this, 'handle_import_apply' ), 10 );
		add_action( 'admin_post_fc_settings_import_cancel', array( $this, 'handle_import_cancel' ), 10 );
		add_action( 'admin_post_fc_settings_reset', array( $this, 'handle_reset' ), 10 );
		add_action( 'admin_post_fc_settings_restore', array( $this, 'handle_restore' ), 10 );

		// Admin notices
		add_action( 'admin_notices', array( $this, 'display_action_notices' ), 10 );
	}



	/**
	 * Whether the current user can manage settings tools.
	 */
	public function current_user_can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get the Tools settings section URL.
	 *
	 * @param  array  $args  Optional query args to merge.
	 */
	public function get_tools_settings_url( $args = array() ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=fc_checkout&section=tools' );

		// Bail if no extra args
		if ( empty( $args ) ) {
			return $url;
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Verify nonce and capability for a settings tools request.
	 *
	 * @param  string  $nonce_action  Nonce action name.
	 */
	public function verify_request( $nonce_action ) {
		// Bail if nonce is invalid
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), $nonce_action ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fluid-checkout' ) );
		}

		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'fluid-checkout' ) );
		}
	}



	/**
	 * Handle settings export download.
	 */
	public function handle_export() {
		$this->verify_request( 'fc_settings_export' );

		$json = FluidCheckout_Admin_Settings_Tools_Service::instance()->get_export_json();

		// Bail if export failed
		if ( false === $json ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Could not generate the settings export file.', 'fluid-checkout' ),
			) );
		}

		$filename = 'fluid-checkout-settings-' . gmdate( 'Y-m-d-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body.
		echo $json;
		exit;
	}



	/**
	 * Handle settings import upload and store a preview for review.
	 */
	public function handle_import_preview() {
		$this->verify_request( 'fc_settings_import' );

		$service = FluidCheckout_Admin_Settings_Tools_Service::instance();
		$mode = $service->normalize_import_mode(
			isset( $_POST[ 'fc_settings_import_mode' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'fc_settings_import_mode' ] ) ) : 'update'
		);
		$upload = $this->get_validated_import_upload();
		$data = $service->decode_import_json( $upload[ 'json' ] );

		// Bail if JSON or payload is invalid
		if ( is_wp_error( $data ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => $data->get_error_message(),
			) );
		}

		$diff = $service->get_import_diff( $data, $mode );

		// Bail if validation errors
		if ( ! empty( $diff[ 'errors' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => implode( ' ', $diff[ 'errors' ] ),
			) );
		}

		$service->set_import_preview( array(
			'json'     => $upload[ 'json' ],
			'mode'     => $mode,
			'diff'     => $diff,
			'filename' => $upload[ 'filename' ],
		) );

		wp_safe_redirect( $this->get_tools_settings_url() );
		exit;
	}

	/**
	 * Validate the uploaded settings file and return its contents.
	 *
	 * Redirects with an error notice when validation fails.
	 *
	 * @return array{ json: string, filename: string }
	 */
	public function get_validated_import_upload() {
		// Bail if no file uploaded
		if ( empty( $_FILES[ 'fc_settings_import_file' ][ 'tmp_name' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Please choose a settings file to import.', 'fluid-checkout' ),
			) );
		}

		$file = $_FILES[ 'fc_settings_import_file' ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File validated below.

		// Bail if upload error
		if ( ! empty( $file[ 'error' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'There was an error uploading the settings file.', 'fluid-checkout' ),
			) );
		}

		// Bail if not a JSON file by extension
		$filename = isset( $file[ 'name' ] ) ? sanitize_file_name( wp_unslash( $file[ 'name' ] ) ) : '';
		if ( ! preg_match( '/\.json$/i', $filename ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Please upload a valid JSON settings file.', 'fluid-checkout' ),
			) );
		}

		$tmp_name = $file[ 'tmp_name' ];
		$max_bytes = FluidCheckout_Admin_Settings_Tools_Service::IMPORT_FILE_MAX_BYTES;

		// Bail if temporary file is not readable
		if ( ! is_uploaded_file( $tmp_name ) || ! is_readable( $tmp_name ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Could not read the uploaded settings file.', 'fluid-checkout' ),
			) );
		}

		// Bail if file exceeds the allowed size
		$file_size = isset( $file[ 'size' ] ) ? (int) $file[ 'size' ] : 0;
		if ( $file_size <= 0 ) {
			$file_size = (int) filesize( $tmp_name );
		}
		if ( $file_size > $max_bytes ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => sprintf(
					/* translators: %s: maximum file size (for example 1 MB) */
					__( 'The settings file is too large. Maximum size is %s.', 'fluid-checkout' ),
					size_format( $max_bytes )
				),
			) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading validated uploaded temp file.
		$json = file_get_contents( $tmp_name );

		// Bail if file contents could not be read
		if ( false === $json ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Could not read the uploaded settings file.', 'fluid-checkout' ),
			) );
		}

		return array(
			'json'     => $json,
			'filename' => $filename,
		);
	}

	/**
	 * Apply a previously reviewed import preview.
	 */
	public function handle_import_apply() {
		$this->verify_request( 'fc_settings_import_apply' );

		$service = FluidCheckout_Admin_Settings_Tools_Service::instance();
		$preview = $service->get_import_preview();

		// Bail if preview is missing
		if ( null === $preview ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'The import preview expired or is no longer available. Please upload the settings file again.', 'fluid-checkout' ),
			) );
		}

		$result = $service->import_settings_from_json( $preview[ 'json' ], true, $preview[ 'mode' ] );
		$service->clear_import_preview();

		// Maybe show parse / validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => implode( ' ', $result[ 'errors' ] ),
			) );
		}

		if ( 'replace' === $result[ 'mode' ] ) {
			$message = __( 'Settings replaced from the reviewed file.', 'fluid-checkout' );
		}
		else {
			$message = __( 'Settings updated from the reviewed file.', 'fluid-checkout' );
		}

		$message .= ' ' . __( 'Re-select the logo or linked pages if needed.', 'fluid-checkout' );

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => $message,
		) );
	}

	/**
	 * Cancel a pending import preview.
	 */
	public function handle_import_cancel() {
		$this->verify_request( 'fc_settings_import_cancel' );

		FluidCheckout_Admin_Settings_Tools_Service::instance()->clear_import_preview();

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => __( 'Import cancelled. No settings were changed.', 'fluid-checkout' ),
		) );
	}



	/**
	 * Handle settings reset.
	 */
	public function handle_reset() {
		$this->verify_request( 'fc_settings_reset' );

		FluidCheckout_Admin_Settings_Tools_Service::instance()->reset_settings( true );

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => __( 'Settings reset to defaults.', 'fluid-checkout' ),
		) );
	}



	/**
	 * Handle restore from the last automatic backup.
	 */
	public function handle_restore() {
		$this->verify_request( 'fc_settings_restore' );

		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->restore_last_backup();

		// Maybe show errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => implode( ' ', $result[ 'errors' ] ),
			) );
		}

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => __( 'Previous settings restored.', 'fluid-checkout' ),
		) );
	}



	/**
	 * Store a notice and redirect back to the Tools settings section.
	 *
	 * @param  array  $notice  Notice data with `type` and `message`.
	 */
	public function redirect_with_notice( $notice ) {
		set_transient( self::NOTICE_TRANSIENT . '_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS * 5 );
		wp_safe_redirect( $this->get_tools_settings_url() );
		exit;
	}

	/**
	 * Display notices after settings tools actions.
	 */
	public function display_action_notices() {
		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) { return; }

		$notice = get_transient( self::NOTICE_TRANSIENT . '_' . get_current_user_id() );

		// Bail if no notice
		if ( ! is_array( $notice ) || empty( $notice[ 'message' ] ) ) { return; }

		delete_transient( self::NOTICE_TRANSIENT . '_' . get_current_user_id() );

		$class = ( isset( $notice[ 'type' ] ) && 'error' === $notice[ 'type' ] ) ? 'notice notice-error' : 'notice notice-success';
		?>
		<div class="<?php echo esc_attr( $class ); ?> is-dismissible">
			<p><?php echo esc_html( $notice[ 'message' ] ); ?></p>
		</div>
		<?php
	}

}

FluidCheckout_Admin_Settings_Tools_Actions::instance();

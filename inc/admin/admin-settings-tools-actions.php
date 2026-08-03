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

		// Bail if temporary file is not readable
		if ( ! is_uploaded_file( $tmp_name ) || ! is_readable( $tmp_name ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Could not read the uploaded settings file.', 'fluid-checkout' ),
			) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading validated uploaded temp file.
		$json = file_get_contents( $tmp_name );
		$data = json_decode( $json, true );

		// Bail if JSON is invalid
		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Could not parse the settings file. Make sure it is valid JSON.', 'fluid-checkout' ),
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
			'json'     => $json,
			'mode'     => $mode,
			'diff'     => $diff,
			'filename' => $filename,
		) );

		wp_safe_redirect( $this->get_tools_settings_url( array( 'fc_settings_import_preview' => '1' ) ) );
		exit;
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
			$message = sprintf(
				/* translators: 1: number of settings cleared, 2: number of settings imported, 3: number of settings skipped */
				__( 'Settings replaced successfully. Cleared: %1$d. Imported: %2$d. Skipped: %3$d. An automatic backup was created and can be restored from this page. License keys and API keys were not changed. Re-select logo images and linked pages if they do not appear correctly.', 'fluid-checkout' ),
				(int) $result[ 'reset' ],
				(int) $result[ 'imported' ],
				(int) $result[ 'skipped' ]
			);
		} else {
			$message = sprintf(
				/* translators: 1: number of settings imported, 2: number of settings skipped */
				__( 'Settings updated successfully. Updated: %1$d. Skipped: %2$d. An automatic backup was created and can be restored from this page. License keys and API keys were not changed. Re-select logo images and linked pages if they do not appear correctly.', 'fluid-checkout' ),
				(int) $result[ 'imported' ],
				(int) $result[ 'skipped' ]
			);
		}

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

		// Bail if confirmation was not checked
		if ( empty( $_POST[ 'fc_settings_reset_confirm' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => __( 'Please confirm that you want to reset Fluid Checkout settings.', 'fluid-checkout' ),
			) );
		}

		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->reset_settings( true );

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: %d: number of settings reset */
				__( 'Settings reset to defaults. Reset: %d. An automatic backup was created and can be restored from this page. License keys and API keys were not changed.', 'fluid-checkout' ),
				(int) $result[ 'reset' ]
			),
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
			'message' => sprintf(
				/* translators: 1: number of settings restored, 2: number of settings removed */
				__( 'Previous settings restored from the automatic backup. Restored: %1$d. Removed: %2$d. License keys and API keys were not changed.', 'fluid-checkout' ),
				(int) $result[ 'restored' ],
				(int) $result[ 'deleted' ]
			),
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

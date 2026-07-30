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
		add_action( 'admin_post_fc_settings_import', array( $this, 'handle_import' ), 10 );
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
	 */
	public function get_tools_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=fc_checkout&section=tools' );
	}



	/**
	 * Handle settings export download.
	 */
	public function handle_export() {
		// Bail if nonce is invalid
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'fc_settings_export' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fluid-checkout' ) );
		}

		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to export settings.', 'fluid-checkout' ) );
		}

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
	 * Handle settings import upload.
	 */
	public function handle_import() {
		// Bail if nonce is invalid
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'fc_settings_import' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fluid-checkout' ) );
		}

		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to import settings.', 'fluid-checkout' ) );
		}

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
		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->import_settings_from_json( $json, true );

		// Maybe show parse / validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			$this->redirect_with_notice( array(
				'type'    => 'error',
				'message' => implode( ' ', $result[ 'errors' ] ),
			) );
		}

		$this->redirect_with_notice( array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: 1: number of settings imported, 2: number of settings skipped */
				__( 'Settings imported successfully. Imported: %1$d. Skipped: %2$d. An automatic backup was created and can be restored from this page. License keys and API keys were not changed. Re-select logo images and linked pages if they do not appear correctly.', 'fluid-checkout' ),
				(int) $result[ 'imported' ],
				(int) $result[ 'skipped' ]
			),
		) );
	}



	/**
	 * Handle settings reset.
	 */
	public function handle_reset() {
		// Bail if nonce is invalid
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'fc_settings_reset' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fluid-checkout' ) );
		}

		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to reset settings.', 'fluid-checkout' ) );
		}

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
		// Bail if nonce is invalid
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'fc_settings_restore' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fluid-checkout' ) );
		}

		// Bail if user does not have necessary permissions
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to restore settings.', 'fluid-checkout' ) );
		}

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

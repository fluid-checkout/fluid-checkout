<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings tools admin field type (export, import, reset, restore).
 */
class FluidCheckout_Admin_SettingType_Settings_Tools extends FluidCheckout {

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
		// Field types
		add_action( 'woocommerce_admin_field_fc_settings_tools', array( $this, 'output_field' ), 10 );

		// Standalone forms outside the WooCommerce settings form
		add_action( 'admin_footer', array( $this, 'maybe_output_standalone_forms' ), 10 );
	}



	/**
	 * Whether the current screen is the Fluid Checkout Tools settings section.
	 */
	public function is_tools_settings_screen() {
		// Bail if required query args are missing
		if ( ! isset( $_GET[ 'page' ], $_GET[ 'tab' ], $_GET[ 'section' ] ) ) { return false; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$page    = sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab     = sanitize_text_field( wp_unslash( $_GET[ 'tab' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET[ 'section' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return ( 'wc-settings' === $page && 'fc_checkout' === $tab && 'tools' === $section );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$action = isset( $value[ 'tool_action' ] ) ? $value[ 'tool_action' ] : '';

		// Bail if action is invalid
		if ( ! in_array( $action, array( 'reset', 'restore', 'export', 'import' ), true ) ) { return; }

		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description[ 'description' ];
		$tooltip_html      = $field_description[ 'tooltip_html' ];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo esc_html( $value[ 'title' ] ); ?> <?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value[ 'type' ] ) ); ?>">
				<?php
				switch ( $action ) {
					case 'reset':
						$this->output_reset_controls( $description );
						break;
					case 'restore':
						$this->output_restore_controls( $description );
						break;
					case 'export':
						$this->output_export_controls( $description );
						break;
					case 'import':
						$this->output_import_controls( $description );
						break;
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output reset controls.
	 *
	 * @param  string  $description  Field description HTML.
	 */
	public function output_reset_controls( $description ) {
		?>
		<fieldset class="fc-settings-tools">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<label>
					<input type="checkbox" name="fc_settings_reset_confirm" id="fc_settings_reset_confirm" form="fc_settings_reset_form" value="1" required>
					<?php echo esc_html__( 'I understand this will reset Fluid Checkout settings to their defaults.', 'fluid-checkout' ); ?>
				</label>
			</p>
			<p>
				<button type="submit" class="button button-secondary" form="fc_settings_reset_form" onclick="return confirm( '<?php echo esc_js( __( 'Are you sure you want to reset Fluid Checkout settings to defaults? An automatic backup will be created first.', 'fluid-checkout' ) ); ?>' );">
					<?php echo esc_html__( 'Reset settings', 'fluid-checkout' ); ?>
				</button>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Output restore controls.
	 *
	 * @param  string  $description  Field description HTML.
	 */
	public function output_restore_controls( $description ) {
		$backup = FluidCheckout_Admin_Settings_Tools_Service::instance()->get_last_backup();
		?>
		<fieldset class="fc-settings-tools">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $backup ) : ?>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: backup date/time in site timezone, 2: import or reset */
							__( 'Backup from %1$s, created before the last %2$s.', 'fluid-checkout' ),
							$this->format_backup_created_at( $backup[ 'created_at' ] ),
							'reset' === ( $backup[ 'created_by' ] ?? '' )
								? __( 'reset', 'fluid-checkout' )
								: __( 'import', 'fluid-checkout' )
						)
					);
					?>
				</p>
				<p>
					<button type="submit" class="button button-secondary" form="fc_settings_restore_form" onclick="return confirm( '<?php echo esc_js( __( 'Are you sure you want to restore the previous Fluid Checkout settings from the automatic backup?', 'fluid-checkout' ) ); ?>' );">
						<?php echo esc_html__( 'Restore previous settings', 'fluid-checkout' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No backup is available. A backup is created automatically before import or reset.', 'fluid-checkout' ); ?></p>
			<?php endif; ?>
		</fieldset>
		<?php
	}

	/**
	 * Format a backup timestamp for display in the site timezone.
	 *
	 * @param  string  $created_at  GMT/UTC datetime string.
	 */
	public function format_backup_created_at( $created_at ) {
		$timestamp = strtotime( $created_at );

		// Bail if timestamp is invalid
		if ( false === $timestamp ) {
			return $created_at;
		}

		return wp_date(
			sprintf( '%s %s', get_option( 'date_format' ), get_option( 'time_format' ) ),
			$timestamp
		);
	}

	/**
	 * Output export controls.
	 *
	 * @param  string  $description  Field description HTML.
	 */
	public function output_export_controls( $description ) {
		?>
		<fieldset class="fc-settings-tools">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<button type="submit" class="button button-secondary" form="fc_settings_export_form">
					<?php echo esc_html__( 'Export settings', 'fluid-checkout' ); ?>
				</button>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Output import controls.
	 *
	 * @param  string  $description  Field description HTML.
	 */
	public function output_import_controls( $description ) {
		?>
		<fieldset class="fc-settings-tools">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<input type="file" name="fc_settings_import_file" id="fc_settings_import_file" form="fc_settings_import_form" accept=".json,application/json" required>
			</p>
			<p>
				<button type="submit" class="button button-secondary" form="fc_settings_import_form">
					<?php echo esc_html__( 'Import settings', 'fluid-checkout' ); ?>
				</button>
			</p>
		</fieldset>
		<?php
	}



	/**
	 * Output standalone forms in the admin footer to avoid nested forms inside WooCommerce settings.
	 */
	public function maybe_output_standalone_forms() {
		// Bail if not on the Tools settings section
		if ( ! $this->is_tools_settings_screen() ) { return; }
		?>
		<form id="fc_settings_export_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_export">
			<?php wp_nonce_field( 'fc_settings_export' ); ?>
		</form>

		<form id="fc_settings_import_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_import">
			<?php wp_nonce_field( 'fc_settings_import' ); ?>
		</form>

		<form id="fc_settings_reset_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_reset">
			<?php wp_nonce_field( 'fc_settings_reset' ); ?>
		</form>

		<form id="fc_settings_restore_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_restore">
			<?php wp_nonce_field( 'fc_settings_restore' ); ?>
		</form>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Settings_Tools::instance();

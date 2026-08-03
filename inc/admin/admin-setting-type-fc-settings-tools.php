<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings tools admin field type (export, import, reset, restore).
 */
class FluidCheckout_Admin_SettingType_Settings_Tools extends FluidCheckout {

	/**
	 * Max rows to show per diff section in the import preview.
	 *
	 * @var int
	 */
	const PREVIEW_ROWS_LIMIT = 50;



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
	 * Output the soft live-site warning markup when applicable.
	 */
	public function maybe_output_live_site_warning() {
		// Bail if warning should not be shown
		if ( ! FluidCheckout_Admin_Settings_Tools_Service::instance()->should_show_live_site_warning() ) { return; }
		?>
		<p class="fc-settings-tools__live-warning">
			<?php echo esc_html__( 'This store looks like a live site. Import and reset will change the current configuration.', 'fluid-checkout' ); ?>
		</p>
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
				<button type="submit" class="button button-secondary" form="fc_settings_reset_form" onclick="return confirm( <?php echo wp_json_encode( __( 'Reset Fluid Checkout settings to defaults?', 'fluid-checkout' ) ); ?> );">
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
					<button type="submit" class="button button-secondary" form="fc_settings_restore_form" onclick="return confirm( <?php echo wp_json_encode( __( 'Restore previous settings from the backup?', 'fluid-checkout' ) ); ?> );">
						<?php echo esc_html__( 'Restore previous settings', 'fluid-checkout' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No backup is available yet.', 'fluid-checkout' ); ?></p>
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
	 * Output import controls or the pending preview review panel.
	 *
	 * @param  string  $description  Field description HTML.
	 */
	public function output_import_controls( $description ) {
		$preview = FluidCheckout_Admin_Settings_Tools_Service::instance()->get_import_preview();

		// Maybe show import preview review
		if ( null !== $preview ) {
			$this->output_import_preview( $preview );
			return;
		}
		?>
		<fieldset class="fc-settings-tools">
			<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php $this->maybe_output_live_site_warning(); ?>
			<p>
				<input type="file" name="fc_settings_import_file" id="fc_settings_import_file" form="fc_settings_import_form" accept=".json,application/json" required>
			</p>
			<p class="fc-settings-tools__import-mode">
				<label>
					<input type="radio" name="fc_settings_import_mode" form="fc_settings_import_form" value="update" checked>
					<strong><?php echo esc_html__( 'Update matching settings only', 'fluid-checkout' ); ?></strong>
				</label>
				<span class="description"><?php echo esc_html__( 'Only changes settings present in the file.', 'fluid-checkout' ); ?></span>
			</p>
			<p class="fc-settings-tools__import-mode">
				<label>
					<input type="radio" name="fc_settings_import_mode" form="fc_settings_import_form" value="replace" id="fc_settings_import_mode_replace">
					<strong><?php echo esc_html__( 'Replace all Fluid Checkout settings', 'fluid-checkout' ); ?></strong>
				</label>
				<span class="description"><?php echo esc_html__( 'Clears saved settings, then applies the file. Best when copying from another site.', 'fluid-checkout' ); ?></span>
			</p>
			<p>
				<button type="submit" class="button button-secondary" form="fc_settings_import_form" onclick="return fcSettingsToolsConfirmImport( event );">
					<?php echo esc_html__( 'Review import', 'fluid-checkout' ); ?>
				</button>
			</p>
		</fieldset>
		<script>
			function fcSettingsToolsConfirmImport( event ) {
				var replaceInput = document.getElementById( 'fc_settings_import_mode_replace' );
				if ( replaceInput && replaceInput.checked ) {
					return confirm( <?php echo wp_json_encode( __( 'Clear current settings, then review the import?', 'fluid-checkout' ) ); ?> );
				}
				return true;
			}
		</script>
		<?php
	}

	/**
	 * Output the import preview review panel.
	 *
	 * @param  array  $preview  Pending preview payload.
	 */
	public function output_import_preview( $preview ) {
		$diff = $preview[ 'diff' ];
		$mode = $preview[ 'mode' ];
		$filename = ! empty( $preview[ 'filename' ] ) ? $preview[ 'filename' ] : '';
		$changed_count = count( $diff[ 'changed' ] );
		$added_count = count( $diff[ 'added' ] );
		$will_clear_count = count( $diff[ 'will_clear' ] );
		?>
		<fieldset class="fc-settings-tools fc-settings-tools--preview">
			<p><strong><?php echo esc_html__( 'Review import', 'fluid-checkout' ); ?></strong></p>
			<?php if ( $filename ) : ?>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: uploaded file name */
							__( 'File: %s', 'fluid-checkout' ),
							$filename
						)
					);
					?>
				</p>
			<?php endif; ?>
			<p class="description">
				<?php
				echo esc_html(
					'replace' === $mode
						? __( 'Mode: Replace', 'fluid-checkout' )
						: __( 'Mode: Update', 'fluid-checkout' )
				);
				?>
			</p>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: changed count, 2: added count, 3: unchanged count, 4: skipped count */
						__( 'Changed: %1$d. Added: %2$d. Unchanged: %3$d. Skipped: %4$d.', 'fluid-checkout' ),
						$changed_count,
						$added_count,
						(int) $diff[ 'unchanged_count' ],
						(int) $diff[ 'skipped_count' ]
					)
				);
				if ( 'replace' === $mode ) {
					echo ' ';
					echo esc_html(
						sprintf(
							/* translators: %d: number of settings that will be cleared */
							__( 'Will clear: %d.', 'fluid-checkout' ),
							$will_clear_count
						)
					);
				}
				?>
			</p>

			<?php $this->output_diff_key_value_table( __( 'Settings that will change', 'fluid-checkout' ), $diff[ 'changed' ] ); ?>
			<?php $this->output_diff_key_value_table( __( 'Settings that will be added', 'fluid-checkout' ), $diff[ 'added' ] ); ?>
			<?php
			if ( 'replace' === $mode && $will_clear_count > 0 ) {
				$this->output_diff_key_list( __( 'Settings that will be cleared', 'fluid-checkout' ), $diff[ 'will_clear' ] );
			}
			?>

			<p>
				<button type="submit" class="button button-primary" form="fc_settings_import_apply_form" onclick="return confirm( <?php echo wp_json_encode( __( 'Apply this import now?', 'fluid-checkout' ) ); ?> );">
					<?php echo esc_html__( 'Confirm import', 'fluid-checkout' ); ?>
				</button>
				<button type="submit" class="button button-secondary" form="fc_settings_import_cancel_form">
					<?php echo esc_html__( 'Cancel', 'fluid-checkout' ); ?>
				</button>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Output a key/from/to table for diff entries.
	 *
	 * @param  string  $title    Section title.
	 * @param  array   $entries  Map of option => { from, to }.
	 */
	public function output_diff_key_value_table( $title, $entries ) {
		// Bail if no entries
		if ( empty( $entries ) ) { return; }

		$total = count( $entries );
		$entries = array_slice( $entries, 0, self::PREVIEW_ROWS_LIMIT, true );
		?>
		<div class="fc-settings-tools__diff-section">
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
			<table class="widefat striped fc-settings-tools__diff-table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Setting', 'fluid-checkout' ); ?></th>
						<th><?php echo esc_html__( 'Current', 'fluid-checkout' ); ?></th>
						<th><?php echo esc_html__( 'New', 'fluid-checkout' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entries as $option => $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $option ); ?></code></td>
							<td><code><?php echo esc_html( null === $row[ 'from' ] ? '—' : (string) $row[ 'from' ] ); ?></code></td>
							<td><code><?php echo esc_html( (string) $row[ 'to' ] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $total > self::PREVIEW_ROWS_LIMIT ) : ?>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of additional rows not shown */
							__( 'And %d more…', 'fluid-checkout' ),
							$total - self::PREVIEW_ROWS_LIMIT
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output a simple list of option keys for the will-clear bucket.
	 *
	 * @param  string  $title  Section title.
	 * @param  array   $keys   Option keys.
	 */
	public function output_diff_key_list( $title, $keys ) {
		// Bail if no keys
		if ( empty( $keys ) ) { return; }

		$total = count( $keys );
		$keys = array_slice( $keys, 0, self::PREVIEW_ROWS_LIMIT );
		?>
		<div class="fc-settings-tools__diff-section">
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
			<ul class="fc-settings-tools__diff-list">
				<?php foreach ( $keys as $option ) : ?>
					<li><code><?php echo esc_html( $option ); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $total > self::PREVIEW_ROWS_LIMIT ) : ?>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of additional rows not shown */
							__( 'And %d more…', 'fluid-checkout' ),
							$total - self::PREVIEW_ROWS_LIMIT
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>
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

		<form id="fc_settings_import_apply_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_import_apply">
			<?php wp_nonce_field( 'fc_settings_import_apply' ); ?>
		</form>

		<form id="fc_settings_import_cancel_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fc-settings-tools-form">
			<input type="hidden" name="action" value="fc_settings_import_cancel">
			<?php wp_nonce_field( 'fc_settings_import_cancel' ); ?>
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

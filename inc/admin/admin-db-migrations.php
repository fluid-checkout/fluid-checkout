<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage database version migrations.
 *
 * Migrations declare ordered steps via `get_migration_steps()`. Each step has a risk
 * tier (low / medium / high). Low steps run silently in wp-admin; medium and high
 * require merchant confirmation. `fc_db_version` advances only when every step for
 * that migration version is complete. Partial progress is stored in
 * `fc_db_migration_step_progress`.
 *
 * High-risk confirmation on guided setup screens should eventually live in a final
 * guided-setup step (one step listing all pending high-risk changes). Until then,
 * use the FC admin notice on settings and other admin screens (not guided setup).
 */
class FluidCheckout_AdminDBMigrations extends FluidCheckout {

	/**
	 * Plugin prefix for options and admin notices.
	 */
	private static $plugin_prefix = 'fc';

	/**
	 * Low risk: no irreversible deletion; FC-scoped copy/update; additive FC schema only.
	 */
	const RISK_LOW = 'low';

	/**
	 * Medium risk: deletes after copy; shared WC options; alters existing FC columns.
	 */
	const RISK_MEDIUM = 'medium';

	/**
	 * High risk: bulk entity updates; destructive DDL; WC core table changes.
	 */
	const RISK_HIGH = 'high';

	/**
	 * Option key for completed step ids per migration version.
	 */
	const STEP_PROGRESS_OPTION = 'fc_db_migration_step_progress';



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
		add_action( 'admin_init', array( $this, 'maybe_dismiss_migrate_success_message' ), 10 );
		add_action( 'admin_init', array( $this, 'maybe_apply_low_risk_migrations' ), 5 );
		add_action( 'admin_init', array( $this, 'maybe_migrate_database' ), 10 );
		add_action( 'init', array( $this, 'maybe_migrate_database_on_first_activation' ), 10 );
	}



	/**
	 * Maybe dismiss database updated notice.
	 */
	public function maybe_dismiss_migrate_success_message() {
		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), self::$plugin_prefix . '_updated_db_notice' ) ) { return; }

		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail if not dismiss updated database notice request
		if ( ! array_key_exists( self::$plugin_prefix . '_action', $_GET ) || 'dismiss_updated_db' !== sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_action' ] ) ) ) { return; }

		// Set flag to hide update success notice
		$this->update_success_notice_option( false );
	}



	/**
	 * Maybe migrate database after merchant confirmation.
	 */
	public function maybe_migrate_database() {
		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), self::$plugin_prefix . '_update_db' ) ) { return; }

		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail if not update database request
		if ( ! array_key_exists( self::$plugin_prefix . '_action', $_GET ) || 'update_db' !== sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_action' ] ) ) ) { return; }

		// Bail if there are no migrations to apply
		if ( ! $this->has_migrations_to_apply() ) { return; }

		// Run all remaining pending steps
		$ran_count = $this->run_pending_steps( array( self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH ) );

		// Maybe set flag to show update success notice
		if ( $ran_count > 0 ) {
			$this->update_success_notice_option( true );
		}

		// Get redirect url
		$redirect_url = remove_query_arg( array( '_wpnonce', self::$plugin_prefix . '_action' ) );

		// Redirect
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Silently apply pending low-risk migration steps in wp-admin.
	 */
	public function maybe_apply_low_risk_migrations() {
		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail if there are no low-risk steps to apply
		if ( empty( $this->get_pending_steps( array( self::RISK_LOW ) ) ) ) { return; }

		$this->run_pending_steps( array( self::RISK_LOW ) );
	}

	/**
	 * Maybe migrate database on first activation (low and medium only).
	 */
	public function maybe_migrate_database_on_first_activation() {
		// Bail if on multisite network
		if ( is_multisite() ) { return; }

		// Bail if there are no migrations to apply
		if ( ! $this->has_migrations_to_apply() ) { return; }

		// Get install date
		// Need to get option directly as the Lite plugin might not be activated at this point
		$install_date = get_option( self::$plugin_prefix . '_plugin_activation_time' );
		$past_date = strtotime( '-10 min' );

		// Bail if time limit has passed since first installation,
		// in which case the admin user needs to confirm medium/high steps through the admin notice.
		if ( ! $install_date || $past_date > $install_date ) { return; }

		// Auto-run low and medium steps only; never high
		$this->run_pending_steps( array( self::RISK_LOW, self::RISK_MEDIUM ) );
	}



	/**
	 * Run pending migration steps matching the given risk levels.
	 *
	 * @param   array  $risk_levels  Risk levels to run.
	 * @return  int    Number of steps executed.
	 */
	public function run_pending_steps( $risk_levels ) {
		$pending_steps = $this->get_pending_steps( $risk_levels );
		$ran_count = 0;
		$touched_versions = array();

		foreach ( $pending_steps as $pending ) {
			$step = $pending[ 'step' ];
			$version = $pending[ 'version' ];
			$callback = isset( $step[ 'callback' ] ) ? $step[ 'callback' ] : null;

			// Skip if callback is not callable
			if ( ! is_callable( $callback ) ) { continue; }

			call_user_func( $callback );
			$this->mark_step_completed( $version, $step[ 'id' ] );
			$touched_versions[ $version ] = $pending[ 'class' ];
			$ran_count++;
		}

		// Maybe bump db version for fully completed migration files
		foreach ( $touched_versions as $version => $migration_class ) {
			if ( ! $this->is_migration_version_complete( $migration_class ) ) { continue; }

			$this->update_db_version( $version );
			$this->clear_step_progress( $version );
		}

		return $ran_count;
	}



	/**
	 * Get the migration class instance for a migration file.
	 *
	 * @param   string  $migration_file  Absolute path to the migration file.
	 */
	public function get_migration_class_instance( $migration_file ) {
		// Get version number from file name
		$migration_version = str_replace( 'migration-', '', basename( $migration_file ) );
		$migration_version = str_replace( '.php', '', $migration_version );
		$class_name = 'FluidCheckout_Migration_' . str_replace( '.', '_', $migration_version );

		// Maybe load migration file
		if ( ! class_exists( $class_name ) ) {
			require_once $migration_file;
		}

		// Bail if migration class is not available
		if ( ! class_exists( $class_name ) ) { return null; }

		// Return migration class instance
		return $class_name::instance();
	}



	/**
	 * Get normalized migration steps for a migration file.
	 *
	 * @param   string  $migration_file  Absolute path to the migration file.
	 * @return  array|null
	 */
	public function get_migration_steps_for_file( $migration_file ) {
		$migration_class = $this->get_migration_class_instance( $migration_file );

		// Bail if migration class is not available
		if ( ! $migration_class ) { return null; }

		// Bail if steps are not declared
		if ( ! method_exists( $migration_class, 'get_migration_steps' ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: migration class name */
					esc_html__( 'Migration class %s must implement get_migration_steps().', 'fluid-checkout' ),
					esc_html( get_class( $migration_class ) )
				),
				'5.0.0'
			);
			return null;
		}

		$steps = $migration_class->get_migration_steps();

		// Bail if steps are empty or invalid
		if ( ! is_array( $steps ) || empty( $steps ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: migration class name */
					esc_html__( 'Migration class %s returned an empty get_migration_steps() list.', 'fluid-checkout' ),
					esc_html( get_class( $migration_class ) )
				),
				'5.0.0'
			);
			return null;
		}

		$normalized = array();
		foreach ( $steps as $step ) {
			// Skip invalid step entries
			if ( ! is_array( $step ) || empty( $step[ 'id' ] ) ) { continue; }

			$risk = isset( $step[ 'risk' ] ) ? $step[ 'risk' ] : self::RISK_HIGH;
			if ( ! in_array( $risk, array( self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH ), true ) ) {
				$risk = self::RISK_HIGH;
			}

			$normalized[] = array(
				'id'          => (string) $step[ 'id' ],
				'description' => isset( $step[ 'description' ] ) ? (string) $step[ 'description' ] : '',
				'risk'        => $risk,
				'callback'    => isset( $step[ 'callback' ] ) ? $step[ 'callback' ] : null,
			);
		}

		// Bail if no valid steps remain
		if ( empty( $normalized ) ) { return null; }

		return $normalized;
	}



	/**
	 * Get completed step ids for a migration version.
	 *
	 * @param   string  $version  Migration version string.
	 * @return  array
	 */
	public function get_completed_step_ids( $version ) {
		$progress = get_option( self::STEP_PROGRESS_OPTION, array() );
		$progress = is_array( $progress ) ? $progress : array();

		if ( ! isset( $progress[ $version ] ) || ! is_array( $progress[ $version ] ) ) {
			return array();
		}

		return array_values( array_map( 'strval', $progress[ $version ] ) );
	}

	/**
	 * Mark a migration step as completed.
	 *
	 * @param   string  $version  Migration version string.
	 * @param   string  $step_id  Step id.
	 */
	public function mark_step_completed( $version, $step_id ) {
		$progress = get_option( self::STEP_PROGRESS_OPTION, array() );
		$progress = is_array( $progress ) ? $progress : array();

		if ( ! isset( $progress[ $version ] ) || ! is_array( $progress[ $version ] ) ) {
			$progress[ $version ] = array();
		}

		// Bail if already recorded
		if ( in_array( (string) $step_id, $progress[ $version ], true ) ) { return; }

		$progress[ $version ][] = (string) $step_id;
		update_option( self::STEP_PROGRESS_OPTION, $progress, false );
	}

	/**
	 * Clear step progress for a migration version.
	 *
	 * @param   string  $version  Migration version string.
	 */
	public function clear_step_progress( $version ) {
		$progress = get_option( self::STEP_PROGRESS_OPTION, array() );
		$progress = is_array( $progress ) ? $progress : array();

		// Bail if version has no progress
		if ( ! isset( $progress[ $version ] ) ) { return; }

		unset( $progress[ $version ] );

		if ( empty( $progress ) ) {
			delete_option( self::STEP_PROGRESS_OPTION );
			return;
		}

		update_option( self::STEP_PROGRESS_OPTION, $progress, false );
	}

	/**
	 * Whether every step for a migration class version is complete.
	 *
	 * @param   object  $migration_class  Migration class instance.
	 * @return  bool
	 */
	public function is_migration_version_complete( $migration_class ) {
		// Bail if class does not provide version or steps
		if ( ! method_exists( $migration_class, 'get_db_version' ) || ! method_exists( $migration_class, 'get_migration_steps' ) ) {
			return false;
		}

		$version = $migration_class->get_db_version();
		$steps = $migration_class->get_migration_steps();

		// Empty steps list is treated as incomplete (invalid migration)
		if ( ! is_array( $steps ) || empty( $steps ) ) { return false; }

		$completed = $this->get_completed_step_ids( $version );
		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) || empty( $step[ 'id' ] ) ) { continue; }
			if ( ! in_array( (string) $step[ 'id' ], $completed, true ) ) { return false; }
		}

		return true;
	}



	/**
	 * Get pending steps for a migration class (not yet in progress storage).
	 *
	 * @param   object  $migration_class  Migration class instance.
	 * @return  array
	 */
	public function get_pending_steps_for_version( $migration_class ) {
		// Bail if class does not provide version or steps
		if ( ! method_exists( $migration_class, 'get_db_version' ) || ! method_exists( $migration_class, 'get_migration_steps' ) ) {
			return array();
		}

		$version = $migration_class->get_db_version();
		$steps = $migration_class->get_migration_steps();
		$completed = $this->get_completed_step_ids( $version );
		$pending = array();

		if ( ! is_array( $steps ) ) { return array(); }

		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) || empty( $step[ 'id' ] ) ) { continue; }
			if ( in_array( (string) $step[ 'id' ], $completed, true ) ) { continue; }

			$risk = isset( $step[ 'risk' ] ) ? $step[ 'risk' ] : self::RISK_HIGH;
			if ( ! in_array( $risk, array( self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH ), true ) ) {
				$risk = self::RISK_HIGH;
			}

			$pending[] = array(
				'id'          => (string) $step[ 'id' ],
				'description' => isset( $step[ 'description' ] ) ? (string) $step[ 'description' ] : '',
				'risk'        => $risk,
				'callback'    => isset( $step[ 'callback' ] ) ? $step[ 'callback' ] : null,
			);
		}

		return $pending;
	}



	/**
	 * Get pending migration steps across all pending migration files.
	 *
	 * @param   array|null  $risk_levels  Optional risk filter. Null = all risks.
	 * @return  array
	 */
	public function get_pending_steps( $risk_levels = null ) {
		$migrations_to_apply = $this->get_migrations_to_apply();
		$pending = array();

		foreach ( $migrations_to_apply as $migration_file ) {
			$steps = $this->get_migration_steps_for_file( $migration_file );

			// Skip invalid migration files
			if ( null === $steps ) { continue; }

			$migration_class = $this->get_migration_class_instance( $migration_file );
			if ( ! $migration_class || ! method_exists( $migration_class, 'get_db_version' ) ) { continue; }

			$version = $migration_class->get_db_version();
			$completed = $this->get_completed_step_ids( $version );

			foreach ( $steps as $step ) {
				if ( in_array( $step[ 'id' ], $completed, true ) ) { continue; }

				// Maybe filter by risk
				if ( is_array( $risk_levels ) && ! in_array( $step[ 'risk' ], $risk_levels, true ) ) { continue; }

				$pending[] = array(
					'file'    => $migration_file,
					'version' => $version,
					'class'   => $migration_class,
					'step'    => $step,
				);
			}
		}

		return $pending;
	}



	/**
	 * Get the highest risk among pending steps.
	 *
	 * @return  string|null
	 */
	public function get_highest_pending_risk() {
		$pending_steps = $this->get_pending_steps();

		// Bail if nothing pending
		if ( empty( $pending_steps ) ) { return null; }

		$rank = array(
			self::RISK_LOW    => 1,
			self::RISK_MEDIUM => 2,
			self::RISK_HIGH   => 3,
		);
		$highest = self::RISK_LOW;

		foreach ( $pending_steps as $pending ) {
			$risk = $pending[ 'step' ][ 'risk' ];
			if ( $rank[ $risk ] > $rank[ $highest ] ) {
				$highest = $risk;
			}
		}

		return $highest;
	}

	/**
	 * Whether any pending medium or high risk steps remain.
	 *
	 * @return  bool
	 */
	public function has_migrations_requiring_confirmation() {
		return ! empty( $this->get_pending_steps( array( self::RISK_MEDIUM, self::RISK_HIGH ) ) );
	}



	/**
	 * Get descriptions of pending migration steps.
	 *
	 * @param   array|null  $risk_levels  Optional risk filter. Null = all pending steps.
	 * @return  array
	 */
	public function get_migrations_descriptions( $risk_levels = null ) {
		$pending_steps = $this->get_pending_steps( $risk_levels );
		$descriptions = array();

		foreach ( $pending_steps as $pending ) {
			$description = $pending[ 'step' ][ 'description' ];

			// Skip empty descriptions
			if ( empty( $description ) ) { continue; }

			$descriptions[] = $description;
		}

		return $descriptions;
	}



	/**
	 * Get current database version.
	 */
	public function get_db_version() {
		// Get db version
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$db_version = get_option( self::$plugin_prefix . '_db_version', null );

		// Return db version
		return $db_version;
	}

	/**
	 * Update database version.
	 *
	 * @param  string  $db_version  Database version to update to.
	 */
	public function update_db_version( $db_version ) {
		// Update db version
		update_option( self::$plugin_prefix . '_db_version', $db_version );
	}



	/**
	 * Update flag to show database updated notice.
	 *
	 * @param  bool  $show_notice  Whether to show notice or not.
	 */
	public function update_success_notice_option( $show_notice = true ) {
		if ( true === $show_notice ) {
			// Set to show notice
			update_option( self::$plugin_prefix . '_show_db_update_notice', 'yes' );
		}
		else {
			// Set to hide notice
			delete_option( self::$plugin_prefix . '_show_db_update_notice' );
		}
	}



	/**
	 * Get list of available migration files.
	 */
	public function get_available_migrations() {
		// Get list of migration files
		$files = glob( self::$directory_path . 'inc/admin/migrations/migration-*.php' );
		$files = is_array( $files ) ? $files : array();

		/**
		 * Filters the list of available database migration files.
		 *
		 * @param  array  $files  Absolute paths to migration PHP files.
		 */
		$files = apply_filters( 'fc_available_db_migrations', $files );
		$files = is_array( $files ) ? $files : array();

		// Sort files by version number
		usort( $files, function( $a, $b ) {
			// Get version number from file name
			$a_version = str_replace( 'migration-', '', basename( $a ) );
			$a_version = str_replace( '.php', '', $a_version );
			$b_version = str_replace( 'migration-', '', basename( $b ) );
			$b_version = str_replace( '.php', '', $b_version );

			// Compare version numbers
			return version_compare( $a_version, $b_version );
		} );

		// Return list of files
		return $files;
	}



	/**
	 * Get list of migrations that have not been fully applied yet.
	 */
	public function get_migrations_to_apply() {
		// Get db version
		$db_version = $this->get_db_version();
		$db_version = ! empty( $db_version ) ? $db_version : '0.0.0';

		// Get available migrations
		$available_migrations = $this->get_available_migrations();

		// Get migrations to apply
		$migrations_to_apply = array();
		foreach ( $available_migrations as $migration ) {
			// Get version number from file name
			$migration_version = str_replace( 'migration-', '', basename( $migration ) );
			$migration_version = str_replace( '.php', '', $migration_version );

			// Compare version numbers
			if ( version_compare( $migration_version, $db_version, '>' ) ) {
				$migrations_to_apply[] = $migration;
			}
		}

		// Return migrations to apply
		return $migrations_to_apply;
	}



	/**
	 * Check whether there are migrations that have not been fully applied yet.
	 */
	public function has_migrations_to_apply() {
		return ! empty( $this->get_pending_steps() );
	}

}

FluidCheckout_AdminDBMigrations::instance();

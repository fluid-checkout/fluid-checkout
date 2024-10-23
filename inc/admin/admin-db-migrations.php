<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage database version migrations.
 */
class FluidCheckout_AdminDBMigrations extends FluidCheckout {

	/**
	 * Plugin prefix for the admin notices options.
	 */
	private static $plugin_prefix = 'fc';



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
	 * Maybe migrate database.
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

		// Migrate database
		$this->migrate_database();

		// Set flag to show update success notice
		$this->update_success_notice_option( true );

		// Get redirect url
		$redirect_url = remove_query_arg( array( '_wpnonce', self::$plugin_prefix . '_action' ) );

		// Redirect
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Maybe migrate database on first activation.
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
		// in which case the admin user needs to confirm the action through the admin notice.
		if ( ! $install_date || $past_date > $install_date ) { return; }

		// Migrate database, do not show notice in this case.
		$this->migrate_database();
	}



	/**
	 * Migrate database.
	 */
	public function migrate_database() {
		// Get migrations to apply
		$migrations_to_apply = $this->get_migrations_to_apply();

		// Apply migrations
		foreach ( $migrations_to_apply as $migration ) {
			// Initialize migration class
			$migration_class = require_once $migration;

			// Maybe apply migration
			if ( method_exists( $migration_class, 'migrate' ) ) {
				$migration_class->migrate();
			}

			// Maybe update db version
			if ( method_exists( $migration_class, 'get_db_version' ) ) {
				$this->update_db_version( $migration_class->get_db_version() );
			}
		}
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
	 * Get list of migrations that have not been apply yet.
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
	 * Check whether there are migrations that have not been apply yet.
	 */
	public function has_migrations_to_apply() {
		// Get migrations to apply
		$migrations_to_apply = $this->get_migrations_to_apply();

		// Return whether there are migrations to apply
		return ! empty( $migrations_to_apply );
	}

}

FluidCheckout_AdminDBMigrations::instance();

<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Platform_Api
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run `npm run install-wp-tests` ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/fluid-checkout.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Maybe run database migrations
function _maybe_run_migrations() {
	$migration_instance = FluidCheckout_AdminDBMigrations::instance();

	if ( $migration_instance->has_migrations_to_apply() ) { 
		echo "Running migrations... ";
		$migration_instance->migrate_database();
		echo "completed.\n";
	} else {
		echo "Skipping migration.\n";
	}
}
tests_add_filter( 'setup_theme', '_maybe_run_migrations' );


// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";


// Load the test traits
require dirname( __FILE__ ) . '/inc/trait-transactional-test-class.php';

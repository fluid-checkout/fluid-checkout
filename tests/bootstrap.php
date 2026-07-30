<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Fluid_Checkout
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

/**
 * Read WordPress test database settings from the install-wp-tests npm script.
 *
 * @param   string  $plugin_dir  Plugin root directory.
 *
 * @return  array{db_name: string, db_user: string, db_password: string, db_host: string}|null
 */
function _fc_get_wp_tests_db_config_from_package_json( $plugin_dir ) {
	$package_json_path = rtrim( $plugin_dir, '/\\' ) . '/package.json';

	if ( ! file_exists( $package_json_path ) ) {
		return null;
	}

	$package = json_decode( file_get_contents( $package_json_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( empty( $package[ 'scripts' ][ 'install-wp-tests' ] ) ) {
		return null;
	}

	$install_wp_tests_script = $package[ 'scripts' ][ 'install-wp-tests' ];

	if ( ! preg_match( '/install-wp-tests\.sh\s+(\S+)\s+(\S+)\s+(\S+)(?:\s+(\S+))?/', $install_wp_tests_script, $matches ) ) {
		return null;
	}

	return array(
		'db_name'     => $matches[ 1 ],
		'db_user'     => $matches[ 2 ],
		'db_password' => $matches[ 3 ],
		'db_host'     => ! empty( $matches[ 4 ] ) ? $matches[ 4 ] : 'localhost',
	);
}

/**
 * Update database constants in wp-tests-config.php.
 *
 * @param   string  $config_path  Path to wp-tests-config.php.
 * @param   array   $db_config    Database config array.
 */
function _fc_sync_wp_tests_config( $config_path, $db_config ) {
	if ( ! file_exists( $config_path ) ) {
		return;
	}

	$config = file_get_contents( $config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	$replacements = array(
		'DB_NAME'     => $db_config[ 'db_name' ],
		'DB_USER'     => $db_config[ 'db_user' ],
		'DB_PASSWORD' => $db_config[ 'db_password' ],
		'DB_HOST'     => $db_config[ 'db_host' ],
	);

	foreach ( $replacements as $constant => $value ) {
		$escaped_value = addcslashes( $value, "\\'" );
		$config        = preg_replace(
			"/define\\( '" . $constant . "', '[^']*' \\);/",
			"define( '" . $constant . "', '" . $escaped_value . "' );",
			$config
		);
	}

	file_put_contents( $config_path, $config ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents
}

$_wp_tests_db_config = _fc_get_wp_tests_db_config_from_package_json( dirname( dirname( __FILE__ ) ) );

if ( null !== $_wp_tests_db_config ) {
	_fc_sync_wp_tests_config( $_tests_dir . '/wp-tests-config.php', $_wp_tests_db_config );
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
 * Manually load WooCommerce and Fluid Checkout.
 */
function _fc_manually_load_plugin() {
	$plugins_dir = dirname( dirname( dirname( __FILE__ ) ) );

	// Load WooCommerce (required by Fluid Checkout)
	$woocommerce_file = $plugins_dir . '/woocommerce/woocommerce.php';
	if ( ! file_exists( $woocommerce_file ) ) {
		echo "Could not find WooCommerce at {$woocommerce_file}." . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit( 1 );
	}
	require $woocommerce_file;

	// Load Fluid Checkout Lite
	require dirname( dirname( __FILE__ ) ) . '/fluid-checkout.php';
}
tests_add_filter( 'muplugins_loaded', '_fc_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load settings tools service (normally loaded only in wp-admin)
require_once dirname( dirname( __FILE__ ) ) . '/inc/admin/admin-settings-tools-service.php';

// Load test traits
require dirname( __FILE__ ) . '/inc/trait-options-test-class.php';

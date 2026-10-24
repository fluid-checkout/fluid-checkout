<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package FluidCheckout
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
function _get_wp_tests_db_config_from_package_json( $plugin_dir ) {
	$package_json_path = rtrim( $plugin_dir, '/\\' ) . '/package.json';

	if ( ! file_exists( $package_json_path ) ) {
		return null;
	}

	$package = json_decode( file_get_contents( $package_json_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( empty( $package[ 'scripts' ][ 'install-wp-tests' ] ) ) {
		return null;
	}

	$install_wp_tests_script = $package[ 'scripts' ][ 'install-wp-tests' ];

	// Exclude shell quotes so wrapped npm scripts (bash -lc '... root root') parse correctly.
	if ( ! preg_match( '/install-wp-tests\.sh\s+([^\s\'"]+)\s+([^\s\'"]+)\s+([^\s\'"]+)(?:\s+([^\s\'"]+))?/', $install_wp_tests_script, $matches ) ) {
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
function _sync_wp_tests_config( $config_path, $db_config ) {
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
			"/define\\( '" . preg_quote( $constant, '/' ) . "',\\s*'.+?'\\s*\\);/",
			"define( '" . $constant . "', '" . $escaped_value . "' );",
			$config,
			1
		);
	}

	file_put_contents( $config_path, $config ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents
}

$_wp_tests_db_config = _get_wp_tests_db_config_from_package_json( dirname( dirname( __FILE__ ) ) );

if ( null !== $_wp_tests_db_config ) {
	_sync_wp_tests_config( $_tests_dir . '/wp-tests-config.php', $_wp_tests_db_config );
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run `npm run install-wp-tests` or `bin/install-wp-tests.sh` ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Mark required dependency plugins as active for the WordPress test runtime.
 *
 * @param   array  $active_plugins  Active plugins.
 *
 * @return  array
 */
function _fc_lite_test_set_active_plugins( $active_plugins ) {
	$required_plugins = array(
		'woocommerce/woocommerce.php',
		'fluid-checkout/fluid-checkout.php',
	);

	return array_values( array_unique( array_merge( $required_plugins, (array) $active_plugins ) ) );
}
tests_add_filter( 'option_active_plugins', '_fc_lite_test_set_active_plugins' );

/**
 * Get the Local site plugins directory.
 *
 * @return  string
 */
function _fc_lite_test_get_plugins_dir() {
	return dirname( dirname( dirname( __FILE__ ) ) );
}

/**
 * Maybe load a plugin from the Local site plugins directory.
 *
 * @param   string  $plugin_file  Plugin folder and main file.
 */
function _fc_lite_test_maybe_load_dependency_plugin( $plugin_file ) {
	$plugin_path = trailingslashit( _fc_lite_test_get_plugins_dir() ) . ltrim( $plugin_file, '/\\' );

	if ( file_exists( $plugin_path ) ) {
		require_once $plugin_path;
	}
}

/**
 * Manually load dependency plugins and the plugin being tested.
 */
function _fc_lite_test_manually_load_plugin() {
	_fc_lite_test_maybe_load_dependency_plugin( 'woocommerce/woocommerce.php' );

	// Admin feature classes (settings, guided setup concrete) load only when is_admin() is true.
	if ( ! defined( 'WP_ADMIN' ) ) {
		define( 'WP_ADMIN', true );
	}

	require_once dirname( dirname( __FILE__ ) ) . '/fluid-checkout.php';
}
tests_add_filter( 'muplugins_loaded', '_fc_lite_test_manually_load_plugin' );

/**
 * Ensure WooCommerce schema exists in the test database.
 */
tests_add_filter(
	'setup_theme',
	static function () {
		if ( class_exists( 'WC_Install' ) ) {
			WC_Install::install();
		}
	},
	0
);

// Start up the WordPress testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load the shared test trait.
require dirname( __FILE__ ) . '/inc/trait-transactional-test-class.php';

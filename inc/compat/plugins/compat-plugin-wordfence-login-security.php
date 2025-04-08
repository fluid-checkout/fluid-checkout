<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Wordfence Login Security (by Wordfence).
 */
class FluidCheckout_WordfenceLoginSecurity extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_compat_plugin_lite();
	}

	/**
	 * Load compatibility with Wordfence plugin.
	 */
	public function load_compat_plugin_lite() {
		$compat_file = FluidCheckout::$directory_path . 'inc/compat/plugins/compat-plugin-wordfence.php';
		if ( file_exists( $compat_file ) ) {
			require_once $compat_file;
		}
	}

}

FluidCheckout_WordfenceLoginSecurity::instance();

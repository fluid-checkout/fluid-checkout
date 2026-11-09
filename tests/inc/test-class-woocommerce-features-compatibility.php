<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: WooCommerce features compatibility declarations.
 */
class FluidCheckout_WooCommerce_Features_Compatibility_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: the Lite main plugin file is registered in the WordPress plugins list.
	 */
	public function test_plugin_main_file_is_listed_in_wp_plugins() {
		$fluid_checkout = FluidCheckout::instance();
		$main_plugin    = ( new ReflectionClass( 'FluidCheckout' ) )->getFileName();

		$this->assertFileExists( $main_plugin );
		$this->assertSame( FluidCheckout::$plugin_basename, plugin_basename( $main_plugin ) );
		$this->assertTrue( $fluid_checkout->is_plugin_file_listed_in_wp_plugins( $main_plugin ) );
	}

	/**
	 * Test: a nested bundled Lite path is not registered as its own WordPress plugin.
	 */
	public function test_bundled_lite_plugin_file_is_not_listed_in_wp_plugins() {
		$fluid_checkout = FluidCheckout::instance();
		$bundled_path   = WP_PLUGIN_DIR . '/fluid-checkout-pro/bundled/fluid-checkout/fluid-checkout.php';

		$this->assertFalse( $fluid_checkout->is_plugin_file_listed_in_wp_plugins( $bundled_path ) );
	}
}

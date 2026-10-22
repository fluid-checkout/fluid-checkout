<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: plugin bootstrap.
 */
class FluidCheckout_Plugin_Bootstrap_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: Plugin bootstrap loads dependency and feature classes.
	 */
	public function test_plugin_bootstrap_loads_feature_classes() {
		$this->assertTrue( class_exists( 'WooCommerce' ) );
		$this->assertTrue( class_exists( 'FluidCheckout' ) );
	}
}

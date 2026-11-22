<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: plugin bootstrap.
 */
class FluidCheckout_Plugin_Bootstrap_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Mark Fluid Checkout PRO as active.
	 *
	 * @param   array  $plugins  Active plugins.
	 */
	public function filter_activate_pro_plugin( $plugins ) {
		$plugins[] = 'fluid-checkout-pro/fluid-checkout-pro.php';

		return $plugins;
	}

	/**
	 * Mark Fluid Checkout PRO as inactive.
	 *
	 * @param   array  $plugins  Active plugins.
	 */
	public function filter_deactivate_pro_plugin( $plugins ) {
		return array_values( array_filter( $plugins, function( $plugin ) {
			return 'fluid-checkout-pro/fluid-checkout-pro.php' !== $plugin;
		} ) );
	}



	/**
	 * Test: Plugin bootstrap loads dependency and feature classes.
	 */
	public function test_plugin_bootstrap_loads_feature_classes() {
		$this->assertTrue( class_exists( 'WooCommerce' ) );
		$this->assertTrue( class_exists( 'FluidCheckout' ) );
	}



	/**
	 * Test: The PRO features load by default.
	 */
	public function test_should_load_pro_features_is_true_by_default() {
		$this->assertTrue( FluidCheckout::instance()->should_load_pro_features() );
	}

	/**
	 * Test: The PRO features do not load when the `fc_should_load_pro_features` filter returns `false`.
	 */
	public function test_should_load_pro_features_is_false_when_filter_returns_false() {
		add_filter( 'fc_should_load_pro_features', '__return_false' );

		$should_load = FluidCheckout::instance()->should_load_pro_features();

		remove_filter( 'fc_should_load_pro_features', '__return_false' );

		$this->assertFalse( $should_load );
	}

	/**
	 * Test: The PRO features load when the `fc_should_load_pro_features` filter returns `null`, as only `false` turns them off.
	 */
	public function test_should_load_pro_features_is_true_when_filter_returns_null() {
		add_filter( 'fc_should_load_pro_features', '__return_null' );

		$should_load = FluidCheckout::instance()->should_load_pro_features();

		remove_filter( 'fc_should_load_pro_features', '__return_null' );

		$this->assertTrue( $should_load );
	}

	/**
	 * Test: PRO is active when it is in the active plugins and its features load.
	 */
	public function test_is_pro_activated_is_true_when_pro_active() {
		add_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$is_pro_activated = FluidCheckout::instance()->is_pro_activated();

		remove_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$this->assertTrue( $is_pro_activated );
	}

	/**
	 * Test: PRO is not active when the `fc_should_load_pro_features` filter returns `false`.
	 */
	public function test_is_pro_activated_is_false_when_filter_returns_false() {
		add_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );
		add_filter( 'fc_should_load_pro_features', '__return_false' );

		$is_pro_activated = FluidCheckout::instance()->is_pro_activated();

		remove_filter( 'fc_should_load_pro_features', '__return_false' );
		remove_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$this->assertFalse( $is_pro_activated );
	}

	/**
	 * Test: PRO is not active when it is not in the active plugins.
	 */
	public function test_is_pro_activated_is_false_when_pro_inactive() {
		add_filter( 'option_active_plugins', array( $this, 'filter_deactivate_pro_plugin' ) );

		$is_pro_activated = FluidCheckout::instance()->is_pro_activated();

		remove_filter( 'option_active_plugins', array( $this, 'filter_deactivate_pro_plugin' ) );

		$this->assertFalse( $is_pro_activated );
	}
}

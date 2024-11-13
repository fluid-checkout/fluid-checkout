<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: FluidCheckout_DesignTemplates class.
 */
class FluidCheckout_DesignTemplates_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: get_design_template_options().
	 */
	public function test_get_design_template_options() {
		// Check that class exists
		$this->assertTrue( doing_action( 'after_setup_theme' ) );
		$this->assertTrue( class_exists( 'FluidCheckout_DesignTemplates' ) );
	}
}

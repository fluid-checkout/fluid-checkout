<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: guided setup slug progress storage.
 */
class FluidCheckout_Guided_Setup_Progress_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Clear progress option before each test.
	 */
	public function setUpInstance() : void {
		delete_option( FluidCheckout_Admin_GuidedSetup::PROGRESS_OPTION );

		// Transaction rollback leaves options in object cache; clear them explicitly.
		wp_cache_delete( FluidCheckout_Admin_GuidedSetup::PROGRESS_OPTION, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Test: guided setup classes load and the Lite concrete instance is available.
	 */
	public function test_guided_setup_classes_load() {
		$this->assertTrue( class_exists( 'FluidCheckout_Admin_GuidedSetup' ) );
		$this->assertTrue( class_exists( 'FluidCheckout_Admin_GuidedSetup_FluidCheckout' ) );
		$this->assertInstanceOf(
			'FluidCheckout_Admin_GuidedSetup_FluidCheckout',
			FluidCheckout_Admin_GuidedSetup_FluidCheckout::instance()
		);
	}

	/**
	 * Test: empty slug is rejected and returns empty defaults.
	 */
	public function test_empty_slug_is_rejected() {
		$this->assertSame( '', FluidCheckout_Admin_GuidedSetup::sanitize_guided_setup_id( '' ) );
		$this->assertSame( '', FluidCheckout_Admin_GuidedSetup::sanitize_guided_setup_id( '!!!' ) );
		$this->assertFalse( FluidCheckout_Admin_GuidedSetup::set_current_step_for_slug( '', 'welcome' ) );
		$this->assertFalse( FluidCheckout_Admin_GuidedSetup::clear_progress_for_slug( '' ) );

		$progress = FluidCheckout_Admin_GuidedSetup::get_progress_for_slug( '' );
		$this->assertSame( '', $progress['current_step'] );
		$this->assertSame( array(), $progress['sub_step_progress'] );
	}

	/**
	 * Test: set and get current step for a slug.
	 */
	public function test_set_and_get_current_step_for_slug() {
		$slug = 'fc_lite_checkout';

		$this->assertTrue( FluidCheckout_Admin_GuidedSetup::set_current_step_for_slug( $slug, 'explore_checkout' ) );
		$this->assertSame( 'explore_checkout', FluidCheckout_Admin_GuidedSetup::get_current_step_for_slug( $slug ) );
	}

	/**
	 * Test: sub-step progress is stored and retrieved per slug.
	 */
	public function test_sub_step_progress_for_slug() {
		$slug     = 'fc_lite_checkout';
		$progress = array(
			'example_step' => array(
				'sub_a' => array( 'completed' => true ),
			),
		);

		$this->assertTrue( FluidCheckout_Admin_GuidedSetup::set_sub_step_progress_for_slug( $slug, $progress ) );
		$this->assertSame( $progress, FluidCheckout_Admin_GuidedSetup::get_sub_step_progress_for_slug( $slug ) );
	}

	/**
	 * Test: progress is isolated between slugs.
	 */
	public function test_progress_is_isolated_between_slugs() {
		FluidCheckout_Admin_GuidedSetup::set_current_step_for_slug( 'fc_lite_checkout', 'welcome' );
		FluidCheckout_Admin_GuidedSetup::set_current_step_for_slug( 'fc_gaa', 'get_api_key' );

		$this->assertSame( 'welcome', FluidCheckout_Admin_GuidedSetup::get_current_step_for_slug( 'fc_lite_checkout' ) );
		$this->assertSame( 'get_api_key', FluidCheckout_Admin_GuidedSetup::get_current_step_for_slug( 'fc_gaa' ) );

		FluidCheckout_Admin_GuidedSetup::clear_progress_for_slug( 'fc_lite_checkout' );

		$this->assertSame( '', FluidCheckout_Admin_GuidedSetup::get_current_step_for_slug( 'fc_lite_checkout' ) );
		$this->assertSame( 'get_api_key', FluidCheckout_Admin_GuidedSetup::get_current_step_for_slug( 'fc_gaa' ) );
	}

	/**
	 * Test: clear removes the slug entry from the shared option.
	 */
	public function test_clear_progress_for_slug() {
		$slug = 'fc_lite_checkout';

		FluidCheckout_Admin_GuidedSetup::set_current_step_for_slug( $slug, 'wrap_up' );
		FluidCheckout_Admin_GuidedSetup::clear_progress_for_slug( $slug );

		$all = FluidCheckout_Admin_GuidedSetup::get_all_progress();
		$this->assertArrayNotHasKey( $slug, $all );
	}
}

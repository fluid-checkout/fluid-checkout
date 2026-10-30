<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit tests: Fluid Checkout checkout guided setup wizard.
 */
class FluidCheckout_Guided_Setup_FluidCheckout_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * @var FluidCheckout_Admin_GuidedSetup_FluidCheckout
	 */
	private $wizard;



	/**
	 * Reset progress and wizard instance before each test.
	 */
	public function setUpInstance() : void {
		delete_option( FluidCheckout_Admin_GuidedSetup::PROGRESS_OPTION );

		wp_cache_delete( FluidCheckout_Admin_GuidedSetup::PROGRESS_OPTION, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		$this->wizard = FluidCheckout_Admin_GuidedSetup_FluidCheckout::instance();
	}



	/**
	 * Test: admin_enqueue_scripts is wired to maybe_enqueue_assets (valid callback).
	 */
	public function test_maybe_enqueue_assets_hook_is_registered() {
		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $this->wizard, 'maybe_enqueue_assets' ) )
		);
		$this->assertTrue( is_callable( array( $this->wizard, 'maybe_enqueue_assets' ) ) );
	}



	/**
	 * Test: admin_head and footer hooks use valid callbacks on the abstract base.
	 */
	public function test_shared_admin_hooks_use_valid_callbacks() {
		$this->assertNotFalse(
			has_action( 'admin_head', array( $this->wizard, 'maybe_output_settings_inline_script' ) )
		);
		$this->assertTrue( is_callable( array( $this->wizard, 'maybe_output_settings_inline_script' ) ) );
		$this->assertTrue( is_callable( array( $this->wizard, 'clear_guided_setup_admin_footer' ) ) );
	}



	/**
	 * Test: maybe_enqueue_assets ignores unrelated admin screen hooks.
	 */
	public function test_maybe_enqueue_assets_bails_on_unrelated_hook_suffix() {
		$this->wizard->page_hook_suffix = 'woocommerce_page_wc-settings';

		$this->wizard->maybe_enqueue_assets( 'index.php' );

		$this->assertFalse( wp_script_is( 'fc-guided-setup-fluid-checkout', 'enqueued' ) );
	}



	/**
	 * Test: Phase 1 checkout wizard step slugs and order.
	 */
	public function test_checkout_wizard_step_slugs() {
		$steps = array_keys( $this->wizard->get_steps() );

		$this->assertSame(
			array(
				'about_store',
				'product_types',
				'recommended_setup',
				'wrap_up',
			),
			$steps
		);
	}



	/**
	 * Test: guided setup id is fc_checkout.
	 */
	public function test_checkout_guided_setup_id() {
		$reflection = new ReflectionClass( $this->wizard );
		$method     = $reflection->getMethod( 'get_guided_setup_id' );
		$method->setAccessible( true );

		$this->assertSame( 'fc_checkout', $method->invoke( $this->wizard ) );
	}



	/**
	 * Test: telemetry sanitizer removes email when newsletter is off.
	 */
	public function test_sanitize_guided_setup_progress_for_telemetry_omits_email() {
		$progress = array(
			'answers' => array(
				'newsletter_opt_in' => false,
				'email'             => 'merchant@example.com',
			),
		);

		$sanitized = $this->wizard->sanitize_guided_setup_progress_for_telemetry( $progress, 'fc_checkout' );

		$this->assertArrayNotHasKey( 'email', $sanitized['answers'] );
	}



	/**
	 * Test: placeholder recommended_setup and wrap_up steps are registered for AJAX continue.
	 */
	public function test_recommended_setup_step_ajax_hook_is_registered() {
		$this->assertNotFalse(
			has_action( 'wp_ajax_fc_process_guided_setup_step_recommended_setup', array( $this->wizard, 'ajax_process_step_recommended_setup' ) )
		);
		$this->assertTrue( is_callable( array( $this->wizard, 'ajax_process_step_recommended_setup' ) ) );
	}



	/**
	 * Test: telemetry sanitizer keeps email when newsletter is on.
	 */
	public function test_sanitize_guided_setup_progress_for_telemetry_keeps_email_when_opted_in() {
		$progress = array(
			'answers' => array(
				'newsletter_opt_in' => true,
				'email'             => 'merchant@example.com',
			),
		);

		$sanitized = $this->wizard->sanitize_guided_setup_progress_for_telemetry( $progress, 'fc_checkout' );

		$this->assertSame( 'merchant@example.com', $sanitized['answers']['email'] );
	}

}

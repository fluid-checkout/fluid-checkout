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
	 * Test: telemetry sanitizer never includes the merchant email.
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
	 * Test: telemetry sanitizer omits email even when newsletter is opted in.
	 */
	public function test_sanitize_guided_setup_progress_for_telemetry_omits_email_when_newsletter_opted_in() {
		$progress = array(
			'answers' => array(
				'newsletter_opt_in' => true,
				'email'             => 'merchant@example.com',
			),
		);

		$sanitized = $this->wizard->sanitize_guided_setup_progress_for_telemetry( $progress, 'fc_checkout' );

		$this->assertArrayNotHasKey( 'email', $sanitized['answers'] );
		$this->assertTrue( $sanitized['answers']['newsletter_opt_in'] );
	}



	/**
	 * Test: newsletter signup posts to the public fc-licenses API, not via telemetry.
	 */
	public function test_newsletter_signup_posts_to_licenses_api() {
		$captured = null;

		add_filter(
			'pre_http_request',
			function( $preempt, $args, $url ) use ( &$captured ) {
				$captured = array(
					'url'  => $url,
					'args' => $args,
				);

				return array(
					'headers'  => array(),
					'body'     => wp_json_encode( array( 'data' => array( 'ok' => true ) ) ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);

		FluidCheckout_Admin_GuidedSetup::set_progress_for_slug(
			'fc_checkout',
			array(
				'answers' => array(
					'newsletter_opt_in' => true,
					'email'             => 'merchant@example.com',
				),
			)
		);

		$method = new ReflectionMethod( FluidCheckout_Admin_GuidedSetup_FluidCheckout::class, 'maybe_subscribe_newsletter' );
		$method->setAccessible( true );
		$method->invoke( $this->wizard );

		$this->assertIsArray( $captured );
		$this->assertSame( 'https://fluidcheckout.com/wp-json/fc-licenses/v1/newsletter/subscribe', $captured['url'] );

		$body = json_decode( $captured['args']['body'], true );
		$this->assertIsArray( $body );
		$this->assertSame( 'merchant@example.com', $body['email'] );
		$this->assertSame( 'guided_setup', $body['source'] );
	}



	/**
	 * Test: recommended_setup persists Lite exclusions and applies remaining profile values.
	 *
	 * Unchecked Lite toggles are stored as user_overrides and skipped on apply.
	 * PRO recommendation keys are never written from the Lite apply path.
	 */
	public function test_recommended_setup_saves_overrides_and_applies_settings() {
		$settings = FluidCheckout_Settings::instance();
		$engine   = FluidCheckout_Guided_Setup_Recommendations::instance();

		$answers = FluidCheckout_Guided_Setup_Checkout_Data::get_default_answers(
			array(
				'business_industry' => 'fashion-apparel-accessories',
				'customer_audience' => 'dtc',
				'customer_location' => 'domestic',
				'product_types'     => array( 'physical' ),
				'delivery_methods'  => array( 'ship_to_address' ),
			)
		);

		// Seed baseline profile values opposite to what recommendations will apply
		$settings->update_active_profile_values(
			array(
				'fc_enable_checkout_hide_optional_fields' => 'no',
				'fc_show_shipping_section_highlighted'    => 'no',
				'fc_gaa_enabled'                          => 'no',
			)
		);
		$settings->invalidate_cache();

		FluidCheckout_Admin_GuidedSetup::set_progress_for_slug(
			'fc_checkout',
			array(
				'answers'         => $answers,
				'recommendations' => array(
					'answers_hash'   => $engine->get_answers_hash( $answers ),
					'profile_values' => array(
						'fc_enable_checkout_hide_optional_fields' => 'yes',
						'fc_show_shipping_section_highlighted'    => 'yes',
						'fc_gaa_enabled'                          => 'yes',
					),
					'recommended'    => array(
						array(
							'option_key'  => 'fc_enable_checkout_hide_optional_fields',
							'value'       => 'yes',
							'checked'     => true,
							'is_pro'      => false,
							'title'       => 'Hide optional fields',
							'description' => '',
						),
						array(
							'option_key'  => 'fc_show_shipping_section_highlighted',
							'value'       => 'yes',
							'checked'     => true,
							'is_pro'      => false,
							'title'       => 'Highlight shipping',
							'description' => '',
						),
						array(
							'option_key'  => 'fc_gaa_enabled',
							'value'       => 'yes',
							'checked'     => true,
							'is_pro'      => true,
							'title'       => 'Google Address Autocomplete',
							'description' => '',
						),
					),
					'user_overrides' => array(),
				),
			)
		);

		$reflection     = new ReflectionClass( $this->wizard );
		$save_overrides = $reflection->getMethod( 'save_recommended_setup_user_overrides' );
		$apply          = $reflection->getMethod( 'maybe_apply_recommendations_on_finish' );
		$save_overrides->setAccessible( true );
		$apply->setAccessible( true );

		// Keep hide-optional checked; leave highlight-shipping unchecked (excluded)
		$save_overrides->invoke(
			$this->wizard,
			array(
				'recommendation_toggles' => array( 'fc_enable_checkout_hide_optional_fields' ),
			)
		);

		$progress = FluidCheckout_Admin_GuidedSetup::get_progress_for_slug( 'fc_checkout' );

		$this->assertSame(
			array( 'fc_show_shipping_section_highlighted' ),
			$progress['recommendations']['user_overrides']
		);

		$apply->invoke( $this->wizard );
		$settings->invalidate_cache();

		$this->assertSame( 'yes', $settings->get_option( 'fc_enable_checkout_hide_optional_fields' ) );
		$this->assertSame( 'no', $settings->get_option( 'fc_show_shipping_section_highlighted' ) );
		$this->assertSame( 'no', $settings->get_option( 'fc_gaa_enabled' ) );
	}

}

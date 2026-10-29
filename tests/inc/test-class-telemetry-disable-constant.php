<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: FC_DISABLE_TELEMETRY turns usage tracking off without changing stored settings.
 */
class FluidCheckout_Telemetry_Disable_Constant_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: the helper is false when the constant is not defined.
	 */
	public function test_is_disabled_by_constant_is_false_when_unset() {
		$this->skip_if_constant_already_defined();

		$api_url = FluidCheckout::get_telemetry_api_url();
		$config  = FC_Telemetry_Client::get_telemetry_config( $api_url );

		$this->assertFalse( FC_Telemetry_Client::is_disabled_by_constant() );
		$this->assertSame( 'FC_DISABLE_TELEMETRY', FC_Telemetry_Client::DISABLE_CONSTANT );
		$this->assertSame( 'FC_DISABLE_TELEMETRY', $config['disable_constant'] );
		$this->assertSame( 'FC_DISABLE_TELEMETRY', FC_Telemetry_Client::get_disable_constant_name( $api_url ) );
		$this->assertStringContainsString( 'FC_DISABLE_TELEMETRY', FC_Telemetry_Client::get_disabled_by_constant_message() );
	}

	/**
	 * Test: a schedule can be created while the constant is unset.
	 */
	public function test_schedule_is_created_when_constant_is_unset() {
		$this->skip_if_constant_already_defined();

		$hook = $this->get_cron_hook();
		wp_clear_scheduled_hook( $hook );

		FC_Telemetry_Client::schedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );

		$this->assertNotFalse( wp_next_scheduled( $hook ) );

		wp_clear_scheduled_hook( $hook );
	}

	/**
	 * Test: boolean true blocks sending and scheduling, hides the notice, and keeps stored options.
	 *
	 * Keep this last. Defining the constant cannot be undone in this process.
	 */
	public function test_true_constant_blocks_sending_and_keeps_stored_options() {
		$this->skip_if_constant_already_defined();

		$settings = FluidCheckout_Settings::instance();
		$hook     = $this->get_cron_hook();
		$groups   = array( 'basic_environment', 'woocommerce_sales_metrics' );

		$this->make_telemetry_notice_eligible();
		$this->assertTrue( FluidCheckout_AdminNotices_Telemetry::instance()->should_show_telemetry_prompt() );

		$settings->update_raw_option( 'fc_telemetry_enabled', 'yes', false );
		$settings->update_raw_option( 'fc_telemetry_data_groups', $groups, false );
		$settings->update_raw_option( 'fc_telemetry_last_sent', 12345, false );

		wp_clear_scheduled_hook( $hook );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', $hook );
		$this->assertNotFalse( wp_next_scheduled( $hook ) );

		define( 'FC_DISABLE_TELEMETRY', true );

		$this->assertTrue( FC_Telemetry_Client::is_disabled_by_constant() );
		$this->assertFalse( FluidCheckout_AdminNotices_Telemetry::instance()->should_show_telemetry_prompt() );

		$result = FC_Telemetry_Client::send_telemetry_now( $groups, true, false, FluidCheckout::get_telemetry_api_url() );

		$this->assertTrue( is_array( $result ) );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'disabled_by_constant', $result['error_code'] );
		$this->assertSame( 'yes', $settings->get_raw_option( 'fc_telemetry_enabled' ) );
		$this->assertSame( $groups, $settings->get_raw_option( 'fc_telemetry_data_groups' ) );
		$this->assertSame( 12345, (int) $settings->get_raw_option( 'fc_telemetry_last_sent' ) );

		FC_Telemetry_Client::maybe_send_telemetry( FluidCheckout::get_telemetry_api_url() );
		$this->assertSame( 12345, (int) $settings->get_raw_option( 'fc_telemetry_last_sent' ) );

		$direct_send = FC_Telemetry_Client::send_telemetry( array( 'site_domain' => 'example.com' ), FluidCheckout::get_telemetry_api_url() );

		$this->assertInstanceOf( 'WP_Error', $direct_send );
		$this->assertSame( 'fc_telemetry_disabled_by_constant', $direct_send->get_error_code() );

		FC_Telemetry_Client::maybe_schedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
		$this->assertFalse( wp_next_scheduled( $hook ) );

		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', $hook );
		FC_Telemetry_Client::run_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
		$this->assertFalse( wp_next_scheduled( $hook ) );

		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', $hook );
		FC_Telemetry_Client::schedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
		$this->assertFalse( wp_next_scheduled( $hook ) );

		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', $hook );
		FluidCheckout_Settings_Tools::sync_telemetry_cron();
		$this->assertFalse( wp_next_scheduled( $hook ) );

		$settings->update_raw_option( 'fc_telemetry_enabled', 'no', false );
		FluidCheckout_AdminNotices_Telemetry::instance()->enable_telemetry();
		$this->assertSame( 'no', $settings->get_raw_option( 'fc_telemetry_enabled' ) );

		$_GET['fc_action'] = 'enable_telemetry';
		$_GET['_wpnonce']  = wp_create_nonce( FluidCheckout_AdminNotices_Telemetry::ENABLE_NONCE_ACTION );
		FluidCheckout_AdminNotices_Telemetry::instance()->maybe_handle_enable_request();
		$this->assertSame( 'no', $settings->get_raw_option( 'fc_telemetry_enabled' ) );
		$this->assertFalse( (bool) get_option( 'fc_dismissed_notice_telemetry', false ) );

		FluidCheckout_Activation::on_activation();
		$this->assertSame( 'no', $settings->get_raw_option( 'fc_telemetry_enabled' ) );
		$this->assertSame( $groups, $settings->get_raw_option( 'fc_telemetry_data_groups' ) );
		$this->assertFalse( wp_next_scheduled( $hook ) );
	}

	public function tearDownInstance() : void {
		wp_set_current_user( 0 );
		unset( $_GET['fc_action'], $_GET['_wpnonce'] );
	}

	private function skip_if_constant_already_defined() {
		if ( ! defined( 'FC_DISABLE_TELEMETRY' ) ) { return; }

		$this->markTestSkipped( 'FC_DISABLE_TELEMETRY is already defined in this process.' );
	}

	private function get_cron_hook() {
		$config = FC_Telemetry_Client::get_telemetry_config( FluidCheckout::get_telemetry_api_url() );

		return $config['cron_hook'];
	}

	private function make_telemetry_notice_eligible() {
		$user_id = wp_insert_user(
			array(
				'user_login' => 'fc_telemetry_constant_' . wp_generate_password( 8, false ),
				'user_email' => 'fc_telemetry_constant_' . wp_generate_password( 8, false ) . '@example.com',
				'user_pass'  => wp_generate_password(),
				'role'       => 'administrator',
			)
		);

		$this->assertFalse( is_wp_error( $user_id ) );
		wp_set_current_user( $user_id );

		delete_option( 'fc_dismissed_notice_telemetry' );

		$settings = FluidCheckout_Settings::instance();
		$settings->update_raw_option( 'fc_telemetry_enabled', 'no', false );
		$settings->update_active_profile_values(
			array(
				'fc_telemetry_enabled' => 'no',
			)
		);
		$settings->invalidate_cache();
		$settings->update_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, time() - ( 3 * WEEK_IN_SECONDS ), false );
	}

}

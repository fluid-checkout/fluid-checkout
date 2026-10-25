<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: database migration risk steps and runner.
 */
class FluidCheckout_Admin_DB_Migrations_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Temporary migration fixture directories created during tests.
	 *
	 * @var array
	 */
	private $fixture_dirs = array();

	/**
	 * Reset migration state before each test.
	 */
	public function setUpInstance() : void {
		$option_keys = array(
			'fc_db_version',
			FluidCheckout_AdminDBMigrations::STEP_PROGRESS_OPTION,
			'fc_show_db_update_notice',
			'fc_test_migration_99_low',
			'fc_test_migration_99_high',
			'fc_test_migration_99_1_legacy',
			'fc_test_migration_99_2_low',
			'fc_test_migration_99_2_medium',
			'fc_test_migration_99_2_high',
		);

		foreach ( $option_keys as $option_key ) {
			delete_option( $option_key );
			wp_cache_delete( $option_key, 'options' );
		}

		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		remove_all_filters( 'fc_available_db_migrations' );
	}

	/**
	 * Remove temporary fixtures after each test.
	 */
	public function tearDownInstance() : void {
		remove_all_filters( 'fc_available_db_migrations' );

		foreach ( $this->fixture_dirs as $dir ) {
			$files = glob( trailingslashit( $dir ) . '*.php' );
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				}
			}
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		$this->fixture_dirs = array();
	}



	/**
	 * Write a temporary migration fixture named `migration-{version}.php` and return its path.
	 *
	 * @param   string  $version  Migration version string.
	 * @param   string  $php      Class body PHP (without opening tag).
	 * @return  string
	 */
	private function write_migration_fixture( $version, $php ) {
		$dir = sys_get_temp_dir() . '/fc-migrations-' . wp_generate_password( 8, false );
		mkdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		$this->fixture_dirs[] = $dir;

		$file = trailingslashit( $dir ) . 'migration-' . $version . '.php';
		file_put_contents( $file, "<?php\n" . $php ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return $file;
	}

	/**
	 * Limit available migrations to fixture files only.
	 *
	 * @param   array  $files  Fixture absolute paths.
	 */
	private function use_fixture_migrations( $files ) {
		add_filter( 'fc_available_db_migrations', function() use ( $files ) {
			return $files;
		} );
	}



	/**
	 * Test: low steps bump fc_db_version only when the whole version is complete.
	 */
	public function test_partial_progress_keeps_previous_db_version_until_all_steps_complete() {
		$file = $this->write_migration_fixture(
			'99.0.0',
			'class FluidCheckout_Migration_99_0_0 extends FluidCheckout {
				public function get_db_version() { return "99.0.0"; }
				public function get_migration_steps() {
					return array(
						array(
							"id" => "step_low",
							"description" => "Low step",
							"risk" => FluidCheckout_AdminDBMigrations::RISK_LOW,
							"callback" => array( $this, "run_low" ),
						),
						array(
							"id" => "step_high",
							"description" => "High step",
							"risk" => FluidCheckout_AdminDBMigrations::RISK_HIGH,
							"callback" => array( $this, "run_high" ),
						),
					);
				}
				public function run_low() { update_option( "fc_test_migration_99_low", "1" ); }
				public function run_high() { update_option( "fc_test_migration_99_high", "1" ); }
			}
			return FluidCheckout_Migration_99_0_0::instance();'
		);

		update_option( 'fc_db_version', '4.2.5' );
		$this->use_fixture_migrations( array( $file ) );

		$sut = FluidCheckout_AdminDBMigrations::instance();
		$ran = $sut->run_pending_steps( array( FluidCheckout_AdminDBMigrations::RISK_LOW ) );

		$this->assertSame( 1, $ran );
		$this->assertSame( '4.2.5', $sut->get_db_version() );
		$this->assertSame( array( 'step_low' ), $sut->get_completed_step_ids( '99.0.0' ) );
		$this->assertTrue( $sut->has_migrations_requiring_confirmation() );
		$this->assertSame(
			array( 'High step' ),
			$sut->get_migrations_descriptions( array( FluidCheckout_AdminDBMigrations::RISK_MEDIUM, FluidCheckout_AdminDBMigrations::RISK_HIGH ) )
		);

		$ran_all = $sut->run_pending_steps( array(
			FluidCheckout_AdminDBMigrations::RISK_LOW,
			FluidCheckout_AdminDBMigrations::RISK_MEDIUM,
			FluidCheckout_AdminDBMigrations::RISK_HIGH,
		) );

		$this->assertSame( 1, $ran_all );
		$this->assertSame( '99.0.0', $sut->get_db_version() );
		$this->assertSame( array(), $sut->get_completed_step_ids( '99.0.0' ) );
		$this->assertFalse( $sut->has_migrations_requiring_confirmation() );
	}

	/**
	 * Test: migration without get_migration_steps is skipped.
	 */
	public function test_migration_without_steps_is_skipped() {
		$file = $this->write_migration_fixture(
			'99.1.0',
			'class FluidCheckout_Migration_99_1_0 extends FluidCheckout {
				public function get_db_version() { return "99.1.0"; }
				public function migrate() { update_option( "fc_test_migration_99_1_legacy", "1" ); }
			}
			return FluidCheckout_Migration_99_1_0::instance();'
		);

		update_option( 'fc_db_version', '4.2.5' );
		$this->use_fixture_migrations( array( $file ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		$sut = FluidCheckout_AdminDBMigrations::instance();
		$ran = $sut->run_pending_steps( array(
			FluidCheckout_AdminDBMigrations::RISK_LOW,
			FluidCheckout_AdminDBMigrations::RISK_MEDIUM,
			FluidCheckout_AdminDBMigrations::RISK_HIGH,
		) );

		$this->assertSame( 0, $ran );
		$this->assertSame( '4.2.5', $sut->get_db_version() );
		$this->assertFalse( get_option( 'fc_test_migration_99_1_legacy', false ) );
		$this->assertNull( $sut->get_migration_steps_for_file( $file ) );

		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Test: first-activation risk filter runs low and medium, leaves high pending.
	 */
	public function test_first_activation_runs_low_and_medium_only() {
		$file = $this->write_migration_fixture(
			'99.2.0',
			'class FluidCheckout_Migration_99_2_0 extends FluidCheckout {
				public function get_db_version() { return "99.2.0"; }
				public function get_migration_steps() {
					return array(
						array(
							"id" => "step_low",
							"description" => "Low",
							"risk" => FluidCheckout_AdminDBMigrations::RISK_LOW,
							"callback" => array( $this, "run_low" ),
						),
						array(
							"id" => "step_medium",
							"description" => "Medium",
							"risk" => FluidCheckout_AdminDBMigrations::RISK_MEDIUM,
							"callback" => array( $this, "run_medium" ),
						),
						array(
							"id" => "step_high",
							"description" => "High",
							"risk" => FluidCheckout_AdminDBMigrations::RISK_HIGH,
							"callback" => array( $this, "run_high" ),
						),
					);
				}
				public function run_low() { update_option( "fc_test_migration_99_2_low", "1" ); }
				public function run_medium() { update_option( "fc_test_migration_99_2_medium", "1" ); }
				public function run_high() { update_option( "fc_test_migration_99_2_high", "1" ); }
			}
			return FluidCheckout_Migration_99_2_0::instance();'
		);

		update_option( 'fc_db_version', '0.0.0' );
		$this->use_fixture_migrations( array( $file ) );

		$sut = FluidCheckout_AdminDBMigrations::instance();
		$ran = $sut->run_pending_steps( array(
			FluidCheckout_AdminDBMigrations::RISK_LOW,
			FluidCheckout_AdminDBMigrations::RISK_MEDIUM,
		) );

		$this->assertSame( 2, $ran );
		$this->assertSame( '1', get_option( 'fc_test_migration_99_2_low' ) );
		$this->assertSame( '1', get_option( 'fc_test_migration_99_2_medium' ) );
		$this->assertFalse( get_option( 'fc_test_migration_99_2_high', false ) );
		$this->assertSame( '0.0.0', $sut->get_db_version() );
		$this->assertSame( FluidCheckout_AdminDBMigrations::RISK_HIGH, $sut->get_highest_pending_risk() );
		$this->assertTrue( $sut->has_migrations_requiring_confirmation() );
	}

	/**
	 * Test: guided setup admin screen detection and notice bail.
	 */
	public function test_guided_setup_hides_fc_admin_notices() {
		FluidCheckout_Admin_GuidedSetup::register_guided_setup_page_slug( 'fc-test-guided-setup' );
		$_GET[ 'page' ] = 'fc-test-guided-setup';

		$this->assertTrue( FluidCheckout_Admin_GuidedSetup::is_guided_setup_admin_screen() );

		ob_start();
		FluidCheckout_AdminNotices::instance()->display_notices();
		$output = ob_get_clean();

		$this->assertSame( '', $output );

		unset( $_GET[ 'page' ] );
	}

	/**
	 * Test: settings screen keeps FC notices registered after strip helper.
	 */
	public function test_settings_screen_re_registers_fc_notices_only() {
		add_action( 'admin_notices', 'fc_test_third_party_admin_notice_callback', 10 );
		$this->assertNotFalse( has_action( 'admin_notices', 'fc_test_third_party_admin_notice_callback' ) );

		FluidCheckout_Admin::strip_non_fc_admin_notices_on_load( true );

		$this->assertFalse( has_action( 'admin_notices', 'fc_test_third_party_admin_notice_callback' ) );
		$this->assertNotFalse( has_action( 'admin_notices', array( FluidCheckout_AdminNotices::instance(), 'display_notices' ) ) );
	}
}

/**
 * Dummy third-party admin notice callback for strip tests.
 */
function fc_test_third_party_admin_notice_callback() {}

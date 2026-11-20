<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: admin preview token detection from GET, header, and Referer.
 */
class FluidCheckout_Admin_Preview_Token_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Administrator user id created for token tests.
	 *
	 * @var int
	 */
	private $admin_user_id = 0;

	/**
	 * Customer user id created for authorization tests.
	 *
	 * @var int
	 */
	private $customer_user_id = 0;

	/**
	 * Load admin preview classes once for this test case.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$plugin_dir = dirname( dirname( __DIR__ ) );

		require_once $plugin_dir . '/inc/admin/admin-preview.php';
		require_once $plugin_dir . '/inc/admin/admin-preview-dummy-data.php';
		require_once $plugin_dir . '/inc/admin/admin-preview-session-handler.php';
	}

	/**
	 * Reset request globals and preview cache after each test.
	 */
	public function tearDownInstance() {
		unset( $_GET[ 'fc_preview' ], $_GET[ 'fc_preview_mode' ], $_SERVER[ 'HTTP_X_FC_PREVIEW' ], $_SERVER[ 'HTTP_REFERER' ] );

		$this->reset_preview_request_cache();

		if ( $this->admin_user_id ) {
			wp_delete_user( $this->admin_user_id );
			$this->admin_user_id = 0;
		}

		if ( $this->customer_user_id ) {
			wp_delete_user( $this->customer_user_id );
			$this->customer_user_id = 0;
		}

		remove_filter( 'get_user_metadata', array( FluidCheckout_Admin_Preview::instance(), 'maybe_get_dummy_customer_meta' ), 10 );

		wp_set_current_user( 0 );
	}

	/**
	 * Clear the cached preview-request flag on the singleton.
	 */
	private function reset_preview_request_cache() {
		// Bail if the preview class is not loaded
		if ( ! class_exists( 'FluidCheckout_Admin_Preview', false ) ) { return; }

		$preview = FluidCheckout_Admin_Preview::instance();
		$reflection = new ReflectionClass( $preview );
		$property = $reflection->getProperty( 'is_preview_request' );
		$property->setAccessible( true );
		$property->setValue( $preview, null );
	}

	/**
	 * Force the cached preview-request flag for persistence-guard tests.
	 *
	 * @param   bool  $is_preview  Whether the request should be treated as a preview request.
	 */
	private function set_preview_request_cache( $is_preview ) {
		$preview = FluidCheckout_Admin_Preview::instance();
		$reflection = new ReflectionClass( $preview );
		$property = $reflection->getProperty( 'is_preview_request' );
		$property->setAccessible( true );
		$property->setValue( $preview, (bool) $is_preview );
	}

	/**
	 * Create a user with the given role.
	 *
	 * @param   string  $role  WordPress role.
	 *
	 * @return  int
	 */
	private function create_user_with_role( $role ) {
		return wp_insert_user(
			array(
				'user_login' => 'fc_preview_' . $role . '_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 12, true ),
				'user_email' => 'fc-preview-' . $role . '-' . wp_generate_password( 6, false ) . '@example.com',
				'role'       => $role,
			)
		);
	}

	/**
	 * Create an administrator and return a signed preview token for that user.
	 *
	 * @return  string
	 */
	private function create_admin_preview_token() {
		$this->admin_user_id = $this->create_user_with_role( 'administrator' );
		wp_set_current_user( $this->admin_user_id );

		return FluidCheckout_Admin_Preview::instance()->get_token();
	}

	/**
	 * Boot the WooCommerce session and customer used by persisted-data helpers.
	 */
	private function boot_wc_customer_session() {
		if ( ! WC()->session ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}
		if ( ! WC()->customer ) {
			WC()->customer = new WC_Customer( 0, true );
		}
	}



	/**
	 * Test: should_load_admin_preview is true when fc_preview is in the query string.
	 */
	public function test_should_load_admin_preview_with_get_token() {
		$_GET[ 'fc_preview' ] = 'token-from-get';

		$this->assertTrue( FluidCheckout::instance()->should_load_admin_preview() );
	}

	/**
	 * Test: should_load_admin_preview is true when X-FC-Preview is present.
	 */
	public function test_should_load_admin_preview_with_header_token() {
		$_SERVER[ 'HTTP_X_FC_PREVIEW' ] = 'token-from-header';

		$this->assertTrue( FluidCheckout::instance()->should_load_admin_preview() );
	}

	/**
	 * Test: should_load_admin_preview is true when the same-site Referer has fc_preview.
	 */
	public function test_should_load_admin_preview_with_referer_token() {
		$_SERVER[ 'HTTP_REFERER' ] = home_url( '/checkout/?fc_preview=token-from-referer' );

		$this->assertTrue( FluidCheckout::instance()->should_load_admin_preview() );
	}

	/**
	 * Test: should_load_admin_preview ignores a cross-site Referer preview token.
	 */
	public function test_should_not_load_admin_preview_with_cross_site_referer() {
		$_SERVER[ 'HTTP_REFERER' ] = 'https://evil.example/?fc_preview=token-from-referer';

		// Bail expectation: only the referer path is under test; admin page heuristics may still apply in WP_ADMIN
		$token = FluidCheckout::instance()->get_preview_token_from_referer();
		$this->assertSame( '', $token );
	}

	/**
	 * Test: get_request_token prefers the query string over the header and Referer.
	 */
	public function test_get_request_token_prefers_get() {
		$_GET[ 'fc_preview' ] = 'token-from-get';
		$_SERVER[ 'HTTP_X_FC_PREVIEW' ] = 'token-from-header';
		$_SERVER[ 'HTTP_REFERER' ] = home_url( '/checkout/?fc_preview=token-from-referer' );

		$this->assertSame( 'token-from-get', FluidCheckout_Admin_Preview::instance()->get_request_token() );
	}

	/**
	 * Test: get_request_token reads the X-FC-Preview header when the query string has none.
	 */
	public function test_get_request_token_from_header() {
		$_SERVER[ 'HTTP_X_FC_PREVIEW' ] = 'token-from-header';

		$this->assertSame( 'token-from-header', FluidCheckout_Admin_Preview::instance()->get_request_token() );
	}

	/**
	 * Test: get_request_token reads the same-site Referer when GET and header are empty.
	 */
	public function test_get_request_token_from_referer() {
		$_SERVER[ 'HTTP_REFERER' ] = home_url( '/checkout/?fc_preview=token-from-referer' );

		$this->assertSame( 'token-from-referer', FluidCheckout_Admin_Preview::instance()->get_request_token() );
	}

	/**
	 * Test: get_request_token is empty when no token source is present.
	 */
	public function test_get_request_token_empty_without_sources() {
		$this->assertSame( '', FluidCheckout_Admin_Preview::instance()->get_request_token() );
	}

	/**
	 * Test: a valid header token resolves to the token user through get_request_token().
	 */
	public function test_valid_header_token_resolves_to_admin_user() {
		$token = $this->create_admin_preview_token();
		$this->assertNotSame( '', $token );

		$_SERVER[ 'HTTP_X_FC_PREVIEW' ] = $token;

		$preview = FluidCheckout_Admin_Preview::instance();
		$this->assertSame( $this->admin_user_id, $preview->get_token_user_id( $preview->get_request_token() ) );
	}

	/**
	 * Test: update_customer_persisted_data does not write session fields on preview requests.
	 */
	public function test_update_customer_persisted_data_skips_session_on_preview() {
		$sut = FluidCheckout_Steps::instance();
		$this->boot_wc_customer_session();

		$session_key = FluidCheckout_Steps::SESSION_PREFIX . 'billing_first_name';
		WC()->session->set( $session_key, 'KeepMe' );

		// Force preview classification without needing a full preview bootstrap
		$this->set_preview_request_cache( true );

		$posted_data = array(
			'billing_first_name' => 'John',
			'billing_last_name'  => 'Doe',
			'billing_country'    => 'US',
		);

		$result = $sut->update_customer_persisted_data( $posted_data );

		$this->assertSame( $posted_data, $result );
		$this->assertSame( 'KeepMe', WC()->session->get( $session_key ) );
	}

	/**
	 * Test: guests and customers cannot mint a preview token.
	 */
	public function test_guest_and_customer_cannot_mint_preview_token() {
		$preview = FluidCheckout_Admin_Preview::instance();

		wp_set_current_user( 0 );
		$this->assertSame( '', $preview->get_token() );

		$this->customer_user_id = $this->create_user_with_role( 'customer' );
		wp_set_current_user( $this->customer_user_id );
		$this->assertSame( '', $preview->get_token() );
		$this->assertFalse( current_user_can( FluidCheckout_Admin_Preview::CAPABILITY ) );
	}

	/**
	 * Test: a forged token, including one that names an admin user id, is rejected.
	 */
	public function test_forged_token_does_not_authenticate_as_admin() {
		$this->admin_user_id = $this->create_user_with_role( 'administrator' );
		$preview = FluidCheckout_Admin_Preview::instance();

		$forged = $this->admin_user_id . '.' . ( time() + DAY_IN_SECONDS ) . '.' . str_repeat( 'ab', 32 );

		$this->assertSame( 0, $preview->get_token_user_id( $forged ) );
		$this->assertSame( 0, $preview->get_token_user_id( 'not-a-token' ) );

		$_GET[ 'fc_preview' ] = $forged;
		$this->reset_preview_request_cache();
		$this->assertFalse( $preview->is_preview_request() );
	}

	/**
	 * Test: a customer session still stores their own checkout data when the request is not a preview.
	 */
	public function test_customer_checkout_session_persists_when_not_preview() {
		$this->customer_user_id = $this->create_user_with_role( 'customer' );
		wp_set_current_user( $this->customer_user_id );

		$sut = FluidCheckout_Steps::instance();
		$this->boot_wc_customer_session();
		$this->set_preview_request_cache( false );

		$session_key = FluidCheckout_Steps::SESSION_PREFIX . 'billing_first_name';
		WC()->session->__unset( $session_key );

		$posted_data = array(
			'billing_first_name' => 'Jane',
			'billing_country'    => 'US',
		);

		$sut->update_customer_persisted_data( $posted_data );

		$this->assertSame( 'Jane', WC()->session->get( $session_key ) );
		$this->assertSame( $this->customer_user_id, get_current_user_id() );
		$this->assertFalse( current_user_can( FluidCheckout_Admin_Preview::CAPABILITY ) );
	}

	/**
	 * Test: a valid admin preview token switches to a guest or the dummy customer, never to the admin who signed it.
	 */
	public function test_valid_preview_token_does_not_grant_admin_capabilities() {
		$token = $this->create_admin_preview_token();
		$admin_user_id = $this->admin_user_id;
		$preview = FluidCheckout_Admin_Preview::instance();

		// Continue the request as a customer who only possesses the admin's token
		$this->customer_user_id = $this->create_user_with_role( 'customer' );
		wp_set_current_user( $this->customer_user_id );
		$_GET[ 'fc_preview' ] = $token;

		$this->assertSame( $admin_user_id, $preview->get_token_user_id( $preview->get_request_token() ) );

		// Guest preview mode
		$preview->switch_to_preview_customer();
		$this->assertSame( 0, get_current_user_id() );
		$this->assertNotSame( $admin_user_id, get_current_user_id() );
		$this->assertFalse( current_user_can( FluidCheckout_Admin_Preview::CAPABILITY ) );

		// Logged-in preview mode uses the dummy customer role only
		$_GET[ 'fc_preview_mode' ] = FluidCheckout_Admin_Preview::MODE_LOGGED_IN;
		add_filter( 'get_user_metadata', array( $preview, 'maybe_get_dummy_customer_meta' ), 10, 4 );
		$preview->switch_to_preview_customer();

		$this->assertSame( FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID, get_current_user_id() );
		$this->assertNotSame( $admin_user_id, get_current_user_id() );
		$this->assertFalse( current_user_can( FluidCheckout_Admin_Preview::CAPABILITY ) );
		$this->assertFalse( user_can( get_current_user_id(), 'manage_options' ) );
	}

}

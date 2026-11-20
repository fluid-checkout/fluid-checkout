<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: keep the logged-in checkout contact email.
 */
class FluidCheckout_Checkout_Steps_Billing_Email_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * User id created for account-email fallback tests.
	 *
	 * @var int
	 */
	private $created_user_id = 0;

	/**
	 * Get the checkout steps feature instance.
	 *
	 * @return  FluidCheckout_Steps
	 */
	private function get_sut() {
		return FluidCheckout_Steps::instance();
	}

	/**
	 * Boot the WooCommerce session when unit tests have not started one yet.
	 */
	private function boot_wc_session_if_needed() {
		if ( ! function_exists( 'WC' ) || ! WC() ) { return; }

		if ( ! WC()->session ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}
	}

	/**
	 * Boot the WooCommerce cart and customer when persisted-data helpers need them.
	 */
	private function boot_wc_cart_and_customer_if_needed() {
		if ( ! function_exists( 'WC' ) || ! WC() ) { return; }

		$this->boot_wc_session_if_needed();

		if ( ! WC()->customer ) {
			WC()->customer = new WC_Customer( 0, true );
		}

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
	}

	/**
	 * Clear the checkout session key for a field.
	 *
	 * @param   string  $field_key  Checkout field key.
	 */
	private function clear_session_field( $field_key ) {
		$this->boot_wc_session_if_needed();
		WC()->session->__unset( FluidCheckout_Steps::SESSION_PREFIX . $field_key );
	}

	/**
	 * Reset session and user state after each test.
	 */
	public function tearDownInstance() {
		$this->clear_session_field( 'billing_email' );
		$this->clear_session_field( 'billing_country' );
		$this->clear_session_field( 'billing_first_name' );
		$this->set_parsed_posted_data_cache( array() );

		if ( $this->created_user_id ) {
			wp_delete_user( $this->created_user_id );
			$this->created_user_id = 0;
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Set the parsed posted data cache used by persisted-data helpers.
	 *
	 * @param   array  $posted_data  Parsed posted data.
	 */
	private function set_parsed_posted_data_cache( $posted_data ) {
		$sut = $this->get_sut();
		$reflection = new ReflectionClass( $sut );
		$property = $reflection->getProperty( 'posted_data' );
		$property->setAccessible( true );
		$property->setValue( $sut, $posted_data );
	}

	/**
	 * Test: Empty session billing_email does not hide the customer value.
	 */
	public function test_empty_session_billing_email_returns_null() {
		$sut = $this->get_sut();
		$this->boot_wc_session_if_needed();

		$sut->set_checkout_field_value_to_session( 'billing_email', '' );
		$this->set_parsed_posted_data_cache( array() );

		$this->assertNull( $sut->get_checkout_field_value_from_session_or_posted_data( 'billing_email' ) );
	}

	/**
	 * Test: Empty posted billing_email during AJAX keeps the stored session email.
	 */
	public function test_empty_posted_billing_email_during_ajax_keeps_session_email() {
		$sut = $this->get_sut();
		$this->boot_wc_session_if_needed();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$sut->set_checkout_field_value_to_session( 'billing_email', 'kept@example.com' );
		$this->set_parsed_posted_data_cache( array( 'billing_email' => '' ) );

		$this->assertSame( 'kept@example.com', $sut->get_checkout_field_value_from_session_or_posted_data( 'billing_email' ) );
	}

	/**
	 * Test: Missing billing_email in a refresh payload does not clear the session email.
	 */
	public function test_reset_remaining_preserves_billing_email() {
		$sut = $this->get_sut();
		$this->boot_wc_cart_and_customer_if_needed();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$sut->set_checkout_field_value_to_session( 'billing_email', 'kept@example.com' );

		$posted_data = $sut->reset_remaining_customer_persisted_data( array(
			'billing_first_name' => 'Jane',
		) );

		$this->assertSame( 'kept@example.com', $sut->get_checkout_field_value_from_session( 'billing_email' ) );
		$this->assertArrayNotHasKey( 'billing_email', $posted_data );
	}

	/**
	 * Test: Empty posted billing_email is not written over a stored session email.
	 */
	public function test_update_customer_persisted_data_skips_empty_billing_email() {
		$sut = $this->get_sut();
		$this->boot_wc_cart_and_customer_if_needed();

		$sut->set_checkout_field_value_to_session( 'billing_email', 'kept@example.com' );

		$sut->update_customer_persisted_data( array(
			'billing_email'      => '',
			'billing_first_name' => 'Jane',
		) );

		$this->assertSame( 'kept@example.com', $sut->get_checkout_field_value_from_session( 'billing_email' ) );
	}

	/**
	 * Test: Account edit-address without billing_email keeps the stored checkout email.
	 */
	public function test_account_edit_address_without_billing_email_keeps_session_email() {
		$sut = $this->get_sut();
		$this->boot_wc_session_if_needed();

		$sut->set_checkout_field_value_to_session( 'billing_email', 'kept@example.com' );
		$sut->set_checkout_field_value_to_session( 'billing_country', 'DE' );
		$sut->set_checkout_field_value_to_session( 'billing_first_name', 'Jane' );

		$_POST[ 'action' ] = 'edit_address';
		$_POST[ 'billing_country' ] = 'DE';
		$_POST[ 'billing_first_name' ] = 'Updated';
		$_REQUEST[ 'woocommerce-edit-address-nonce' ] = wp_create_nonce( 'woocommerce-edit_address' );

		global $wp;
		$wp = new WP();
		$wp->query_vars[ 'edit-address' ] = 'billing';

		$sut->maybe_update_checkout_address_from_account();

		$this->assertSame( 'kept@example.com', $sut->get_checkout_field_value_from_session( 'billing_email' ) );
		$this->assertSame( 'Updated', $sut->get_checkout_field_value_from_session( 'billing_first_name' ) );

		unset( $_POST[ 'action' ], $_POST[ 'billing_country' ], $_POST[ 'billing_first_name' ], $_REQUEST[ 'woocommerce-edit-address-nonce' ] );
	}

	/**
	 * Test: Logged-in customers fall back to the WordPress account email.
	 */
	public function test_account_email_fallback_when_billing_email_meta_empty() {
		$sut = $this->get_sut();

		$this->created_user_id = wp_create_user( 'fc_email_fallback', 'password', 'account@example.com' );
		update_user_meta( $this->created_user_id, 'billing_email', '' );
		wp_set_current_user( $this->created_user_id );

		$result = $sut->maybe_use_account_email_for_checkout_billing_email( '', 'billing_email' );

		$this->assertSame( 'account@example.com', $result );
	}

	/**
	 * Test: Existing billing email is not replaced by the account email fallback.
	 */
	public function test_account_email_fallback_keeps_existing_billing_email() {
		$sut = $this->get_sut();

		$this->created_user_id = wp_create_user( 'fc_email_keep', 'password', 'account@example.com' );
		wp_set_current_user( $this->created_user_id );

		$result = $sut->maybe_use_account_email_for_checkout_billing_email( 'billing@example.com', 'billing_email' );

		$this->assertSame( 'billing@example.com', $result );
	}
}

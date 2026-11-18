<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: checkout field args filters.
 */
class FluidCheckout_CheckoutFields_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Filter callback that simulates a plugin removing the order field group.
	 *
	 * @param   array  $fields  Checkout field groups.
	 *
	 * @return  array
	 */
	public function filter_remove_order_checkout_field_group( $fields ) {
		unset( $fields['order'] );

		return $fields;
	}

	/**
	 * Get the checkout fields feature instance.
	 *
	 * @return  FluidCheckout_CheckoutFields
	 */
	private function get_sut() {
		return FluidCheckout_CheckoutFields::instance();
	}

	/**
	 * Clear the cached checkout fields so WooCommerce rebuilds them.
	 */
	private function reset_wc_checkout_fields_cache() {
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
			return;
		}

		$checkout   = WC()->checkout();
		$reflection = new ReflectionClass( $checkout );
		$property   = $reflection->getProperty( 'fields' );
		$property->setAccessible( true );
		$property->setValue( $checkout, null );
	}

	/**
	 * Remove filters registered during tests.
	 */
	public function tearDownInstance() {
		remove_filter( 'woocommerce_checkout_fields', array( $this, 'filter_remove_order_checkout_field_group' ), 50 );

		$this->reset_wc_checkout_fields_cache();
	}

	/**
	 * Test: change_order_field_args leaves field groups unchanged when order is missing.
	 */
	public function test_change_order_field_args_skips_when_order_group_missing() {
		$sut    = $this->get_sut();
		$fields = array(
			'billing'  => array(
				'billing_email' => array(
					'type' => 'email',
				),
			),
			'shipping' => array(),
		);

		$result = $sut->change_order_field_args( $fields );

		$this->assertArrayNotHasKey( 'order', $result );
		$this->assertSame( $fields, $result );
	}

	/**
	 * Test: change_order_field_args does not set the order group to null.
	 */
	public function test_change_order_field_args_does_not_set_order_group_to_null() {
		$sut    = $this->get_sut();
		$fields = array(
			'billing'  => array(),
			'shipping' => array(),
		);

		$result = $sut->change_order_field_args( $fields );

		$this->assertFalse( array_key_exists( 'order', $result ) && null === $result['order'] );
	}

	/**
	 * Test: change_account_field_args leaves field groups unchanged when account is missing.
	 */
	public function test_change_account_field_args_skips_when_account_group_missing() {
		$sut    = $this->get_sut();
		$fields = array(
			'billing'  => array(
				'billing_email' => array(
					'type' => 'email',
				),
			),
			'shipping' => array(),
		);

		$result = $sut->change_account_field_args( $fields );

		$this->assertArrayNotHasKey( 'account', $result );
		$this->assertSame( $fields, $result );
	}

	/**
	 * Test: WooCommerce checkout fields initialize when the order group was removed before field args filters.
	 */
	public function test_get_checkout_fields_initializes_when_order_group_removed_by_earlier_filter() {
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
			$this->markTestSkipped( 'WooCommerce checkout is not available.' );
		}

		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_remove_order_checkout_field_group' ), 50 );

		$this->reset_wc_checkout_fields_cache();

		$checkout_fields = WC()->checkout()->get_checkout_fields();

		foreach ( $checkout_fields as $field_group => $group_fields ) {
			$this->assertIsArray( $group_fields, "Checkout field group `{$field_group}` must be an array." );
		}

		$this->assertArrayHasKey( 'billing', $checkout_fields );
		$this->assertArrayHasKey( 'shipping', $checkout_fields );
	}

	/**
	 * Boot the WooCommerce session, customer, and cart when totals tests need them.
	 */
	private function boot_wc_cart_if_needed() {
		if ( ! function_exists( 'WC' ) || ! WC() ) {
			return;
		}

		if ( ! WC()->session ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}

		if ( ! WC()->customer ) {
			WC()->customer = new WC_Customer( 0, true );
		}

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
	}

	/**
	 * Test: Cart shipping totals do not fatal when the order group was removed before field args filters.
	 */
	public function test_cart_calculate_totals_when_order_group_removed_by_earlier_filter() {
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_load_cart' ) ) {
			$this->markTestSkipped( 'WooCommerce cart is not available.' );
		}

		$this->boot_wc_cart_if_needed();

		$product_id = $this->get_simple_shippable_product_id();
		$this->assertNotSame( 0, $product_id );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_remove_order_checkout_field_group' ), 50 );

		wc_load_cart();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product_id, 1 );
		$this->reset_wc_checkout_fields_cache();

		WC()->cart->calculate_totals();

		$this->assertGreaterThan( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Get a simple product id suitable for shipping calculation tests.
	 *
	 * @return  int  Product id or 0 when none could be created.
	 */
	private function get_simple_shippable_product_id() {
		$products = wc_get_products(
			array(
				'limit'   => 1,
				'status'  => 'publish',
				'type'    => 'simple',
				'return'  => 'ids',
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( ! empty( $products ) ) {
			$product_id = (int) $products[0];
			$product    = wc_get_product( $product_id );

			if ( $product && $product->needs_shipping() ) {
				return $product_id;
			}
		}

		$product = new WC_Product_Simple();
		$product->set_name( 'FC Checkout Fields Test Product' );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		$product->set_virtual( 'no' );
		$product_id = $product->save();

		return $product_id ? (int) $product_id : 0;
	}
}
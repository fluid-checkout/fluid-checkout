<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: FluidCheckout_Utils page and admin screen helpers.
 */
class FluidCheckout_Utils_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: class is loaded as a feature singleton.
	 */
	public function test_utils_class_exists() {
		$this->assertTrue( class_exists( 'FluidCheckout_Utils' ) );
		$this->assertInstanceOf( 'FluidCheckout_Utils', FluidCheckout_Utils::instance() );
	}

	/**
	 * Test: HPOS off uses classic shop_order screen id.
	 */
	public function test_get_order_edit_screen_id_classic() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'no' );

		$utils = FluidCheckout_Utils::instance();

		$this->assertFalse( $utils->is_hpos_enabled() );
		$this->assertSame( 'shop_order', $utils->get_order_edit_screen_id() );
	}

	/**
	 * Test: HPOS on uses woocommerce_page_wc-orders screen id.
	 */
	public function test_get_order_edit_screen_id_hpos() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );

		$utils = FluidCheckout_Utils::instance();

		$this->assertTrue( $utils->is_hpos_enabled() );
		$this->assertSame( 'woocommerce_page_wc-orders', $utils->get_order_edit_screen_id() );

		update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
	}

	/**
	 * Test: checkout page filter forces Utils and Steps wrappers to agree.
	 */
	public function test_is_checkout_page_or_fragment_filter_and_steps_wrapper() {
		$force_true = function() {
			return true;
		};

		add_filter( 'fc_is_checkout_page_or_fragment', $force_true, 10 );

		$utils_result = FluidCheckout_Utils::instance()->is_checkout_page_or_fragment();
		$steps_result = FluidCheckout_Steps::instance()->is_checkout_page_or_fragment();

		$this->assertTrue( $utils_result );
		$this->assertTrue( $steps_result );
		$this->assertSame( $utils_result, $steps_result );

		remove_filter( 'fc_is_checkout_page_or_fragment', $force_true, 10 );
	}

	/**
	 * Test: cart page filter forces Utils and Steps wrappers to agree.
	 */
	public function test_is_cart_page_or_fragment_filter_and_steps_wrapper() {
		$force_true = function() {
			return true;
		};

		add_filter( 'fc_is_cart_page_or_fragment', $force_true, 10 );

		$utils_result = FluidCheckout_Utils::instance()->is_cart_page_or_fragment();
		$steps_result = FluidCheckout_Steps::instance()->is_cart_page_or_fragment();

		$this->assertTrue( $utils_result );
		$this->assertTrue( $steps_result );
		$this->assertSame( $utils_result, $steps_result );

		remove_filter( 'fc_is_cart_page_or_fragment', $force_true, 10 );
	}

	/**
	 * Test: Lite and PRO order-pay filters force detection.
	 */
	public function test_is_order_pay_page_or_fragment_filters() {
		$utils = FluidCheckout_Utils::instance();

		$force_lite = function() {
			return true;
		};
		add_filter( 'fc_is_order_pay_page_or_fragment', $force_lite, 10 );
		$this->assertTrue( $utils->is_order_pay_page_or_fragment() );
		remove_filter( 'fc_is_order_pay_page_or_fragment', $force_lite, 10 );

		$force_pro = function() {
			return true;
		};
		add_filter( 'fc_pro_is_order_pay_page_or_fragment', $force_pro, 10 );
		$this->assertTrue( $utils->is_order_pay_page_or_fragment() );
		remove_filter( 'fc_pro_is_order_pay_page_or_fragment', $force_pro, 10 );
	}

	/**
	 * Test: HPOS new order request shape is detected when in admin.
	 */
	public function test_is_admin_new_order_page_hpos_request() {
		$utils = FluidCheckout_Utils::instance();

		$_GET['page'] = 'wc-orders';
		$_GET['action'] = 'new';

		$result = $utils->is_admin_new_order_page();

		unset( $_GET['page'], $_GET['action'] );

		// PHPUnit may or may not run with is_admin(); assert the full path when admin, otherwise the early bail.
		if ( is_admin() ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

}

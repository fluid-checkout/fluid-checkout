<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: admin preview dummy order datastore wrapper.
 */
class FluidCheckout_Admin_Preview_Order_Data_Store_Test extends TestCase {

	/**
	 * Load preview dummy order classes for tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$plugin_dir = dirname( dirname( __DIR__ ) );

		require_once $plugin_dir . '/inc/admin/admin-preview-dummy-data.php';
		require_once $plugin_dir . '/inc/admin/admin-preview-dummy-order.php';
		require_once $plugin_dir . '/inc/admin/admin-preview-order-data-store.php';
	}



	/**
	 * Test: read() uses the delegate for real order IDs.
	 */
	public function test_read_delegates_for_non_dummy_order_ids() {
		$delegate = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'read' ) )
			->getMock();

		$delegate->expects( $this->once() )->method( 'read' );

		$store = new FluidCheckout_Admin_Preview_Order_Data_Store( $delegate );
		$order = new WC_Order( 0 );
		$order->set_id( 12345 );

		$store->read( $order );
	}



	/**
	 * Test: wrap_order_data_store does not wrap the wrapper twice.
	 */
	public function test_wrap_order_data_store_is_idempotent() {
		require_once dirname( dirname( __DIR__ ) ) . '/inc/admin/admin-preview.php';

		$preview = new FluidCheckout_Admin_Preview();
		$wrapped = $preview->wrap_order_data_store( new stdClass() );

		$this->assertInstanceOf( FluidCheckout_Admin_Preview_Order_Data_Store::class, $wrapped );
		$this->assertSame( $wrapped, $preview->wrap_order_data_store( $wrapped ) );
	}

}

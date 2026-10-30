<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: checkout guided setup answer helpers.
 */
class FluidCheckout_Guided_Setup_Checkout_Data_Test extends TestCase {

	/**
	 * Test: product types step requires at least one type.
	 */
	public function test_product_types_step_requires_selection() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'product_types',
			array(),
			array()
		);

		$this->assertInstanceOf( 'WP_Error', $result );
	}



	/**
	 * Test: physical products require a delivery method.
	 */
	public function test_physical_requires_delivery_method() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'product_types',
			array(
				'product_types' => array( 'physical' ),
			),
			array()
		);

		$this->assertInstanceOf( 'WP_Error', $result );
	}



	/**
	 * Test: delivery methods persist when physical is not selected.
	 */
	public function test_delivery_persisted_when_physical_not_selected() {
		$existing = array(
			'delivery_methods' => array( 'local_pickup' ),
		);

		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'product_types',
			array(
				'product_types' => array( 'downloads' ),
			),
			$existing
		);

		$this->assertIsArray( $result );
		$this->assertSame( array( 'local_pickup' ), $result['delivery_methods'] );
	}



	/**
	 * Test: about store clears email when newsletter is off.
	 */
	public function test_about_store_clears_email_without_newsletter() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'about_store',
			array(
				'business_industry' => 'food-drink',
				'email'             => 'merchant@example.com',
			),
			array()
		);

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['email'] );
	}

}

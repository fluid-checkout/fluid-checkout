<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: checkout guided setup answer helpers.
 */
class FluidCheckout_Guided_Setup_Checkout_Data_Test extends TestCase {
	use TransactionalTestClassTrait;

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
				'business_industry'  => 'food-drink',
				'customer_audience'  => 'dtc',
				'customer_location'  => 'domestic',
				'email'              => 'merchant@example.com',
			),
			array()
		);

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['email'] );
	}



	/**
	 * Test: default answers pre-select DTC customer audience.
	 */
	public function test_default_answers_customer_audience_is_dtc() {
		$defaults = FluidCheckout_Guided_Setup_Checkout_Data::get_default_answers();

		$this->assertSame( 'dtc', $defaults['customer_audience'] );
	}



	/**
	 * Test: default answers include a valid customer location prefill.
	 */
	public function test_default_answers_customer_location_is_valid() {
		$defaults = FluidCheckout_Guided_Setup_Checkout_Data::get_default_answers();
		$options  = FluidCheckout_Guided_Setup_Checkout_Data::get_customer_location_options();

		$this->assertArrayHasKey( $defaults['customer_location'], $options );
	}



	/**
	 * Test: about store requires customer audience.
	 */
	public function test_about_store_requires_customer_audience() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'about_store',
			array(
				'business_industry' => 'food-drink',
			),
			array()
		);

		$this->assertInstanceOf( 'WP_Error', $result );
	}



	/**
	 * Test: about store requires customer location.
	 */
	public function test_about_store_requires_customer_location() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'about_store',
			array(
				'business_industry' => 'food-drink',
				'customer_audience' => 'dtc',
			),
			array()
		);

		$this->assertInstanceOf( 'WP_Error', $result );
	}



	/**
	 * Test: about store saves customer audience and location.
	 */
	public function test_about_store_saves_customer_audience_and_location() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'about_store',
			array(
				'business_industry' => 'food-drink',
				'customer_audience' => 'b2b',
				'customer_location' => 'international',
			),
			array()
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'b2b', $result['customer_audience'] );
		$this->assertSame( 'international', $result['customer_location'] );
	}



	/**
	 * Test: about store does not store usage tracking as an answer.
	 */
	public function test_about_store_does_not_store_telemetry_opt_in_answer() {
		$result = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data(
			'about_store',
			array(
				'business_industry' => 'food-drink',
				'customer_audience' => 'dtc',
				'customer_location' => 'domestic',
				'telemetry_opt_in'  => '1',
			),
			array(
				'telemetry_opt_in' => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'telemetry_opt_in', $result );
	}



	/**
	 * Test: default answers never include usage tracking opt-in.
	 */
	public function test_default_answers_omit_telemetry_opt_in() {
		$defaults = FluidCheckout_Guided_Setup_Checkout_Data::get_default_answers(
			array(
				'telemetry_opt_in' => true,
			)
		);

		$this->assertArrayNotHasKey( 'telemetry_opt_in', $defaults );
	}



	/**
	 * Test: customer location prefill is domestic when selling only to the store country.
	 */
	public function test_prefill_customer_location_domestic_for_single_store_country() {
		$store_country = FluidCheckout_Guided_Setup_Checkout_Data::get_store_country_code();

		// Bail-style skip when WooCommerce has no store country in this environment
		if ( '' === $store_country ) {
			$this->markTestSkipped( 'Store country is unavailable.' );
		}

		$this->set_woocommerce_selling_locations( 'specific', array( $store_country ) );

		$this->assertSame( 'domestic', FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_customer_location() );
	}



	/**
	 * Test: customer location prefill is international when selling to all countries.
	 */
	public function test_prefill_customer_location_international_for_all_countries() {
		$this->set_woocommerce_selling_locations( 'all', array() );

		$this->assertSame( 'international', FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_customer_location() );
	}



	/**
	 * Set WooCommerce selling location options and clear the options object cache.
	 *
	 * @param string   $mode    `woocommerce_allowed_countries` value.
	 * @param string[] $specific Specific country codes when mode is `specific`.
	 */
	private function set_woocommerce_selling_locations( $mode, $specific = array() ) {
		update_option( 'woocommerce_allowed_countries', $mode );
		update_option( 'woocommerce_specific_allowed_countries', $specific );
		wp_cache_delete( 'woocommerce_allowed_countries', 'options' );
		wp_cache_delete( 'woocommerce_specific_allowed_countries', 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	}

}

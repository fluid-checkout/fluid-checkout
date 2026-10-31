<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit tests: recommendation filter value vote scoring.
 */
class FluidCheckout_Guided_Setup_Recommendations_Value_Votes_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * @var FluidCheckout_Guided_Setup_Recommendations
	 */
	private $engine;



	/**
	 * Create a fresh engine before each test.
	 */
	public function setUpInstance() : void {
		$this->engine = new FluidCheckout_Guided_Setup_Recommendations();
	}



	/**
	 * Test: higher vote weight wins when an option receives multiple values.
	 */
	public function test_filter_value_with_more_votes_wins() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_phone_field_visibility' => 'required' ), 0, 'product_types' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_phone_field_visibility' => 'optional' ), 1, 'product_types' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_phone_field_visibility' => 'optional' ), 2, 'product_types' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertTrue( $scores['fc_shipping_phone_field_visibility']['has_multiple_values'] );
		$this->assertSame(
			array(
				'required' => 1,
				'optional' => 2,
			),
			$scores['fc_shipping_phone_field_visibility']['values']
		);
		$this->assertSame( 'optional', $scores['fc_shipping_phone_field_visibility']['winner'] );
		$this->assertSame( 2, $scores['fc_shipping_phone_field_visibility']['winner_count'] );
		$this->assertSame( 'product_types', $scores['fc_shipping_phone_field_visibility']['winner_filter_type'] );
		$this->assertFalse( $scores['fc_shipping_phone_field_visibility']['suppressed'] );

		$resolved = $this->engine->resolve_profile_values_from_votes(
			array( 'fc_shipping_phone_field_visibility' => 'hidden' ),
			$votes
		);

		$this->assertSame( 'optional', $resolved['fc_shipping_phone_field_visibility'] );
	}



	/**
	 * Test: tied vote weights prefer the later filter contribution.
	 */
	public function test_tied_filter_value_votes_prefer_later_filter() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_enable_checkout_local_pickup' => 'yes' ), 0, 'product_types' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_enable_checkout_local_pickup' => 'no' ), 1, 'product_types' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertTrue( $scores['fc_enable_checkout_local_pickup']['has_multiple_values'] );
		$this->assertSame( 'no', $scores['fc_enable_checkout_local_pickup']['winner'] );
		$this->assertSame( 1, $scores['fc_enable_checkout_local_pickup']['winner_count'] );
	}



	/**
	 * Test: a single recommended value is not marked as a multi-value conflict.
	 */
	public function test_single_filter_value_is_not_a_conflict() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_gaa_enabled' => 'yes' ), 0, 'business_industry' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_gaa_enabled' => 'yes' ), 1, 'business_industry' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertFalse( $scores['fc_gaa_enabled']['has_multiple_values'] );
		$this->assertSame( array( 'yes' => 2 ), $scores['fc_gaa_enabled']['values'] );
		$this->assertSame( 'yes', $scores['fc_gaa_enabled']['winner'] );
	}



	/**
	 * Test: more specific filter types own the option key over lower types.
	 */
	public function test_more_specific_filter_type_wins_over_vote_count() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_checkout_layout' => 'multi-step' ), 0, 'business_industry' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_checkout_layout' => 'multi-step' ), 1, 'customer_audience' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_checkout_layout' => 'single-step' ), 2, 'product_types' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertSame( 'single-step', $scores['fc_checkout_layout']['winner'] );
		$this->assertSame( 'product_types', $scores['fc_checkout_layout']['winner_filter_type'] );
		$this->assertSame( 1, $scores['fc_checkout_layout']['winner_count'] );
		$this->assertFalse( $scores['fc_checkout_layout']['suppressed'] );
	}



	/**
	 * Test: domestic before-address beats industry after-address; delivery before beats international after.
	 */
	public function test_shipping_methods_position_specificity_priority() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_methods_substep_position' => 'after_shipping_address' ), 0, 'business_industry' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_methods_substep_position' => 'before_shipping_address' ), 1, 'customer_location' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertSame( 'before_shipping_address', $scores['fc_shipping_methods_substep_position']['winner'] );
		$this->assertSame( 'customer_location', $scores['fc_shipping_methods_substep_position']['winner_filter_type'] );

		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_methods_substep_position' => 'after_shipping_address' ), 0, 'customer_location' );
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_methods_substep_position' => 'before_shipping_address' ), 1, 'delivery_methods' );

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertSame( 'before_shipping_address', $scores['fc_shipping_methods_substep_position']['winner'] );
		$this->assertSame( 'delivery_methods', $scores['fc_shipping_methods_substep_position']['winner_filter_type'] );
	}



	/**
	 * Test: structured weight multiplies a vote within the same filter type.
	 */
	public function test_structured_weight_multiplies_votes_within_filter_type() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_checkout_layout' => 'multi-step' ), 0, 'product_types' );
		$votes = $this->engine->add_filter_map_votes(
			$votes,
			array(
				'fc_checkout_layout' => array(
					'value'  => 'single-step',
					'weight' => 10,
				),
			),
			1,
			'product_types'
		);

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertSame( 'single-step', $scores['fc_checkout_layout']['winner'] );
		$this->assertSame( 10, $scores['fc_checkout_layout']['winner_count'] );
		$this->assertSame(
			array(
				'multi-step'  => 1,
				'single-step' => 10,
			),
			$scores['fc_checkout_layout']['values']
		);
	}



	/**
	 * Test: `__omit` from a more specific type suppresses the option.
	 */
	public function test_omit_from_specific_filter_suppresses_option() {
		$votes = array();
		$votes = $this->engine->add_filter_map_votes( $votes, array( 'fc_shipping_phone_field_visibility' => 'optional' ), 0, 'business_industry' );
		$votes = $this->engine->add_filter_map_votes(
			$votes,
			array( 'fc_shipping_phone_field_visibility' => FluidCheckout_Guided_Setup_Recommendations::FILTER_VALUE_OMIT ),
			1,
			'product_types'
		);

		$scores = $this->engine->build_filter_value_scores( $votes );

		$this->assertTrue( $scores['fc_shipping_phone_field_visibility']['suppressed'] );
		$this->assertNull( $scores['fc_shipping_phone_field_visibility']['winner'] );
		$this->assertSame( 'product_types', $scores['fc_shipping_phone_field_visibility']['winner_filter_type'] );
		$this->assertSame( array( 'fc_shipping_phone_field_visibility' ), $this->engine->get_suppressed_option_keys( $scores ) );

		$resolved = $this->engine->resolve_profile_values_from_votes(
			array( 'fc_shipping_phone_field_visibility' => 'hidden' ),
			$votes
		);

		$this->assertSame( 'hidden', $resolved['fc_shipping_phone_field_visibility'] );
	}



	/**
	 * Test: checkout layout falls back to the settings default when filters omit it.
	 */
	public function test_checkout_layout_uses_default_when_filters_omit_it() {
		$this->assertSame( 'multi-step', $this->engine->resolve_checkout_layout_value( array() ) );
		$this->assertSame(
			'single-step',
			$this->engine->resolve_checkout_layout_value(
				array(
					'fc_checkout_layout' => array(
						'winner'     => 'single-step',
						'suppressed' => false,
					),
				)
			)
		);
		$this->assertSame(
			'multi-step',
			$this->engine->resolve_checkout_layout_value(
				array(
					'fc_checkout_layout' => array(
						'winner'     => null,
						'suppressed' => true,
					),
				)
			)
		);
	}



	/**
	 * Test: checkout layout is always the first recommendation, using the default when filters omit it.
	 */
	public function test_checkout_layout_is_always_first_recommendation() {
		$filters = new FluidCheckout_Guided_Setup_Recommendation_Filters();
		$filters->clear_filters();
		$this->engine->set_filters( $filters );

		$result = $this->engine->get_recommendation(
			array(
				'business_industry' => 'fashion-apparel-accessories',
				'customer_audience' => 'dtc',
				'product_types'     => array( 'downloads' ),
				'delivery_methods'  => array(),
			)
		);

		$this->assertNotEmpty( $result['recommended'] );
		$this->assertSame( 'fc_checkout_layout', $result['recommended'][0]['option_key'] );
		$this->assertSame( 'multi-step', $result['recommended'][0]['value'] );
		$this->assertSame( 'multi-step', $result['profile_values']['fc_checkout_layout'] );
	}



	/**
	 * Test: checkout layout filter winner is used and still listed first.
	 */
	public function test_checkout_layout_filter_winner_is_first_recommendation() {
		$result = $this->engine->get_recommendation(
			array(
				'business_industry' => 'fashion-apparel-accessories',
				'customer_audience' => 'dtc',
				'product_types'     => array( 'downloads' ),
				'delivery_methods'  => array(),
			)
		);

		$this->assertNotEmpty( $result['recommended'] );
		$this->assertSame( 'fc_checkout_layout', $result['recommended'][0]['option_key'] );
		$this->assertSame( 'single-step', $result['recommended'][0]['value'] );
		$this->assertSame( 'single-step', $result['profile_values']['fc_checkout_layout'] );
	}



	/**
	 * Test: digital-only fashion stores omit shipping recommendations and keep single-step layout.
	 */
	public function test_digital_only_fashion_omits_shipping_and_uses_single_step() {
		$result = $this->engine->get_recommendation(
			array(
				'business_industry' => 'fashion-apparel-accessories',
				'customer_audience' => 'dtc',
				'product_types'     => array( 'services' ),
				'delivery_methods'  => array(),
			)
		);

		$recommended_keys = array_values( array_map(
			function ( $row ) {
				return $row['option_key'];
			},
			$result['recommended']
		) );

		$this->assertSame( 'fc_checkout_layout', $recommended_keys[0] );
		$this->assertSame( 'single-step', $result['recommended'][0]['value'] );
		$this->assertNotContains( 'fc_shipping_phone_field_visibility', $recommended_keys );
		$this->assertNotContains( 'fc_gaa_enabled', $recommended_keys );
		$this->assertTrue( $result['filter_value_scores']['fc_shipping_phone_field_visibility']['suppressed'] );
		$this->assertTrue( $result['filter_value_scores']['fc_gaa_enabled']['suppressed'] );
	}



	/**
	 * Test: mixed physical + downloads keeps industry shipping preferences (digital omits are scoped out).
	 */
	public function test_physical_with_downloads_keeps_shipping_recommendations() {
		$result = $this->engine->get_recommendation(
			array(
				'business_industry' => 'fashion-apparel-accessories',
				'customer_audience' => 'dtc',
				'product_types'     => array( 'physical', 'downloads' ),
				'delivery_methods'  => array( 'ship_to_address' ),
			)
		);

		$recommended_keys = array_values( array_map(
			function ( $row ) {
				return $row['option_key'];
			},
			$result['recommended']
		) );

		$this->assertSame( 'fc_checkout_layout', $recommended_keys[0] );
		$this->assertSame( 'multi-step', $result['recommended'][0]['value'] );
		$this->assertContains( 'fc_shipping_phone_field_visibility', $recommended_keys );
		$this->assertFalse(
			isset( $result['filter_value_scores']['fc_shipping_phone_field_visibility']['suppressed'] )
			&& $result['filter_value_scores']['fc_shipping_phone_field_visibility']['suppressed']
		);
	}

}

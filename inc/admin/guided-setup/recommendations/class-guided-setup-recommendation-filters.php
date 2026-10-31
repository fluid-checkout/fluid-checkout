<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registry of guided setup recommendation filter types.
 */
class FluidCheckout_Guided_Setup_Recommendation_Filters {

	/**
	 * Registered filters keyed by type.
	 *
	 * @var array<string, array{type:string,priority:int,resolve_values:callable}>
	 */
	protected $filters = array();



	/**
	 * Register a filter type.
	 *
	 * @param string   $type           Folder name under filters/.
	 * @param int      $priority       Merge order (lower runs first).
	 * @param callable $resolve_values Resolves value slugs from answers: `function( array $answers ): string[]`.
	 */
	public function register_filter( $type, $priority, $resolve_values ) {
		$type = sanitize_key( (string) $type );

		// Bail if type is empty or resolver is not callable
		if ( '' === $type || ! is_callable( $resolve_values ) ) {
			return;
		}

		$this->filters[ $type ] = array(
			'type'           => $type,
			'priority'       => (int) $priority,
			'resolve_values' => $resolve_values,
		);
	}



	/**
	 * Get registered filters sorted by priority, then type.
	 *
	 * @return array<int, array{type:string,priority:int,resolve_values:callable}>
	 */
	public function get_registered_filters() {
		$filters = array_values( $this->filters );

		usort(
			$filters,
			function ( $a, $b ) {
				// Sort by priority first
				if ( $a['priority'] !== $b['priority'] ) {
					return $a['priority'] <=> $b['priority'];
				}

				return strcmp( $a['type'], $b['type'] );
			}
		);

		return $filters;
	}



	/**
	 * Clear all registered filters (tests).
	 */
	public function clear_filters() {
		$this->filters = array();
	}



	/**
	 * Register the default Lite checkout filter types.
	 */
	public function register_default_checkout_filters() {
		$this->register_filter( 'business_industry', 10, array( $this, 'resolve_business_industry_values' ) );
		$this->register_filter( 'customer_audience', 20, array( $this, 'resolve_customer_audience_values' ) );
		$this->register_filter( 'customer_location', 25, array( $this, 'resolve_customer_location_values' ) );
		$this->register_filter( 'product_types', 30, array( $this, 'resolve_product_types_values' ) );
		$this->register_filter( 'delivery_methods', 40, array( $this, 'resolve_delivery_methods_values' ) );
	}



	/**
	 * Resolve business industry file slug from answers.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string[]
	 */
	public function resolve_business_industry_values( $answers ) {
		$answers  = is_array( $answers ) ? $answers : array();
		$industry = isset( $answers['business_industry'] ) ? (string) $answers['business_industry'] : '';

		// Bail if industry is empty
		if ( '' === $industry ) {
			return array();
		}

		$fallback_slugs = array(
			'sports-and-recreation',
			'arts-and-crafts',
			'other',
		);

		// Map sparse wizard slugs to the shared others file
		if ( in_array( $industry, $fallback_slugs, true ) ) {
			return array( 'others' );
		}

		return array( $industry );
	}



	/**
	 * Resolve customer audience file slug from answers.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string[]
	 */
	public function resolve_customer_audience_values( $answers ) {
		$answers  = is_array( $answers ) ? $answers : array();
		$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';

		$allowed = array_keys( FluidCheckout_Guided_Setup_Checkout_Data::get_customer_audience_options() );

		// Fallback to DTC when the value is unknown
		if ( ! in_array( $audience, $allowed, true ) ) {
			$audience = 'dtc';
		}

		return array( $audience );
	}



	/**
	 * Resolve customer location file slug from answers.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string[]
	 */
	public function resolve_customer_location_values( $answers ) {
		$answers  = is_array( $answers ) ? $answers : array();
		$location = isset( $answers['customer_location'] ) ? (string) $answers['customer_location'] : '';
		$allowed  = array_keys( FluidCheckout_Guided_Setup_Checkout_Data::get_customer_location_options() );

		// Fallback to the WooCommerce-based prefill when the value is unknown
		if ( ! in_array( $location, $allowed, true ) ) {
			$location = FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_customer_location();
		}

		return array( $location );
	}



	/**
	 * Resolve product type file slugs from answers (stable order).
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string[]
	 */
	public function resolve_product_types_values( $answers ) {
		$answers       = is_array( $answers ) ? $answers : array();
		$selected      = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
		$selected      = array_map( 'strval', $selected );
		$ordered_slugs = array_keys( FluidCheckout_Guided_Setup_Checkout_Data::get_product_types() );
		$values        = array();

		// Iterate product types in registry order
		foreach ( $ordered_slugs as $slug ) {
			// Skip types the merchant did not select
			if ( ! in_array( $slug, $selected, true ) ) {
				continue;
			}

			$values[] = $slug;
		}

		return $values;
	}



	/**
	 * Resolve dominant delivery method file slug when physical products are selected.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string[]
	 */
	public function resolve_delivery_methods_values( $answers ) {
		$answers  = is_array( $answers ) ? $answers : array();
		$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
		$types    = array_map( 'strval', $types );
		$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();
		$delivery = array_map( 'strval', $delivery );

		// Bail if physical products are not selected
		if ( ! in_array( 'physical', $types, true ) ) {
			return array();
		}

		$dominant = $this->get_dominant_delivery_method( $delivery );

		// Bail if no delivery method can be resolved
		if ( '' === $dominant ) {
			return array();
		}

		return array( $dominant );
	}



	/**
	 * Get the dominant delivery method slug for recommendation merge.
	 *
	 * Priority: local_pickup, local_delivery, ship_to_pickup_point, ship_to_address.
	 *
	 * @param array $delivery_methods Selected delivery method slugs.
	 *
	 * @return string
	 */
	public function get_dominant_delivery_method( $delivery_methods ) {
		$delivery_methods = is_array( $delivery_methods ) ? array_map( 'strval', $delivery_methods ) : array();
		$priority         = array(
			'local_pickup',
			'local_delivery',
			'ship_to_pickup_point',
			'ship_to_address',
		);

		// Iterate priority order
		foreach ( $priority as $slug ) {
			// Skip methods not selected
			if ( ! in_array( $slug, $delivery_methods, true ) ) {
				continue;
			}

			return $slug;
		}

		return '';
	}

}

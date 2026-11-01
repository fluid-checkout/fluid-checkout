<?php
defined( 'ABSPATH' ) || exit;

/**
 * Guided setup recommendation engine (registered filter stack).
 */
class FluidCheckout_Guided_Setup_Recommendations {

	/**
	 * Filter map sentinel: suppress the option from recommendations and profile overrides.
	 */
	const FILTER_VALUE_OMIT = '__omit';

	/**
	 * Singleton instances.
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Filter registry.
	 *
	 * @var FluidCheckout_Guided_Setup_Recommendation_Filters
	 */
	protected $filters;

	/**
	 * Filter map loader.
	 *
	 * @var FluidCheckout_Guided_Setup_Recommendation_Loader
	 */
	protected $loader;



	/**
	 * Singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		$called_class = get_called_class();

		if ( ! array_key_exists( $called_class, self::$instances ) || null === self::$instances[ $called_class ] ) {
			self::$instances[ $called_class ] = new $called_class();
		}

		return self::$instances[ $called_class ];
	}



	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->filters = new FluidCheckout_Guided_Setup_Recommendation_Filters();
		$this->loader  = new FluidCheckout_Guided_Setup_Recommendation_Loader();
		$this->filters->register_default_checkout_filters();
	}



	/**
	 * Get the filter registry (tests may replace filters).
	 *
	 * @return FluidCheckout_Guided_Setup_Recommendation_Filters
	 */
	public function get_filters() {
		return $this->filters;
	}



	/**
	 * Get the filter map loader.
	 *
	 * @return FluidCheckout_Guided_Setup_Recommendation_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}



	/**
	 * Replace the filter registry (tests).
	 *
	 * @param FluidCheckout_Guided_Setup_Recommendation_Filters $filters Registry.
	 */
	public function set_filters( $filters ) {
		// Bail if not the expected class
		if ( ! $filters instanceof FluidCheckout_Guided_Setup_Recommendation_Filters ) {
			return;
		}

		$this->filters = $filters;
	}



	/**
	 * Replace the loader (tests).
	 *
	 * @param FluidCheckout_Guided_Setup_Recommendation_Loader $loader Loader.
	 */
	public function set_loader( $loader ) {
		// Bail if not the expected class
		if ( ! $loader instanceof FluidCheckout_Guided_Setup_Recommendation_Loader ) {
			return;
		}

		$this->loader = $loader;
	}



	/**
	 * Build a recommendation payload from wizard answers.
	 *
	 * Filter maps vote for option values. Resolution uses filter-type specificity first,
	 * then weighted votes within that type (ties prefer the later filter). `__omit` suppresses
	 * an option from recommendations and profile overrides.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return array{profile_values:array,recommended:array,answers_hash:string,filter_value_scores:array,partials_meta:array}
	 */
	public function get_recommendation( $answers ) {
		$answers = is_array( $answers ) ? $answers : array();

		$defaults      = $this->get_settings_defaults();
		$votes         = array();
		$partials_meta = array();
		$vote_order    = 0;

		// Iterate registered filters in priority order
		foreach ( $this->filters->get_registered_filters() as $filter ) {
			$type   = $filter['type'];
			$values = call_user_func( $filter['resolve_values'], $answers );
			$values = is_array( $values ) ? $values : array();

			// Iterate resolved value slugs for this filter type
			foreach ( $values as $value ) {
				$value = (string) $value;
				$map   = $this->sanitize_option_map( $this->loader->load_filter_map( $type, $value ), $defaults );
				$map   = $this->apply_filter_map_answer_scopes( $map, $type, $value, $answers );

				// Skip empty partials
				if ( empty( $map ) ) {
					continue;
				}

				$partials_meta[] = $type . '/' . $value;
				$votes           = $this->add_filter_map_votes( $votes, $map, $vote_order, $type );
				$vote_order++;
			}
		}

		$filter_value_scores = $this->build_filter_value_scores( $votes );
		$profile_values      = $this->resolve_profile_values_from_votes( $defaults, $votes );
		$profile_values      = $this->sanitize_option_map( $profile_values, $defaults );

		// Checkout layout always uses the filter winner when present, otherwise the settings default
		$profile_values['fc_checkout_layout'] = $this->resolve_checkout_layout_value( $filter_value_scores );

		$recommended = $this->build_recommended_rows( $answers, $profile_values, $filter_value_scores );

		return array(
			'profile_values'      => $profile_values,
			'recommended'         => $recommended,
			'answers_hash'        => $this->get_answers_hash( $answers ),
			'filter_value_scores' => $filter_value_scores,
			'partials_meta'       => $partials_meta,
		);
	}



	/**
	 * Filter-type specificity ranks (higher wins ownership of an option key).
	 *
	 * @return array<string, int>
	 */
	public function get_filter_type_specificity() {
		return array(
			'delivery_methods'   => 40,
			'product_types'      => 30,
			'customer_location'  => 25,
			'customer_audience'  => 20,
			'business_industry'  => 10,
		);
	}



	/**
	 * Normalize a filter map entry to value + weight.
	 *
	 * Plain scalars keep weight 1. Structured entries use `value` and optional `weight`.
	 *
	 * @param mixed $raw Raw map value.
	 *
	 * @return array{value:mixed,weight:int}
	 */
	public function normalize_filter_map_entry( $raw ) {
		// Structured value with optional weight
		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$weight = isset( $raw['weight'] ) ? (int) $raw['weight'] : 1;

			return array(
				'value'  => $raw['value'],
				'weight' => max( 1, $weight ),
			);
		}

		return array(
			'value'  => $raw,
			'weight' => 1,
		);
	}



	/**
	 * Maybe strip filter map entries that only apply for certain answer combinations.
	 *
	 * - Downloads/services maps: layout and `__omit` apply only when the catalog is digital-only.
	 * - Customer location maps: shipping methods position applies only when physical products are selected.
	 *
	 * @param array  $map     Sanitized filter map.
	 * @param string $type    Filter type.
	 * @param string $value   Filter value slug.
	 * @param array  $answers Wizard answers.
	 *
	 * @return array
	 */
	public function apply_filter_map_answer_scopes( $map, $type, $value, $answers ) {
		$map     = is_array( $map ) ? $map : array();
		$type    = (string) $type;
		$value   = (string) $value;
		$answers = is_array( $answers ) ? $answers : array();
		$types   = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? array_map( 'strval', $answers['product_types'] ) : array();
		$has_physical = in_array( 'physical', $types, true );

		// Customer location shipping-position votes require physical products
		if ( 'customer_location' === $type ) {
			// Bail if physical products are selected
			if ( $has_physical ) {
				return $map;
			}

			$scoped = $map;
			unset( $scoped['fc_shipping_methods_substep_position'] );

			return $scoped;
		}

		// Bail if not a digital product-type map
		if ( 'product_types' !== $type || ! in_array( $value, array( 'downloads', 'services' ), true ) ) {
			return $map;
		}

		// Bail if physical products are not also selected
		if ( ! $has_physical ) {
			return $map;
		}

		$scoped = array();

		// Iterate map entries and drop digital-only layout/omit preferences
		foreach ( $map as $option_key => $raw ) {
			$option_key = (string) $option_key;
			$normalized = $this->normalize_filter_map_entry( $raw );

			// Skip checkout layout from digital maps when physical products are selected
			if ( 'fc_checkout_layout' === $option_key ) {
				continue;
			}

			// Skip omit sentinels from digital maps when physical products are selected
			if ( self::FILTER_VALUE_OMIT === $normalized['value'] ) {
				continue;
			}

			$scoped[ $option_key ] = $raw;
		}

		return $scoped;
	}



	/**
	 * Add votes from a filter option map.
	 *
	 * @param array  $votes       Existing votes keyed by option_key, then filter type, then value key.
	 * @param array  $map         Sanitized option map from one filter file.
	 * @param int    $vote_order  Monotonic order for tie-breaking (later wins ties).
	 * @param string $filter_type Filter type slug (specificity owner).
	 *
	 * @return array
	 */
	public function add_filter_map_votes( $votes, $map, $vote_order = 0, $filter_type = '' ) {
		$votes       = is_array( $votes ) ? $votes : array();
		$map         = is_array( $map ) ? $map : array();
		$filter_type = sanitize_key( (string) $filter_type );

		// Use a stable bucket when callers omit the filter type (tests)
		if ( '' === $filter_type ) {
			$filter_type = '_default';
		}

		// Iterate option keys contributed by this filter map
		foreach ( $map as $option_key => $raw ) {
			$option_key = (string) $option_key;
			$normalized = $this->normalize_filter_map_entry( $raw );
			$value      = $normalized['value'];
			$weight     = (int) $normalized['weight'];
			$value_key  = $this->get_filter_value_vote_key( $value );

			// Skip empty option keys
			if ( '' === $option_key ) {
				continue;
			}

			// Maybe initialize the option vote bucket
			if ( ! isset( $votes[ $option_key ] ) || ! is_array( $votes[ $option_key ] ) ) {
				$votes[ $option_key ] = array();
			}

			// Maybe initialize the filter-type bucket
			if ( ! isset( $votes[ $option_key ][ $filter_type ] ) || ! is_array( $votes[ $option_key ][ $filter_type ] ) ) {
				$votes[ $option_key ][ $filter_type ] = array();
			}

			// Maybe initialize this value's vote entry
			if ( ! isset( $votes[ $option_key ][ $filter_type ][ $value_key ] ) || ! is_array( $votes[ $option_key ][ $filter_type ][ $value_key ] ) ) {
				$votes[ $option_key ][ $filter_type ][ $value_key ] = array(
					'value'      => $value,
					'weight'     => 0,
					'last_order' => (int) $vote_order,
				);
			}

			$votes[ $option_key ][ $filter_type ][ $value_key ]['weight']     = (int) $votes[ $option_key ][ $filter_type ][ $value_key ]['weight'] + $weight;
			$votes[ $option_key ][ $filter_type ][ $value_key ]['last_order'] = (int) $vote_order;
			$votes[ $option_key ][ $filter_type ][ $value_key ]['value']      = $value;
		}

		return $votes;
	}



	/**
	 * Build public filter value score metadata from internal votes.
	 *
	 * @param array $votes Internal votes from `add_filter_map_votes()`.
	 *
	 * @return array<string, array{has_multiple_values:bool,values:array<string,int>,winner:mixed,winner_count:int,winner_filter_type:string,suppressed:bool}>
	 */
	public function build_filter_value_scores( $votes ) {
		$votes        = is_array( $votes ) ? $votes : array();
		$scores       = array();
		$specificity  = $this->get_filter_type_specificity();

		// Iterate option keys that received filter votes
		foreach ( $votes as $option_key => $votes_by_type ) {
			// Skip malformed vote buckets
			if ( ! is_array( $votes_by_type ) || empty( $votes_by_type ) ) {
				continue;
			}

			$winning_type = '';
			$winning_spec = -1;

			// Iterate filter types that voted for this option to find the most specific owner
			foreach ( $votes_by_type as $filter_type => $value_votes ) {
				// Skip malformed type buckets
				if ( ! is_array( $value_votes ) || empty( $value_votes ) ) {
					continue;
				}

				$spec = isset( $specificity[ $filter_type ] ) ? (int) $specificity[ $filter_type ] : 0;

				// Prefer the highest specificity type that cast at least one positive-weight vote
				if ( $spec > $winning_spec && $this->filter_type_bucket_has_votes( $value_votes ) ) {
					$winning_type = (string) $filter_type;
					$winning_spec = $spec;
				}
			}

			// Skip options that ended without an owning filter type
			if ( '' === $winning_type || ! isset( $votes_by_type[ $winning_type ] ) || ! is_array( $votes_by_type[ $winning_type ] ) ) {
				continue;
			}

			$resolved = $this->resolve_winner_from_value_votes( $votes_by_type[ $winning_type ] );

			// Skip options that ended without a winner
			if ( ! is_array( $resolved ) || '' === $resolved['winner_key'] ) {
				continue;
			}

			$suppressed = ( self::FILTER_VALUE_OMIT === $resolved['winner'] );

			$scores[ (string) $option_key ] = array(
				'has_multiple_values' => count( $resolved['values'] ) > 1,
				'values'              => $resolved['values'],
				'winner'              => $suppressed ? null : $resolved['winner'],
				'winner_count'        => $resolved['winner_count'],
				'winner_filter_type'  => $winning_type,
				'suppressed'          => $suppressed,
			);
		}

		return $scores;
	}



	/**
	 * Whether a filter-type vote bucket has any positive weight.
	 *
	 * @param array $value_votes Votes keyed by value key.
	 *
	 * @return bool
	 */
	protected function filter_type_bucket_has_votes( $value_votes ) {
		$value_votes = is_array( $value_votes ) ? $value_votes : array();

		// Iterate value entries
		foreach ( $value_votes as $entry ) {
			// Skip malformed entries
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$weight = isset( $entry['weight'] ) ? (int) $entry['weight'] : 0;

			// Found a positive-weight vote
			if ( $weight > 0 ) {
				return true;
			}
		}

		return false;
	}



	/**
	 * Resolve the winning value inside one filter-type vote bucket.
	 *
	 * @param array $value_votes Votes keyed by value key.
	 *
	 * @return array{winner:mixed,winner_key:string,winner_count:int,values:array<string,int>}
	 */
	protected function resolve_winner_from_value_votes( $value_votes ) {
		$value_votes  = is_array( $value_votes ) ? $value_votes : array();
		$winner       = null;
		$winner_key   = '';
		$winner_count = 0;
		$winner_order = -1;
		$counts       = array();

		// Iterate distinct values voted within this filter type
		foreach ( $value_votes as $value_key => $entry ) {
			// Skip malformed entries
			if ( ! is_array( $entry ) || ! array_key_exists( 'value', $entry ) ) {
				continue;
			}

			$weight     = isset( $entry['weight'] ) ? (int) $entry['weight'] : 0;
			$last_order = isset( $entry['last_order'] ) ? (int) $entry['last_order'] : -1;
			$counts[ (string) $value_key ] = $weight;

			// Skip non-positive weights
			if ( $weight <= 0 ) {
				continue;
			}

			// Prefer higher weighted vote totals
			if ( $weight > $winner_count ) {
				$winner       = $entry['value'];
				$winner_key   = (string) $value_key;
				$winner_count = $weight;
				$winner_order = $last_order;
				continue;
			}

			// On a tied weight, prefer the later filter contribution
			if ( $weight === $winner_count && $last_order >= $winner_order ) {
				$winner       = $entry['value'];
				$winner_key   = (string) $value_key;
				$winner_count = $weight;
				$winner_order = $last_order;
			}
		}

		return array(
			'winner'       => $winner,
			'winner_key'   => $winner_key,
			'winner_count' => $winner_count,
			'values'       => $counts,
		);
	}



	/**
	 * Resolve profile values from defaults plus winning filter votes.
	 *
	 * Suppressed (`__omit`) keys keep the settings default and are not overridden.
	 *
	 * @param array $defaults Settings defaults.
	 * @param array $votes    Internal votes from `add_filter_map_votes()`.
	 *
	 * @return array
	 */
	public function resolve_profile_values_from_votes( $defaults, $votes ) {
		$defaults = is_array( $defaults ) ? $defaults : array();
		$scores   = $this->build_filter_value_scores( $votes );
		$resolved = $defaults;

		// Iterate winning filter values
		foreach ( $scores as $option_key => $score ) {
			// Skip incomplete or suppressed score rows
			if ( ! is_array( $score ) || ! empty( $score['suppressed'] ) || ! array_key_exists( 'winner', $score ) || null === $score['winner'] ) {
				continue;
			}

			$resolved[ $option_key ] = $score['winner'];
		}

		return $resolved;
	}



	/**
	 * Option keys suppressed by `__omit` after specificity resolution.
	 *
	 * @param array $filter_value_scores Filter value score metadata.
	 *
	 * @return string[]
	 */
	public function get_suppressed_option_keys( $filter_value_scores = array() ) {
		$filter_value_scores = is_array( $filter_value_scores ) ? $filter_value_scores : array();
		$keys                = array();

		// Iterate score rows
		foreach ( $filter_value_scores as $option_key => $score ) {
			// Skip non-suppressed rows
			if ( ! is_array( $score ) || empty( $score['suppressed'] ) ) {
				continue;
			}

			$keys[] = (string) $option_key;
		}

		return $keys;
	}



	/**
	 * Stable string key for counting votes for a filter value.
	 *
	 * @param mixed $value Filter map value (already normalized `.value`).
	 *
	 * @return string
	 */
	protected function get_filter_value_vote_key( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_array( $value ) ) {
			return (string) wp_json_encode( $value );
		}

		if ( is_object( $value ) ) {
			return (string) wp_json_encode( $value );
		}

		return (string) $value;
	}



	/**
	 * Merge option maps left to right (later keys overwrite).
	 *
	 * @param array ...$partials Option maps.
	 *
	 * @return array
	 */
	public function merge_profile_values( ...$partials ) {
		$merged = array();

		// Iterate partials in merge order
		foreach ( $partials as $partial ) {
			// Skip non-arrays
			if ( ! is_array( $partial ) ) {
				continue;
			}

			$merged = array_merge( $merged, $partial );
		}

		return $merged;
	}



	/**
	 * Hash recommendation-relevant answer keys for cache invalidation.
	 *
	 * @param array $answers Wizard answers.
	 *
	 * @return string
	 */
	public function get_answers_hash( $answers ) {
		$answers = is_array( $answers ) ? $answers : array();
		$payload = array(
			'business_industry'  => isset( $answers['business_industry'] ) ? (string) $answers['business_industry'] : '',
			'customer_audience'  => isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : '',
			'customer_location'  => isset( $answers['customer_location'] ) ? (string) $answers['customer_location'] : '',
			'product_types'      => isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? array_values( array_map( 'strval', $answers['product_types'] ) ) : array(),
			'delivery_methods'   => isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? array_values( array_map( 'strval', $answers['delivery_methods'] ) ) : array(),
		);

		sort( $payload['product_types'] );
		sort( $payload['delivery_methods'] );

		return md5( wp_json_encode( $payload ) );
	}



	/**
	 * Get plugin settings defaults.
	 *
	 * @return array
	 */
	protected function get_settings_defaults() {
		// Bail if settings class is unavailable
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) {
			return array();
		}

		$defaults = FluidCheckout_Settings::instance()->get_default_option_values();

		return is_array( $defaults ) ? $defaults : array();
	}



	/**
	 * Drop option keys that are not present in settings defaults.
	 *
	 * @param array $map      Option map.
	 * @param array $defaults Settings defaults (keys are the allowlist).
	 *
	 * @return array
	 */
	public function sanitize_option_map( $map, $defaults = null ) {
		$map = is_array( $map ) ? $map : array();

		// Maybe load defaults when not provided
		if ( null === $defaults ) {
			$defaults = $this->get_settings_defaults();
		}

		$defaults = is_array( $defaults ) ? $defaults : array();
		$allowed  = array_flip( array_keys( $defaults ) );
		$clean    = array();

		// Iterate map keys
		foreach ( $map as $key => $value ) {
			// Skip unknown keys
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$clean[ $key ] = $value;
		}

		return $clean;
	}



	/**
	 * Whether PRO recommendation option keys can be applied on finish.
	 *
	 * Lite default: false (promo rows only). PRO enables apply via
	 * `fc_guided_setup_apply_pro_recommendation_options`.
	 *
	 * @return bool
	 */
	public function should_apply_pro_recommendation_options() {
		/**
		 * Filters whether PRO recommendation option keys are applied on finish.
		 *
		 * @since 5.0.0
		 *
		 * @param bool $apply Default false on Lite.
		 */
		return (bool) apply_filters( 'fc_guided_setup_apply_pro_recommendation_options', false );
	}



	/**
	 * Max PRO recommendation rows shown before the "Show more" control on Lite.
	 *
	 * Lite default: limit to show on Lite.
	 * 
	 * "Show more recommendations"). PRO removes the cap via
	 * `fc_guided_setup_pro_recommendation_row_limit` (return null).
	 *
	 * @return int|null Null means no cap / no overflow UI.
	 */
	public function get_pro_recommendation_row_limit() {
		/**
		 * Filters how many PRO recommendation rows are visible before "Show more" on Lite.
		 *
		 * Return null for no cap (all PRO rows visible; used by PRO).
		 *
		 * @since 5.0.0
		 *
		 * @param int|null $limit Default limit to show on Lite.
		 */
		$limit = apply_filters( 'fc_guided_setup_pro_recommendation_row_limit', 2 );

		if ( null === $limit ) {
			return null;
		}

		return max( 0, (int) $limit );
	}



	/**
	 * Whether a recommendation row matches the active profile value (or default).
	 *
	 * @param array $row Recommended row with option_key and value.
	 *
	 * @return bool
	 */
	public function is_recommendation_already_applied( $row ) {
		$row        = is_array( $row ) ? $row : array();
		$option_key = isset( $row['option_key'] ) ? (string) $row['option_key'] : ( isset( $row['id'] ) ? (string) $row['id'] : '' );

		// Bail if the row has no option key or recommended value
		if ( '' === $option_key || ! array_key_exists( 'value', $row ) ) {
			return false;
		}

		// Bail if settings are unavailable
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) {
			return false;
		}

		$current = FluidCheckout_Settings::instance()->get_option( $option_key );

		return $this->recommendation_values_match( $current, $row['value'] );
	}



	/**
	 * Build recommended step rows (Lite + PRO), all checked.
	 *
	 * Checkout layout is always first. Other rows are sorted by score.
	 * All matching PRO rows are included; Lite UI may collapse overflow.
	 *
	 * @param array $answers             Wizard answers.
	 * @param array $profile_values      Merged profile values.
	 * @param array $filter_value_scores Filter value score metadata.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_recommended_rows( $answers, $profile_values, $filter_value_scores = array() ) {
		$answers             = is_array( $answers ) ? $answers : array();
		$profile_values      = is_array( $profile_values ) ? $profile_values : array();
		$filter_value_scores = is_array( $filter_value_scores ) ? $filter_value_scores : array();
		$suppressed_keys     = $this->get_suppressed_option_keys( $filter_value_scores );
		$layout_definition   = $this->get_checkout_layout_recommendation_definition( $filter_value_scores );
		$scored              = $this->get_scored_recommendation_rows( $answers, $profile_values, $suppressed_keys );
		$lite_rows           = array();
		$pro_rows            = array();

		// Iterate scored registry rows
		foreach ( $scored as $item ) {
			$definition = $item['definition'];
			$option_key = isset( $definition['option_key'] ) ? (string) $definition['option_key'] : '';

			// Skip checkout layout, handled as the first recommendation
			if ( 'fc_checkout_layout' === $option_key ) {
				continue;
			}

			// Skip options suppressed by `__omit`
			if ( in_array( $option_key, $suppressed_keys, true ) ) {
				continue;
			}

			// Collect PRO rows separately
			if ( ! empty( $definition['is_pro'] ) ) {
				$pro_rows[] = $definition;
				continue;
			}

			$lite_rows[] = $definition;
		}

		$rows = array();

		// Always lead with the matching checkout layout recommendation
		if ( is_array( $layout_definition ) ) {
			$rows[] = $this->normalize_recommended_row( $layout_definition );
		}

		// Iterate Lite rows (already sorted by score)
		foreach ( $lite_rows as $definition ) {
			$rows[] = $this->normalize_recommended_row( $definition );
		}

		// Iterate all matching PRO rows (Lite UI may collapse overflow behind "Show more")
		foreach ( $pro_rows as $definition ) {
			$rows[] = $this->normalize_recommended_row( $definition );
		}

		return $rows;
	}



	/**
	 * Resolve the checkout layout value from filter votes or settings default.
	 *
	 * Exclusive to `fc_checkout_layout`: when filters do not recommend a layout value,
	 * fall back to the settings default so a matching registry row can still be shown.
	 *
	 * @param array $filter_value_scores Filter value score metadata.
	 *
	 * @return mixed
	 */
	public function resolve_checkout_layout_value( $filter_value_scores = array() ) {
		$option_key          = 'fc_checkout_layout';
		$filter_value_scores = is_array( $filter_value_scores ) ? $filter_value_scores : array();

		// Prefer the winning concrete filter value when filters recommended a layout
		if ( isset( $filter_value_scores[ $option_key ] ) && is_array( $filter_value_scores[ $option_key ] ) ) {
			$score = $filter_value_scores[ $option_key ];

			// Ignore suppressed or empty layout winners and fall back to the default
			if ( empty( $score['suppressed'] ) && array_key_exists( 'winner', $score ) && null !== $score['winner'] ) {
				return $score['winner'];
			}
		}

		$defaults = $this->get_settings_defaults();

		// Fall back to the settings default for checkout layout only
		if ( array_key_exists( $option_key, $defaults ) ) {
			return $defaults[ $option_key ];
		}

		return 'multi-step';
	}



	/**
	 * Get the registry definition matching the resolved checkout layout value.
	 *
	 * @param array $filter_value_scores Filter value score metadata.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function get_checkout_layout_recommendation_definition( $filter_value_scores = array() ) {
		$option_key   = 'fc_checkout_layout';
		$layout_value = $this->resolve_checkout_layout_value( $filter_value_scores );
		$registry     = $this->get_recommendation_registry();

		// Iterate registry definitions for the matching layout value
		foreach ( $registry as $definition ) {
			$definition_key = isset( $definition['option_key'] ) ? (string) $definition['option_key'] : '';

			// Skip non-layout rows
			if ( $option_key !== $definition_key ) {
				continue;
			}

			// Skip rows without a declared recommendation value
			if ( ! array_key_exists( 'value', $definition ) ) {
				continue;
			}

			// Skip rows that do not match the resolved layout value
			if ( ! $this->recommendation_values_match( $layout_value, $definition['value'] ) ) {
				continue;
			}

			return $definition;
		}

		return null;
	}



	/**
	 * Normalize a recommended row for the wizard UI.
	 *
	 * @param array $definition Registry definition.
	 *
	 * @return array<string, mixed>
	 */
	protected function normalize_recommended_row( $definition ) {
		$option_key   = isset( $definition['option_key'] ) ? (string) $definition['option_key'] : '';
		$feature_slug = isset( $definition['feature_slug'] ) ? sanitize_title( (string) $definition['feature_slug'] ) : '';

		return array(
			'id'           => $option_key,
			'option_key'   => $option_key,
			'feature_slug' => $feature_slug,
			'value'        => array_key_exists( 'value', $definition ) ? $definition['value'] : null,
			'title'        => isset( $definition['title'] ) ? (string) $definition['title'] : '',
			'description'  => isset( $definition['description'] ) ? (string) $definition['description'] : '',
			'checked'      => true,
			'is_pro'       => ! empty( $definition['is_pro'] ),
		);
	}



	/**
	 * Load the recommendation registry definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_recommendation_registry() {
		$path = dirname( __FILE__ ) . '/recommendations/recommendation-registry.php';

		// Bail if registry file is missing
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$registry = include $path;

		return is_array( $registry ) ? $registry : array();
	}



	/**
	 * Score registry rows that match filtered profile values, highest score first.
	 *
	 * A row is included only when score > 0 and filtered_values[ option_key ] equals the row value.
	 * Scores are clamped to a maximum of 100 and define display sorting order (highest first).
	 *
	 * @param array    $answers          Wizard answers.
	 * @param array    $profile_values   Merged profile values.
	 * @param string[] $suppressed_keys  Option keys suppressed by `__omit`.
	 *
	 * @return array<int, array{score:int,definition:array}>
	 */
	protected function get_scored_recommendation_rows( $answers, $profile_values, $suppressed_keys = array() ) {
		$registry         = $this->get_recommendation_registry();
		$defaults         = $this->get_settings_defaults();
		$profile_values   = is_array( $profile_values ) ? $profile_values : array();
		$suppressed_keys  = is_array( $suppressed_keys ) ? $suppressed_keys : array();
		$scored           = array();

		// Iterate registry definitions
		foreach ( $registry as $definition ) {
			$option_key = isset( $definition['option_key'] ) ? (string) $definition['option_key'] : '';

			// Skip checkout layout, always handled first via resolve_checkout_layout_value()
			if ( 'fc_checkout_layout' === $option_key ) {
				continue;
			}

			// Skip options suppressed by `__omit`
			if ( in_array( $option_key, $suppressed_keys, true ) ) {
				continue;
			}

			// Skip incomplete definitions or keys outside settings defaults
			if ( '' === $option_key || ! array_key_exists( $option_key, $defaults ) ) {
				continue;
			}

			// Skip rows without a declared recommendation value
			if ( ! array_key_exists( 'value', $definition ) ) {
				continue;
			}

			// Skip rows whose value does not match the filtered recommendation map
			if ( ! array_key_exists( $option_key, $profile_values ) || ! $this->recommendation_values_match( $profile_values[ $option_key ], $definition['value'] ) ) {
				continue;
			}

			$score = 0;

			// Maybe use a scoring callback
			if ( isset( $definition['score'] ) && is_callable( $definition['score'] ) ) {
				$score = (int) call_user_func( $definition['score'], $answers, $profile_values );
			}

			// Cap scores at 100
			$score = min( 100, $score );

			// Skip rows that scored zero or below
			if ( $score <= 0 ) {
				continue;
			}

			$scored[] = array(
				'score'      => $score,
				'definition' => $definition,
			);
		}

		usort(
			$scored,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return $scored;
	}



	/**
	 * Whether a filtered profile value matches a registry recommendation value.
	 *
	 * @param mixed $profile_value Value from merged profile_values.
	 * @param mixed $expected      Registry recommendation value.
	 *
	 * @return bool
	 */
	protected function recommendation_values_match( $profile_value, $expected ) {
		if ( is_bool( $expected ) || is_bool( $profile_value ) ) {
			return (bool) $profile_value === (bool) $expected;
		}

		return (string) $profile_value === (string) $expected;
	}



	/**
	 * Build progress recommendations blob from engine output, preserving or resetting user overrides.
	 *
	 * @param array $engine_result Engine output from `get_recommendation()`.
	 * @param array $existing      Existing progress `recommendations` array.
	 *
	 * @return array
	 */
	public function build_progress_recommendations( $engine_result, $existing = array() ) {
		$engine_result = is_array( $engine_result ) ? $engine_result : array();
		$existing      = is_array( $existing ) ? $existing : array();

		$new_hash  = isset( $engine_result['answers_hash'] ) ? (string) $engine_result['answers_hash'] : '';
		$old_hash  = isset( $existing['answers_hash'] ) ? (string) $existing['answers_hash'] : '';
		$overrides = isset( $existing['user_overrides'] ) ? $existing['user_overrides'] : array();

		// Reset toggles when answers that feed recommendations changed
		if ( $new_hash !== $old_hash ) {
			$overrides = array();
		}

		return array(
			'answers_hash'        => $new_hash,
			'profile_values'      => isset( $engine_result['profile_values'] ) && is_array( $engine_result['profile_values'] ) ? $engine_result['profile_values'] : array(),
			'recommended'         => isset( $engine_result['recommended'] ) && is_array( $engine_result['recommended'] ) ? $engine_result['recommended'] : array(),
			'filter_value_scores' => isset( $engine_result['filter_value_scores'] ) && is_array( $engine_result['filter_value_scores'] ) ? $engine_result['filter_value_scores'] : array(),
			'user_overrides'      => $this->normalize_excluded_option_keys( $overrides ),
		);
	}



	/**
	 * Normalize user_overrides to a list of excluded option keys.
	 *
	 * @param array $user_overrides Stored overrides (list of keys, or legacy key => value map).
	 *
	 * @return string[]
	 */
	public function normalize_excluded_option_keys( $user_overrides ) {
		$user_overrides = is_array( $user_overrides ) ? $user_overrides : array();
		$keys           = array();

		// List of option keys
		if ( array_values( $user_overrides ) === $user_overrides ) {
			foreach ( $user_overrides as $key ) {
				$key = (string) $key;

				// Skip empty keys
				if ( '' === $key ) {
					continue;
				}

				$keys[] = $key;
			}

			return array_values( array_unique( $keys ) );
		}

		// Legacy map: keys are option keys
		foreach ( array_keys( $user_overrides ) as $key ) {
			$key = (string) $key;

			// Skip empty keys
			if ( '' === $key ) {
				continue;
			}

			$keys[] = $key;
		}

		return array_values( array_unique( $keys ) );
	}



	/**
	 * Get option values to apply on finish.
	 *
	 * Starts from filtered profile_values, then drops:
	 * - option keys the merchant unchecked on recommended_setup (user_overrides list)
	 * - option keys belonging to PRO recommended rows when PRO is not active
	 *
	 * @param array $recommendations Progress recommendations blob.
	 *
	 * @return array
	 */
	public function get_values_to_apply( $recommendations ) {
		$recommendations = is_array( $recommendations ) ? $recommendations : array();
		$profile_values  = isset( $recommendations['profile_values'] ) && is_array( $recommendations['profile_values'] ) ? $recommendations['profile_values'] : array();
		$excluded_keys   = $this->normalize_excluded_option_keys( isset( $recommendations['user_overrides'] ) ? $recommendations['user_overrides'] : array() );
		$recommended     = isset( $recommendations['recommended'] ) && is_array( $recommendations['recommended'] ) ? $recommendations['recommended'] : array();
		$defaults        = $this->get_settings_defaults();
		$values          = $this->sanitize_option_map( $profile_values, $defaults );
		$apply_pro       = $this->should_apply_pro_recommendation_options();

		// Iterate recommended rows
		foreach ( $recommended as $row ) {
			$option_key = isset( $row['option_key'] ) ? (string) $row['option_key'] : ( isset( $row['id'] ) ? (string) $row['id'] : '' );

			// Skip incomplete rows
			if ( '' === $option_key ) {
				continue;
			}

			// Drop PRO promo option keys from Lite apply
			if ( ! empty( $row['is_pro'] ) && ! $apply_pro ) {
				unset( $values[ $option_key ] );
			}
		}

		// Iterate excluded toggles
		foreach ( $excluded_keys as $option_key ) {
			unset( $values[ $option_key ] );
		}

		return $values;
	}



	/**
	 * Build user_overrides from recommended_setup toggle form data.
	 *
	 * Unchecked option keys are stored as an exclusion list and omitted from
	 * the filtered profile_values when applying settings on finish. Checked option keys
	 * are posted as `recommendation_toggles[]` (unchecked boxes are omitted).
	 * PRO rows are ignored on Lite (disabled promos) and included when PRO is active.
	 *
	 * @param array $recommended Recommended rows from progress (pending rows only when hiding applied).
	 * @param array $form_data   Posted form data.
	 *
	 * @return string[]
	 */
	public function build_user_overrides_from_form( $recommended, $form_data ) {
		$recommended  = is_array( $recommended ) ? $recommended : array();
		$form_data    = is_array( $form_data ) ? $form_data : array();
		$checked_raw  = isset( $form_data['recommendation_toggles'] ) ? $form_data['recommendation_toggles'] : array();
		$checked_raw  = is_array( $checked_raw ) ? $checked_raw : array( $checked_raw );
		$checked_keys = array_map( 'strval', $checked_raw );
		$excluded     = array();
		$apply_pro    = $this->should_apply_pro_recommendation_options();

		// Iterate recommended rows
		foreach ( $recommended as $row ) {
			// Skip PRO rows on Lite (disabled promos)
			if ( ! empty( $row['is_pro'] ) && ! $apply_pro ) {
				continue;
			}

			$option_key = isset( $row['option_key'] ) ? (string) $row['option_key'] : ( isset( $row['id'] ) ? (string) $row['id'] : '' );

			// Skip rows without an option key
			if ( '' === $option_key ) {
				continue;
			}

			// Skip checked rows (default recommended state)
			if ( in_array( $option_key, $checked_keys, true ) ) {
				continue;
			}

			$excluded[] = $option_key;
		}

		return array_values( array_unique( $excluded ) );
	}

}

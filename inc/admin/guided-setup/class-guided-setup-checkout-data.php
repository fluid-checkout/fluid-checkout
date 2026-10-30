<?php
defined( 'ABSPATH' ) || exit;

/**
 * Guided setup checkout wizard — product types, delivery methods, and answer helpers.
 */
class FluidCheckout_Guided_Setup_Checkout_Data {

	/**
	 * FC product type slugs for the checkout guided setup.
	 *
	 * @return array<string, string> Slug => label.
	 */
	public static function get_product_types() {
		return array(
			'physical'      => __( 'Physical products', 'fluid-checkout' ),
			'downloads'     => __( 'Downloads', 'fluid-checkout' ),
			'services'      => __( 'Services', 'fluid-checkout' ),
			'subscriptions' => __( 'Subscriptions', 'fluid-checkout' ),
			'memberships'   => __( 'Memberships', 'fluid-checkout' ),
			'bookings'      => __( 'Bookings', 'fluid-checkout' ),
		);
	}



	/**
	 * Delivery method slugs (UI order).
	 *
	 * @return array<string, string> Slug => label.
	 */
	public static function get_delivery_methods() {
		return array(
			'ship_to_address'      => __( 'Shipping to address', 'fluid-checkout' ),
			'local_pickup'         => __( 'Pickup in store or at a location', 'fluid-checkout' ),
			'ship_to_pickup_point' => __( 'Ship to pickup point', 'fluid-checkout' ),
			'local_delivery'       => __( 'Local delivery', 'fluid-checkout' ),
		);
	}



	/**
	 * WooCommerce onboarding product type slug => FC slug.
	 *
	 * @return array<string, string>
	 */
	public static function get_wc_onboarding_product_type_map() {
		return array(
			'physical'      => 'physical',
			'downloads'     => 'downloads',
			'subscriptions' => 'subscriptions',
			'memberships'   => 'memberships',
			'bookings'      => 'bookings',
		);
	}



	/**
	 * Industry options (WooCommerce slugs when available, FC labels).
	 *
	 * @return array<string, array{label:string,use_description:bool}>
	 */
	public static function get_industries() {
		if ( class_exists( '\Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingIndustries' ) ) {
			return \Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingIndustries::get_allowed_industries();
		}

		return array(
			'fashion-apparel-accessories' => array(
				'label'             => __( 'Fashion, apparel, and accessories', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'health-beauty'               => array(
				'label'             => __( 'Health and beauty', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'electronics-computers'       => array(
				'label'             => __( 'Electronics and computers', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'food-drink'                  => array(
				'label'             => __( 'Food and drink', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'home-furniture-garden'       => array(
				'label'             => __( 'Home, furniture, and garden', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'education-and-learning'      => array(
				'label'             => __( 'Education and learning', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'sports-and-recreation'       => array(
				'label'             => __( 'Sports and recreation', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'arts-and-crafts'             => array(
				'label'             => __( 'Arts and crafts', 'fluid-checkout' ),
				'use_description'   => false,
				'description_label' => '',
			),
			'other'                       => array(
				'label'             => __( 'Other', 'fluid-checkout' ),
				'use_description'   => true,
				'description_label' => __( 'Description', 'fluid-checkout' ),
			),
		);
	}



	/**
	 * Default answers merged with stored progress.
	 *
	 * @param array $stored Stored answers.
	 *
	 * @return array
	 */
	public static function get_default_answers( $stored = array() ) {
		$defaults = array(
			'business_industry'              => '',
			'business_industry_other'        => '',
			'store_country'                  => self::get_store_country_code(),
			'selling_to_mode'                => '',
			'selling_to_country_codes'       => array(),
			'shipping_to_mode'               => '',
			'shipping_to_country_codes'      => array(),
			'newsletter_opt_in'              => false,
			'email'                          => '',
			'telemetry_opt_in'               => false,
			'product_types'                  => self::get_default_product_types(),
			'delivery_methods'               => self::get_default_delivery_methods(),
		);

		$merged = wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );

		$merged['product_types']     = self::sanitize_slug_list( $merged['product_types'], array_keys( self::get_product_types() ) );
		$merged['delivery_methods']  = self::sanitize_slug_list( $merged['delivery_methods'], array_keys( self::get_delivery_methods() ) );
		$merged['newsletter_opt_in'] = self::normalize_bool( $merged['newsletter_opt_in'] );
		$merged['telemetry_opt_in']  = self::normalize_bool( $merged['telemetry_opt_in'] );

		return $merged;
	}



	/**
	 * Read WooCommerce General location settings into answer fields.
	 *
	 * @return array
	 */
	public static function get_store_location_answers() {
		$answers = array(
			'store_country'             => self::get_store_country_code(),
			'selling_to_mode'           => '',
			'selling_to_country_codes'  => array(),
			'shipping_to_mode'          => '',
			'shipping_to_country_codes' => array(),
		);

		if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
			return $answers;
		}

		$allowed_countries = WC()->countries->get_allowed_countries();
		$answers['selling_to_country_codes'] = array_keys( $allowed_countries );

		$selling_mode = get_option( 'woocommerce_allowed_countries', 'all' );
		$answers['selling_to_mode'] = sanitize_text_field( (string) $selling_mode );

		if ( 'specific' === $selling_mode ) {
			$specific = get_option( 'woocommerce_specific_allowed_countries', array() );
			$answers['selling_to_country_codes'] = self::sanitize_country_codes( $specific );
		} elseif ( 'all_except' === $selling_mode ) {
			$except = get_option( 'woocommerce_all_except_countries', array() );
			$except = self::sanitize_country_codes( $except );
			$answers['selling_to_country_codes'] = array_values( array_diff( array_keys( $allowed_countries ), $except ) );
		}

		$ship_to = get_option( 'woocommerce_ship_to_countries', '' );
		$answers['shipping_to_mode'] = sanitize_text_field( (string) $ship_to );

		if ( 'disabled' === $ship_to ) {
			$answers['shipping_to_country_codes'] = array();
		} elseif ( 'specific' === $ship_to ) {
			$answers['shipping_to_country_codes'] = self::sanitize_country_codes( get_option( 'woocommerce_specific_ship_to_countries', array() ) );
		} else {
			$shipping_countries = WC()->countries->get_shipping_countries();
			$answers['shipping_to_country_codes'] = array_keys( $shipping_countries );
		}

		return $answers;
	}



	/**
	 * Sanitize answer payload from a step form submission.
	 *
	 * @param string $step_slug Step slug.
	 * @param array  $form_data Raw form data.
	 * @param array  $existing  Existing answers.
	 *
	 * @return array|WP_Error
	 */
	public static function sanitize_step_form_data( $step_slug, $form_data, $existing = array() ) {
		$answers = self::get_default_answers( $existing );
		$form_data = is_array( $form_data ) ? $form_data : array();

		if ( 'about_store' === $step_slug ) {
			$industry = isset( $form_data['business_industry'] ) ? sanitize_text_field( wp_unslash( $form_data['business_industry'] ) ) : '';
			$industries = self::get_industries();

			if ( empty( $industry ) || ! array_key_exists( $industry, $industries ) ) {
				return new WP_Error( 'fc_guided_setup_invalid_industry', __( 'Please select what type of products or services you sell.', 'fluid-checkout' ) );
			}

			$answers['business_industry']         = $industry;
			$answers['business_industry_other'] = '';

			$answers = array_merge( $answers, self::get_store_location_answers() );

			$answers['newsletter_opt_in'] = self::normalize_bool( isset( $form_data['newsletter_opt_in'] ) ? $form_data['newsletter_opt_in'] : false );
			$answers['telemetry_opt_in']  = self::normalize_bool( isset( $form_data['telemetry_opt_in'] ) ? $form_data['telemetry_opt_in'] : false );

			if ( $answers['newsletter_opt_in'] ) {
				$email = isset( $form_data['email'] ) ? sanitize_email( wp_unslash( $form_data['email'] ) ) : '';

				if ( empty( $email ) || ! is_email( $email ) ) {
					return new WP_Error( 'fc_guided_setup_invalid_email', __( 'Please enter a valid email address for the newsletter.', 'fluid-checkout' ) );
				}

				$answers['email'] = $email;
			} else {
				$answers['email'] = '';
			}

			return $answers;
		}

		if ( 'product_types' === $step_slug ) {
			$product_types = isset( $form_data['product_types'] ) ? $form_data['product_types'] : array();
			$answers['product_types'] = self::sanitize_slug_list( $product_types, array_keys( self::get_product_types() ) );

			if ( empty( $answers['product_types'] ) ) {
				return new WP_Error( 'fc_guided_setup_product_types_required', __( 'Please select at least one product type.', 'fluid-checkout' ) );
			}

			$has_physical = in_array( 'physical', $answers['product_types'], true );

			if ( $has_physical ) {
				$delivery = isset( $form_data['delivery_methods'] ) ? $form_data['delivery_methods'] : array();
				$answers['delivery_methods'] = self::sanitize_slug_list( $delivery, array_keys( self::get_delivery_methods() ) );

				if ( empty( $answers['delivery_methods'] ) ) {
					return new WP_Error( 'fc_guided_setup_delivery_required', __( 'Please select at least one delivery method for physical products.', 'fluid-checkout' ) );
				}
			}

			return $answers;
		}

		return $answers;
	}



	/**
	 * Sanitize debounced partial save (product types step fields only).
	 *
	 * @param array $form_data Form data.
	 * @param array $existing  Existing answers.
	 *
	 * @return array
	 */
	public static function sanitize_debounced_form_data( $form_data, $existing = array() ) {
		$answers = self::get_default_answers( $existing );
		$form_data = is_array( $form_data ) ? $form_data : array();

		if ( isset( $form_data['product_types'] ) ) {
			$answers['product_types'] = self::sanitize_slug_list( $form_data['product_types'], array_keys( self::get_product_types() ) );
		}

		if ( isset( $form_data['delivery_methods'] ) ) {
			$answers['delivery_methods'] = self::sanitize_slug_list( $form_data['delivery_methods'], array_keys( self::get_delivery_methods() ) );
		}

		return $answers;
	}



	/**
	 * Store country ISO code.
	 *
	 * @return string
	 */
	public static function get_store_country_code() {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			return sanitize_text_field( (string) WC()->countries->get_base_country() );
		}

		return '';
	}



	/**
	 * Localized store country name for display.
	 *
	 * @return string
	 */
	public static function get_store_country_display_name() {
		$code = self::get_store_country_code();

		if ( '' === $code || ! function_exists( 'WC' ) || ! WC()->countries ) {
			return $code;
		}

		$countries = WC()->countries->get_countries();

		return isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}



	/**
	 * Default product types for new installs.
	 *
	 * @return string[]
	 */
	public static function get_default_product_types() {
		$mapped = self::map_wc_onboarding_product_types();

		if ( ! empty( $mapped ) ) {
			return $mapped;
		}

		return array( 'physical' );
	}



	/**
	 * Default delivery methods when physical is selected.
	 *
	 * @return string[]
	 */
	public static function get_default_delivery_methods() {
		$methods = array( 'ship_to_address' );

		if ( class_exists( 'FluidCheckout_Settings' ) ) {
			$local_pickup = FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_local_pickup' );

			if ( 'yes' === $local_pickup ) {
				$methods[] = 'local_pickup';
			}
		}

		return array_values( array_unique( $methods ) );
	}



	/**
	 * Map WC onboarding profile product types to FC slugs.
	 *
	 * @return string[]
	 */
	public static function map_wc_onboarding_product_types() {
		$profile = get_option( 'woocommerce_onboarding_profile', array() );

		if ( empty( $profile['product_types'] ) || ! is_array( $profile['product_types'] ) ) {
			return array();
		}

		$map   = self::get_wc_onboarding_product_type_map();
		$types = array();

		foreach ( $profile['product_types'] as $wc_slug ) {
			$wc_slug = sanitize_text_field( (string) $wc_slug );

			if ( isset( $map[ $wc_slug ] ) ) {
				$types[] = $map[ $wc_slug ];
			}
		}

		return array_values( array_unique( $types ) );
	}



	/**
	 * Pre-fill industry from WooCommerce onboarding profile.
	 *
	 * @return string
	 */
	public static function get_prefill_industry() {
		$profile = get_option( 'woocommerce_onboarding_profile', array() );

		if ( empty( $profile['industry'] ) || ! is_array( $profile['industry'] ) ) {
			return '';
		}

		$first = reset( $profile['industry'] );

		if ( is_array( $first ) && ! empty( $first['slug'] ) ) {
			return sanitize_text_field( (string) $first['slug'] );
		}

		if ( is_string( $first ) ) {
			return sanitize_text_field( $first );
		}

		return '';
	}



	/**
	 * Sanitize list of slugs against allowed keys.
	 *
	 * @param mixed $values  Submitted values.
	 * @param array $allowed Allowed slugs.
	 *
	 * @return string[]
	 */
	public static function sanitize_slug_list( $values, $allowed ) {
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$sanitized = array();

		foreach ( $values as $value ) {
			$value = sanitize_text_field( (string) $value );

			if ( in_array( $value, $allowed, true ) ) {
				$sanitized[] = $value;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}



	/**
	 * Sanitize ISO country codes.
	 *
	 * @param mixed $codes Country codes.
	 *
	 * @return string[]
	 */
	protected static function sanitize_country_codes( $codes ) {
		if ( ! is_array( $codes ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $codes as $code ) {
			$code = sanitize_text_field( (string) $code );

			if ( 2 === strlen( $code ) ) {
				$sanitized[] = strtoupper( $code );
			}
		}

		return array_values( array_unique( $sanitized ) );
	}



	/**
	 * Normalize checkbox-like values to bool.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public static function normalize_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( $value );
			return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
		}

		return ! empty( $value );
	}

}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Recommended_setup row definitions (Lite and PRO).
 *
 * Each item maps to one option key and the setting value that must match the filtered
 * values after recommendation filters run. Keys (in order):
 * option_key, value, title, description, score( $answers, $filtered_values ); optional is_pro (true for PRO only, omitted = Lite);
 * optional feature_slug for PRO promo tracking (`mtm_kwd=guided-setup-{feature_slug}`).
 *
 * Score callbacks return 0–100 (`0` excludes the row) and define display sorting order
 * (highest first). Rows whose value does not match filtered_values[ option_key ] are also
 * excluded. Filter maps may set `__omit` to suppress a key (after filter-type specificity).
 * Checkout layout (`fc_checkout_layout`) is always the first recommendation and ignores
 * score for inclusion: the filter winner is used when present, otherwise the settings
 * default. The recommendation id is the option_key. Lite rows that match are included.
 * Up to 3 matching PRO rows are included.
 *
 * File order: Lite, then PRO. Within each group, rows are sorted by relatedness.
 *
 * @return array<int, array<string, mixed>>
 */
return array(

	// ----------------------------------------
	// Lite — checkout layout
	// ----------------------------------------

	array(
		'option_key'  => 'fc_checkout_layout',
		'value'       => 'multi-step',
		'title'       => __( 'Multi-step checkout', 'fluid-checkout' ),
		'description' => __( 'Clear steps with few fields at a time to guide customers through checkout and keep them focused.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();

			// Prefer multi-step when the catalog includes physical products or is mixed
			if ( in_array( 'physical', $types, true ) || count( $types ) > 1 || empty( $types ) ) {
				return 90;
			}

			return 0;
		},
	),
	array(
		'option_key'  => 'fc_checkout_layout',
		'value'       => 'single-step',
		'title'       => __( 'Single-step checkout', 'fluid-checkout' ),
		'description' => __( 'Show all fields at once. Ideal for short checkouts such as digital products or services that don\'t require much information.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();

			// Prefer single-step for downloads or services only
			if ( 1 === count( $types ) && ( in_array( 'downloads', $types, true ) || in_array( 'services', $types, true ) ) ) {
				return 100;
			}

			return 0;
		},
	),



	// ----------------------------------------
	// Lite — shipping
	// ----------------------------------------

	array(
		'option_key'  => 'fc_shipping_methods_substep_position',
		'value'       => 'before_shipping_address',
		'title'       => __( 'Shipping methods before address', 'fluid-checkout' ),
		'description' => __( 'Show shipping methods before the address so customers can see other delivery options such as local pickup.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();
			$location = isset( $answers['customer_location'] ) ? (string) $answers['customer_location'] : '';

			// Bail if physical products are not selected
			if ( ! in_array( 'physical', $types, true ) ) {
				return 0;
			}

			// Prefer before-address when offering pickup/local delivery options
			if ( array_intersect( array( 'local_pickup', 'ship_to_pickup_point', 'local_delivery' ), $delivery ) ) {
				return 95;
			}

			// Prefer before-address for single-country physical catalogs
			if ( 'domestic' === $location ) {
				return 85;
			}

			return 0;
		},
	),
	array(
		'option_key'  => 'fc_shipping_methods_substep_position',
		'value'       => 'after_shipping_address',
		'title'       => __( 'Shipping methods after address', 'fluid-checkout' ),
		'description' => __( 'Ask for the shipping address first, then show shipping methods that match that destination.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();
			$location = isset( $answers['customer_location'] ) ? (string) $answers['customer_location'] : '';

			// Bail if physical products are not selected
			if ( ! in_array( 'physical', $types, true ) ) {
				return 0;
			}

			// After-address is for multi-country catalogs without pickup/local delivery
			if ( 'international' === $location && ! array_intersect( array( 'local_pickup', 'ship_to_pickup_point', 'local_delivery' ), $delivery ) ) {
				return 80;
			}

			return 0;
		},
	),
	array(
		'option_key'  => 'fc_shipping_phone_field_visibility',
		'value'       => 'optional',
		'title'       => __( 'Optional shipping phone', 'fluid-checkout' ),
		'description' => __( 'Show a shipping phone field customers can fill when carriers or delivery partners need a contact number.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();

			// Bail if physical products are not selected
			if ( ! in_array( 'physical', $types, true ) ) {
				return 0;
			}

			return 55;
		},
	),
	array(
		'option_key'  => 'fc_shipping_phone_field_visibility',
		'value'       => 'required',
		'title'       => __( 'Require shipping phone', 'fluid-checkout' ),
		'description' => __( 'Require a phone number on the shipping address for delivery coordination and failed-delivery recovery.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';
			$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();

			// Bail if physical products are not selected
			if ( ! in_array( 'physical', $types, true ) ) {
				return 0;
			}

			$score = 50;

			if ( 'b2b' === $audience ) {
				$score += 30;
			}

			if ( in_array( 'local_delivery', $delivery, true ) ) {
				$score += 20;
			}

			return min( 100, $score );
		},
	),



	// ----------------------------------------
	// Lite — account access
	// ----------------------------------------

	array(
		'option_key'  => 'woocommerce_enable_checkout_login_reminder',
		'value'       => 'yes',
		'title'       => __( 'Login reminder at checkout', 'fluid-checkout' ),
		'description' => __( 'Offer a login link at checkout so returning customers can reuse saved details faster.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			return 85;
		},
	),
	array(
		'option_key'  => 'woocommerce_enable_guest_checkout',
		'value'       => 'no',
		'title'       => __( 'Require account to checkout', 'fluid-checkout' ),
		'description' => __( 'Turn off guest checkout so customers must log in or create an account before placing an order.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';

			return 'b2b' === $audience ? 90 : 0;
		},
	),



	// ----------------------------------------
	// Lite — B2B billing and layout chrome
	// ----------------------------------------

	array(
		'option_key'  => 'woocommerce_checkout_company_field',
		'value'       => 'required',
		'title'       => __( 'Require billing company', 'fluid-checkout' ),
		'description' => __( 'Make the billing company field required so invoices include a company name for business orders.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';

			return 'b2b' === $audience ? 85 : 0;
		},
	),
	array(
		'option_key'  => 'fc_hide_site_header_footer_at_checkout',
		'value'       => 'no',
		'title'       => __( 'Keep theme header and footer', 'fluid-checkout' ),
		'description' => __( 'Show the store header and footer at checkout instead of the distraction-free layout. Useful for B2B stores that need site navigation during checkout.', 'fluid-checkout' ),
		'score'       => function ( $answers, $filtered_values = array() ) {
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';

			return 'b2b' === $audience ? 80 : 0;
		},
	),



	// ----------------------------------------
	// PRO — cart
	// ----------------------------------------

	array(
		'is_pro'       => true,
		'feature_slug' => 'edit-cart',
		'option_key'   => 'fc_pro_enable_checkout_edit_cart',
		'value'        => 'yes',
		'title'        => __( 'Edit cart at checkout', 'fluid-checkout' ),
		'description'  => __( 'Let customers change quantities and remove items without leaving checkout.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$types = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$score = 10;

			if ( in_array( 'physical', $types, true ) ) {
				$score += 25;
			}

			return $score;
		},
	),



	// ----------------------------------------
	// PRO — delivery and addresses
	// ----------------------------------------

	array(
		'is_pro'       => true,
		'feature_slug' => 'local-pickup',
		'option_key'   => 'fc_enable_checkout_local_pickup',
		'value'        => 'yes',
		'title'        => __( 'Local pickup or in-store collection', 'fluid-checkout' ),
		'description'  => __( 'Let customers choose at checkout where to pick up their order.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();

			// Bail if physical products are not selected
			if ( ! in_array( 'physical', $types, true ) ) {
				return 0;
			}

			if ( in_array( 'local_pickup', $delivery, true ) ) {
				return 90;
			}

			return 0;
		},
	),
	array(
		'is_pro'       => true,
		'feature_slug' => 'address-autocomplete',
		'option_key'   => 'fc_gaa_enabled',
		'value'        => 'yes',
		'title'        => __( 'Google address autocomplete', 'fluid-checkout' ),
		'description'  => __( 'Suggest verified addresses as customers type to reduce typos and failed deliveries.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$delivery = isset( $answers['delivery_methods'] ) && is_array( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();
			$score    = 0;

			// Prefer GAA when shipping physical goods
			if ( in_array( 'physical', $types, true ) ) {
				$score += 55;
			}

			if ( array_intersect( array( 'ship_to_address', 'ship_to_pickup_point', 'local_delivery' ), $delivery ) ) {
				$score += 45;
			}

			return min( 100, $score );
		},
	),
	array(
		'is_pro'       => true,
		'feature_slug' => 'address-book',
		'option_key'   => 'fc_pro_enable_address_book',
		'value'        => 'yes',
		'title'        => __( 'Address book at checkout', 'fluid-checkout' ),
		'description'  => __( 'Let logged-in customers pick a saved address instead of retyping it every time.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$types    = isset( $answers['product_types'] ) && is_array( $answers['product_types'] ) ? $answers['product_types'] : array();
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';
			$score    = 20;

			// Boost for subscriptions and B2B (repeat / multi-address buyers)
			if ( in_array( 'subscriptions', $types, true ) || in_array( 'memberships', $types, true ) ) {
				$score += 35;
			}

			if ( 'b2b' === $audience ) {
				$score += 30;
			}

			if ( in_array( 'physical', $types, true ) ) {
				$score += 15;
			}

			return min( 100, $score );
		},
	),
	array(
		'is_pro'       => true,
		'feature_slug' => 'billing-before-shipping',
		'option_key'   => 'fc_pro_checkout_billing_address_position',
		'value'        => 'step_before_shipping',
		'title'        => __( 'Billing before shipping', 'fluid-checkout' ),
		'description'  => __( 'Collect the billing address as a step before shipping so business buyers can enter invoice details first.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';

			return 'b2b' === $audience ? 90 : 0;
		},
	),



	// ----------------------------------------
	// PRO — account and phone
	// ----------------------------------------

	array(
		'is_pro'       => true,
		'feature_slug' => 'account-matching',
		'option_key'   => 'fc_pro_enable_account_matching',
		'value'        => 'yes',
		'title'        => __( 'Account matching at checkout', 'fluid-checkout' ),
		'description'  => __( 'Detect when the email already belongs to an account and prompt the customer to log in instead of creating a duplicate.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$audience = isset( $answers['customer_audience'] ) ? (string) $answers['customer_audience'] : 'dtc';
			$score    = 45;

			if ( 'b2b' === $audience ) {
				$score += 35;
			}

			return min( 100, $score );
		},
	),
	array(
		'is_pro'       => true,
		'feature_slug' => 'international-phone',
		'option_key'   => 'fc_pro_enable_international_phone_fields',
		'value'        => 'yes',
		'title'        => __( 'International phone fields', 'fluid-checkout' ),
		'description'  => __( 'Show country-aware phone inputs so customers can enter numbers in the correct international format.', 'fluid-checkout' ),
		'score'        => function ( $answers, $filtered_values = array() ) {
			$location = isset( $answers['customer_location'] ) ? (string) $answers['customer_location'] : '';
			$industry = isset( $answers['business_industry'] ) ? (string) $answers['business_industry'] : '';

			// Prefer international phone fields when selling internationally
			if ( 'international' === $location ) {
				return 90;
			}

			if ( 'electronics-computers' === $industry ) {
				return 70;
			}

			return 0;
		},
	),

);

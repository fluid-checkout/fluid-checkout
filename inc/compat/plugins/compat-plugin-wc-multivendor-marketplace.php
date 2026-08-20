<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WCFM - WooCommerce Multivendor Marketplace (by WC Lovers).
 */
class FluidCheckout_WCFMMultiVendorMarketplace extends FluidCheckout {

	/**
	 * Location field keys used by WCFM at checkout.
	 *
	 * @var array
	 */
	private $location_field_keys = array(
		'wcfmmp_user_location',
		'wcfmmp_user_location_lat',
		'wcfmmp_user_location_lng',
	);



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Maybe replace plugin scripts with modified version
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );

		// Late hooks. Priority 20: after WCFM builds `$WCFMmp->frontend` on `init` 10 via `wcfm_init`.
		add_action( 'init', array( $this, 'late_hooks' ), 20 );

		// Move checkout location fields to shipping section
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_reposition_checkout_location_fields' ), 100 );

		// Output location fields when shipping address is not required (e.g. local pickup)
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'maybe_output_checkout_location_fields_without_shipping_address' ), 40 );

		// Maybe clear required flag for delivery location when local pickup is selected
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_clear_delivery_location_required_for_local_pickup' ), 110 );

		// Shipping address review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'add_delivery_location_field_step_review_text_skip_list' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'add_substep_text_lines_shipping_address' ), 10 );

		// Maybe set substep as incomplete when delivery location is missing
		add_filter( 'fc_is_substep_complete_shipping_address', array( $this, 'maybe_set_substep_incomplete_shipping_address' ), 10 );

		// Checkout validation
		add_action( 'woocommerce_checkout_process', array( $this, 'maybe_validate_delivery_location' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Vendor-keyed shipping packages
		$this->maybe_replace_vendor_keyed_shipping_handlers();

		// Move checkout location map to shipping section
		$this->maybe_reposition_checkout_location_map();
	}



	/**
	 * Maybe replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if required class is not available
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Replace checkout location script with FC-compatible version
		wp_register_script( 'wcfmmp_checkout_location_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-multivendor-marketplace/wcfmmp-script-checkout-location' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Replace core shipping handlers that look up chosen methods by numeric index.
	 *
	 * WCFM stores chosen methods keyed by vendor ID (package key).
	 */
	public function maybe_replace_vendor_keyed_shipping_handlers() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Order summary shipping rows
		remove_action( 'fc_review_order_shipping', array( FluidCheckout_Steps::instance(), 'maybe_output_order_review_shipping_method_chosen' ), 30 );
		add_action( 'fc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );

		// Shipping method substep review text
		remove_filter( 'fc_substep_shipping_method_text_lines', array( FluidCheckout_Steps::instance(), 'add_substep_text_lines_shipping_method' ), 10 );
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Shipping method substep completeness
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_fix_substep_complete_shipping_method' ), 10 );
	}



	/**
	 * Move checkout location map to shipping section.
	 */
	public function maybe_reposition_checkout_location_map() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		global $WCFMmp;

		// Bail if frontend is not available
		if ( ! isset( $WCFMmp->frontend ) ) { return; }

		remove_action( 'woocommerce_after_checkout_billing_form', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
		// Use FC hook outside `needs_shipping_address()` so the map still shows for local pickup
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
	}

	/**
	 * Move checkout location fields to shipping section.
	 *
	 * @param   array  $fields  Checkout fields.
	 */
	public function maybe_reposition_checkout_location_fields( $fields ) {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return $fields; }

		// Bail if address field is not available
		if ( ! isset( $fields[ 'billing' ][ 'wcfmmp_user_location' ] ) ) { return $fields; }

		// Ensure shipping fields section exists
		if ( ! isset( $fields[ 'shipping' ] ) ) {
			$fields[ 'shipping' ] = array();
		}

		// Move the address field to shipping section
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ] = $fields[ 'billing' ][ 'wcfmmp_user_location' ];
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ][ 'priority' ] = 999;

		// Move latitude and longitude fields to shipping section
		if ( isset( $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ] ) ) {
			$fields[ 'shipping' ][ 'wcfmmp_user_location_lat' ] = $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ];
		}
		if ( isset( $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ] ) ) {
			$fields[ 'shipping' ][ 'wcfmmp_user_location_lng' ] = $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ];
		}

		unset( $fields[ 'billing' ][ 'wcfmmp_user_location' ], $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ], $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ] );

		return $fields;
	}

	/**
	 * Output checkout location fields when shipping address is not required.
	 *
	 * Shipping-only fields inside `form-shipping.php` are skipped when
	 * `needs_shipping_address()` is false (e.g. local pickup). Output them here
	 * so the map and distance shipping still have the required inputs.
	 */
	public function maybe_output_checkout_location_fields_without_shipping_address() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Bail if checkout or cart is not available
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() || ! WC()->cart ) { return; }

		// Bail if shipping address is needed (fields already output in the shipping form)
		if ( WC()->cart->needs_shipping_address() ) { return; }

		$checkout = WC()->checkout();
		$fields = $checkout->get_checkout_fields( 'shipping' );

		foreach ( $this->location_field_keys as $field_key ) {
			// Skip if field is not available
			if ( ! isset( $fields[ $field_key ] ) ) { continue; }

			woocommerce_form_field( $field_key, $fields[ $field_key ], $checkout->get_value( $field_key ) );
		}
	}

	/**
	 * Maybe clear required flag for delivery location when local pickup is selected.
	 *
	 * @param   array  $fields  Checkout fields.
	 */
	public function maybe_clear_delivery_location_required_for_local_pickup( $fields ) {
		// Bail if delivery location field is not available
		if ( ! isset( $fields[ 'shipping' ][ 'wcfmmp_user_location' ] ) ) { return $fields; }

		// Bail if local pickup is not selected
		if ( ! $this->is_local_pickup_selected() ) { return $fields; }

		// Clear required flag so pickup checkout is not blocked by the delivery pin
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ][ 'required' ] = false;

		return $fields;
	}



	/**
	 * Add delivery location fields to the shipping address review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  Field keys to skip in the review text.
	 */
	public function add_delivery_location_field_step_review_text_skip_list( $field_keys_skip_list ) {
		return array_merge( $field_keys_skip_list, $this->location_field_keys );
	}

	/**
	 * Add delivery location lines to the shipping address substep review text.
	 *
	 * @param   array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_address( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get delivery location values
		$location = WC()->checkout()->get_value( 'wcfmmp_user_location' );
		$lat = WC()->checkout()->get_value( 'wcfmmp_user_location_lat' );
		$lng = WC()->checkout()->get_value( 'wcfmmp_user_location_lng' );

		// Bail if delivery location is empty
		if ( empty( $location ) ) { return $review_text_lines; }

		// Intentionally use the text domain from the WCFM plugin
		$review_text_lines[] = '<strong>' . esc_html__( 'Delivery Location', 'wc-multivendor-marketplace' ) . '</strong>';
		$review_text_lines[] = esc_html( $location );

		// Maybe add coordinates on a single line
		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$review_text_lines[] = esc_html( $lat . ' ' . $lng );
		}

		return $review_text_lines;
	}



	/**
	 * Maybe set the shipping address substep as incomplete when delivery location is required and missing.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if delivery location is not required
		if ( ! $this->is_delivery_location_required() ) { return $is_substep_complete; }

		// Get delivery location values
		$location = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location' );
		$lat = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lat' );
		$lng = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lng' );

		// Maybe set step as incomplete
		if ( empty( $location ) || empty( $lat ) || empty( $lng ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}

	/**
	 * Maybe validate delivery location on place order.
	 */
	public function maybe_validate_delivery_location() {
		// Bail if delivery location is not required
		if ( ! $this->is_delivery_location_required() ) { return; }

		// Get delivery location values
		$location = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location' );
		$lat = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lat' );
		$lng = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lng' );

		// Bail if all values are present
		if ( ! empty( $location ) && ! empty( $lat ) && ! empty( $lng ) ) { return; }

		wc_add_notice( __( 'Please select a delivery location on the map.', 'fluid-checkout' ), 'error' );
	}



	/**
	 * Whether the delivery location field is currently required.
	 */
	public function is_delivery_location_required() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return false; }

		// Bail if checkout is not available
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) { return false; }

		// Bail if local pickup is selected
		if ( $this->is_local_pickup_selected() ) { return false; }

		// Get shipping fields
		$fields = WC()->checkout()->get_checkout_fields( 'shipping' );

		// Bail if delivery location field is not available
		if ( ! isset( $fields[ 'wcfmmp_user_location' ] ) ) { return false; }

		return array_key_exists( 'required', $fields[ 'wcfmmp_user_location' ] ) && true === (bool) $fields[ 'wcfmmp_user_location' ][ 'required' ];
	}

	/**
	 * Whether a local pickup shipping method is currently selected.
	 */
	public function is_local_pickup_selected() {
		// Prefer PRO local pickup detection when available
		if ( class_exists( 'FluidCheckout_PRO_CheckoutLocalPickup' ) && method_exists( FluidCheckout_PRO_CheckoutLocalPickup::instance(), 'is_shipping_method_local_pickup_selected' ) ) {
			return FluidCheckout_PRO_CheckoutLocalPickup::instance()->is_shipping_method_local_pickup_selected();
		}

		// Bail if session is not available
		if ( ! function_exists( 'WC' ) || ! WC()->session ) { return false; }

		// Get chosen shipping methods
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

		// Bail if chosen shipping methods are not available
		if ( empty( $chosen_methods ) || ! is_array( $chosen_methods ) ) { return false; }

		// All chosen methods must be local pickup
		$has_chosen_method = false;
		foreach ( $chosen_methods as $method_id ) {
			// Skip empty method ids
			if ( empty( $method_id ) ) { continue; }

			$has_chosen_method = true;

			// Bail if any chosen method is not local pickup
			if ( ! is_string( $method_id ) || 0 !== strpos( $method_id, 'local_pickup' ) ) {
				return false;
			}
		}

		return $has_chosen_method;
	}



	/**
	 * Get the chosen shipping method ID for a package.
	 *
	 * Prefer the real package key (vendor ID). Only fall back to a sequential numeric
	 * index when that index is not also a vendor package key (avoids picking another vendor).
	 *
	 * @param  int|string  $package_key    Package key from `WC()->shipping()->get_packages()`.
	 * @param  int         $package_index  Sequential package index starting at 0.
	 */
	public function get_chosen_shipping_method_for_package( $package_key, $package_index = 0 ) {
		// Bail if session is not available
		if ( ! function_exists( 'WC' ) || ! WC()->session ) { return ''; }

		// Get chosen shipping methods and packages
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$packages = function_exists( 'WC' ) && WC()->shipping() ? WC()->shipping()->get_packages() : array();

		// Bail if chosen shipping methods are not available
		if ( empty( $chosen_methods ) || ! is_array( $chosen_methods ) ) { return ''; }

		// Prefer package key (vendor-keyed packages)
		if ( isset( $chosen_methods[ $package_key ] ) && '' !== $chosen_methods[ $package_key ] && null !== $chosen_methods[ $package_key ] ) {
			return $chosen_methods[ $package_key ];
		}

		// Fall back to sequential numeric index only when it is not a vendor package key
		if ( ! array_key_exists( $package_index, $packages ) && isset( $chosen_methods[ $package_index ] ) && '' !== $chosen_methods[ $package_index ] && null !== $chosen_methods[ $package_index ] ) {
			return $chosen_methods[ $package_index ];
		}

		return '';
	}

	/**
	 * Output chosen shipping methods for order summary using package keys.
	 *
	 * Copied from `FluidCheckout_Steps::maybe_output_order_review_shipping_method_chosen()`
	 * so vendor-keyed chosen methods are resolved without mutating the session.
	 */
	public function maybe_output_order_review_shipping_method_chosen() {
		// Bail if not on checkout or cart page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! FluidCheckout_Steps::instance()->is_cart_page_or_fragment() ) { return; }

		// Bail if shipping is not available
		if ( ! function_exists( 'WC' ) || ! WC()->shipping() || ! WC()->customer ) { return; }

		// Get packages
		$packages = WC()->shipping()->get_packages();
		$steps = FluidCheckout_Steps::instance();

		// Initialize variables
		$first = true;

		// Iterate packages
		$package_index = 0;
		foreach ( $packages as $package_key => $package ) {
			$available_methods = $package[ 'rates' ];
			$chosen_method = $this->get_chosen_shipping_method_for_package( $package_key, $package_index );
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			/** translators: %d: Package number */
			$package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $package_index + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'fluid-checkout' ), ( $package_index + 1 ) ) : _x( 'Shipping', 'shipping packages', 'fluid-checkout' ), $package_index, $package );
			$product_names = array();

			if ( count( $packages ) > 1 ) {
				foreach ( $package[ 'contents' ] as $item_id => $values ) {
					$product_names[ $item_id ] = $values[ 'data' ]->get_name() . ' &times;' . $values[ 'quantity' ];
				}
				$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
			}

			wc_get_template(
				'checkout/review-order-shipping.php',
				array(
					'package'                  => $package,
					'available_methods'        => $available_methods,
					'show_package_details'     => count( $packages ) > 1,
					'show_shipping_calculator' => $steps->is_cart_page_or_fragment() && apply_filters( 'woocommerce_shipping_show_shipping_calculator', $first, $package_index, $package ),
					'package_details'          => implode( ', ', $product_names ),
					'package_name'             => apply_filters( 'fc_order_summary_shipping_package_name', $package_name, $method, $package_index, $package ),
					'formatted_shipping_price' => $steps->get_cart_totals_shipping_method_label( $method, $package, $package_index ),
					'index'                    => $package_index,
					'chosen_method'            => $chosen_method,
					'method'                   => $method,
					'formatted_destination'    => WC()->countries->get_formatted_address( $package[ 'destination' ], ', ' ),
					'has_calculated_shipping'  => WC()->customer->has_calculated_shipping(),
				)
			);

			$first = false;
			$package_index++;
		}
	}

	/**
	 * Add shipping method substep review text lines using package keys.
	 *
	 * Copied from `FluidCheckout_Steps::add_substep_text_lines_shipping_method()`
	 * so vendor-keyed chosen methods are resolved without mutating the session.
	 *
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if shipping is not available
		if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) { return $review_text_lines; }

		// Get shipping packages
		$packages = WC()->shipping()->get_packages();
		$steps = FluidCheckout_Steps::instance();

		// Determine if has multiple packages
		$has_multiple_packages = apply_filters( 'fc_cart_has_multiple_packages', 1 < count( $packages ) );

		// Determine allowed kses attributes and tags
		$allowed_kses_attributes = array( 'span' => array( 'class' => true ), 'bdi' => array(), 'strong' => array(), 'br' => array() );

		// Iterate shipping packages
		$package_index = 0;
		foreach ( $packages as $package_key => $package ) {
			$package_review_text_lines = array();

			// Get shipping method info
			$available_methods = $package[ 'rates' ];
			$chosen_method = $this->get_chosen_shipping_method_for_package( $package_key, $package_index );
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			$chosen_method_label = $method ? wc_cart_totals_shipping_method_label( $method ) : __( 'Not selected yet.', 'fluid-checkout' );
			$chosen_method_label = apply_filters( 'fc_shipping_method_substep_text_chosen_method_label', $chosen_method_label, $method );

			// Handle package name
			if ( $has_multiple_packages && $steps->is_shipping_package_name_display_enabled() ) {
				$package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $package_index + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'fluid-checkout' ), ( $package_index + 1 ) ) : _x( 'Shipping', 'shipping packages', 'fluid-checkout' ), $package_index, $package );
				$package_name = '<strong>' . $package_name . '</strong>';
				$package_review_text_lines[] = wp_kses( $package_name, $allowed_kses_attributes );
			}

			// Add chosen shipping method line
			$package_review_text_lines[] = wp_kses( $chosen_method_label, $allowed_kses_attributes );

			// Handle package destination
			if ( $has_multiple_packages && $steps->is_shipping_package_contents_destination_text_lines_enabled() ) {
				// Get package destination
				$destination = array_key_exists( 'destination', $package ) && ! empty( $package[ 'destination' ] ) ? $package[ 'destination' ] : array();
				$destination = apply_filters( 'fc_shipping_method_substep_text_package_destination_data', $destination, $package_index, $package, $chosen_method, $method );

				// Get formatted destination text
				$destination_text = WC()->countries->get_formatted_address( $destination, ', ' );
				$destination_text = apply_filters( 'fc_shipping_method_substep_text_package_destination_text', $destination_text, $package_index, $package, $chosen_method, $method );

				// Add package destination line
				if ( ! empty( $destination_text ) ) {
					$package_review_text_lines[] = wp_kses( $destination_text, $allowed_kses_attributes );
				}
			}

			// Filter review text lines for the shipping package before adding the package contents
			$package_review_text_lines = apply_filters( 'fc_shipping_method_substep_text_package_review_text_lines_before_contents', $package_review_text_lines, $package_index, $package, $chosen_method, $method );

			// Handle package contents
			if ( $has_multiple_packages && $steps->is_shipping_package_contents_substep_text_lines_enabled() ) {
				// Get shipping package contents
				$contents = '';
				foreach ( $package[ 'contents' ] as $item_id => $values ) {
					$contents .= $values[ 'quantity' ] . ' × ' . $values[ 'data' ]->get_name() . ', ';
				}
				// Remove extra comma at the end
				$contents = trim( rtrim( $contents, ', ' ) );

				// Wrap contents in a `span` tag for small text
				$contents = '<span class="fc-step__substep-text-line--small-text">' . $contents . '</span>';

				// Add package contents line
				$package_review_text_lines[] = wp_kses( $contents, $allowed_kses_attributes );
			}

			// Filter review text lines for the shipping package
			$package_review_text_lines = apply_filters( 'fc_shipping_method_substep_text_package_review_text_lines', $package_review_text_lines, $package_index, $package, $chosen_method, $method );

			// Add package review text lines
			$review_text_lines = array_merge( $review_text_lines, $package_review_text_lines );

			// Increase package index
			$package_index++;
		}

		return $review_text_lines;
	}

	/**
	 * Maybe fix shipping method substep completeness for vendor-keyed packages.
	 *
	 * @param  bool  $is_substep_complete  Whether the substep is complete.
	 */
	public function maybe_fix_substep_complete_shipping_method( $is_substep_complete ) {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return $is_substep_complete; }

		// Bail if shipping is not available
		if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) { return $is_substep_complete; }

		// Re-check completeness using package keys
		$packages = WC()->shipping()->get_packages();
		$package_index = 0;
		foreach ( $packages as $package_key => $package ) {
			$chosen_method = $this->get_chosen_shipping_method_for_package( $package_key, $package_index );
			if ( empty( $chosen_method ) ) {
				return false;
			}
			$package_index++;
		}

		return true;
	}

}

FluidCheckout_WCFMMultiVendorMarketplace::instance();

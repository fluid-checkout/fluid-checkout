<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MyParcel (by MyParcel).
 */
class FluidCheckout_WooCommerceMyParcel extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Persisted fields
		add_filter( 'fc_customer_persisted_data_clear_fields_order_processed', array( $this, 'change_customer_persisted_data_clear_fields_order_processed' ), 10 );

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10, 2 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_shipping' ), 100 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_billing' ), 100 );

		// Shipping methods
		add_filter( 'wc_wcmp_delivery_options_location', array( $this, 'change_hook_delivery_options_location' ), 10 );
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'maybe_change_substep_text_lines_shipping_methods' ), 20 );

		// Maybe subset step as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-my-parcel-update-handler', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-myparcel/myparcel-update-handler' ), array( 'fc-utils', 'wc-myparcel-frontend' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-my-parcel-update-handler', 'window.addEventListener("load",function(){MyParcelUpdateHandler.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-my-parcel-update-handler' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if not showing delivery options
		if ( ! $this->should_show_delivery_options() ) { return; }

		$this->enqueue_assets();
	}



	/**
     * Returns true if any product in the loop is:
     *  - physical
     *  - not on backorder OR user allows products on backorder to have delivery options
     * COPIED FROM WCMP_Checkout::shouldShowDeliveryOptions
     */
	public function should_show_delivery_options() {
		// CHANGE: Bail if function or class not available
		if ( ! function_exists( 'WCMYPA' ) || ! class_exists( 'WCMYPA_Settings' ) ) { return false; }

		$showForBackorders = WCMYPA()->setting_collection->isEnabled( WCMYPA_Settings::SETTINGS_SHOW_DELIVERY_OPTIONS_FOR_BACKORDERS );
		$showDeliveryOptions = false;

		foreach ( WC()->cart->get_cart() as $cartItem ) {
			/**
			 * @var WC_Product $product
			 */
			$product = $cartItem['data'];

			if ( ! $product->is_virtual() ) {
				$isOnBackOrder = $product->is_on_backorder( $cartItem['quantity'] );
				if (! $showForBackorders && $isOnBackOrder) {
					$showDeliveryOptions = false;
					break;
				}

				$showDeliveryOptions = true;
			}
		}

		return apply_filters( 'wc_myparcel_show_delivery_options', $showDeliveryOptions );
	}



	/**
	 * Add pickup location fields to be cleared when order is processed.
	 *
	 * @param   array  $clear_field_keys  Checkout field keys to clear from the session after placing an order.
	 */
	public function change_customer_persisted_data_clear_fields_order_processed( $clear_field_keys ) {
		// Add fields to be cleared
		$clear_field_keys[] = '_myparcel_delivery_options';

		return $clear_field_keys;
	}



	/**
	 * Adds custom fields to the list of optional fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_field_keys ) {
		$fields_keys = array(
			'address_1',
			'address_2',
			'street_name',
			'house_number',
			'house_number_suffix',

			'shipping_address_1',
			'shipping_address_2',
			'shipping_street_name',
			'shipping_house_number',
			'shipping_house_number_suffix',

			'billing_address_1',
			'billing_address_2',
			'billing_street_name',
			'billing_house_number',
			'billing_house_number_suffix',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}



	/**
	 * Change shipping extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	function change_substep_text_extra_fields_skip_list_shipping( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'shipping_house_number',
			'shipping_house_number_suffix',
			'shipping_street_name',
		) );
		return $skip_list;
	}

	/**
	* Change billing extra fields to skip for the substep review text.
	*
	* @param   array  $skip_list  List of fields to skip adding to the substep review text.
	*/
	function change_substep_text_extra_fields_skip_list_billing( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'billing_house_number',
			'billing_house_number_suffix',
			'billing_street_name',
		) );

		return $skip_list;
	}



	/**
	 * Change the hook location for the delivery options.
	 */
	public function change_hook_delivery_options_location( $hook_name ) {
		return 'fc_shipping_methods_after_packages';
	}



	/**
	 * Return an `array` of shipping methods that will show delivery options, `true` if showing delivery options for all shipping methods, or `false` if not showing delivery options.
	 * 
	 * COPIED AND ADAPTED FROM: WCMP_Checkout::getShippingMethodsAllowingDeliveryOptions
	 * 
	 * @see WCMP_Checkout::getShippingMethodsAllowingDeliveryOptions
	 * @see WCMP_Export::DISALLOWED_SHIPPING_METHODS
	 */
	public function get_shipping_methods_allowing_delivery_options() {
		// Bail if classes not available
		if ( ! function_exists( 'WCMYPA' ) || ! class_exists( 'WCMYPA_Settings' ) || ! class_exists( 'WCMP_Checkout' ) ) { return false; }

		// Get settings
		$allowedMethods               = array();
		$displayFor                   = WCMYPA()->setting_collection->getByName( WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY );
		$shippingMethodsByPackageType = WCMYPA()->setting_collection->getByName( WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES );

		// Maybe return `true` if displaying for all shipping methods
		if ( WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS === $displayFor || ! $shippingMethodsByPackageType ) {
			return true;
		}

		// Get shipping methods for package
		$shippingMethodsForPackage = $shippingMethodsByPackageType[ MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME ];

		// Iterate over shipping methods for package
		foreach ( $shippingMethodsForPackage as $shippingMethod ) {
			$methodId = WCMP_Checkout::splitShippingMethodString( $shippingMethod );

			// Maybe add to allowed methods
			if ( ! in_array( $methodId, WCMP_Export::DISALLOWED_SHIPPING_METHODS, true ) ) {
				$allowedMethods[] = $shippingMethod;
			}
		}

		return $allowedMethods;
	}



	/**
	 * Check whether the shipping method is associated with MyParcel.
	 */
	public function is_shipping_method_myparcel( $shipping_method_id ) {
		// Bail if MyParcel delivery options section is disabled
		if ( ! function_exists( 'WCMYPA' ) || ! class_exists( 'WCMYPA_Settings' ) || ! WCMYPA()->setting_collection->isEnabled( WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED ) ) { return false; }

		// Get shipping methods allowing delivery options
		$shipping_methods_allowing = $this->get_shipping_methods_allowing_delivery_options();

		// Return `true` if showing delivery options for all shipping methods
		if ( true === $shipping_methods_allowing ) {
			return true;
		}

		// Otherwise, check if shipping method is associated with MyParcel
		$shipping_method_split = WCMP_Checkout::splitShippingMethodString( $shipping_method_id );
		$shipping_method_type = is_array( $shipping_method_split ) ? $shipping_method_split[ 0 ] : $shipping_method_split;
		if ( true === $shipping_methods_allowing || in_array( $shipping_method_type, $shipping_methods_allowing, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a shipping method associated with MyParcel is selected.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if a MyParcel shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_myparcel( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Check whether the order shipping method is local pickup from MyParcel.
	 */
	public function is_order_shipping_method_local_pickup( $order ) {
		// Bail if order is not valid
		if ( ! $order ) { return null; }

		// Get my parcel delivery data
		$delivery_options_meta = $order->get_meta( '_myparcel_delivery_options' );
		$delivery_options = $this->parse_delivery_options_data( $delivery_options_meta );

		// Bail if delivery options data is not available
		if ( ! $delivery_options ) { return null; }

		// Check whether a local pickup delivery option has been selected
		if ( is_array( $delivery_options ) && array_key_exists( 'isPickup', $delivery_options ) && $delivery_options[ 'isPickup' ] ) {
			return true;
		}

		// Otherwise, return `false`
		return false;
	}



	/**
	 * Set the shipping method substep as always incomplete when shipping method is associated with MyParcel.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Maybe set substep as incomplete if shipping method associated with MyParcel is selected
		if ( $this->is_shipping_method_selected() ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Parse delivery options data.
	 */
	public function parse_delivery_options_data( $data_json ) {
		// Bail if delivery options data is not available
		if ( ! $data_json || empty( $data_json ) ) { return false; }

		// Try to decode delivery options data
		$delivery_options_object = json_decode( $data_json, true );

		// Bail if delivery options data is not valid
		if ( ! $delivery_options_object ) { return false; }

		// Otherwise, return the delivery options object
		return $delivery_options_object;
	}

	/**
	 * Get delivery options data as an array.
	 */
	public function get_delivery_options_data() {
		global $wp;

		// Initialize delivery options data as empty
		$delivery_options = false;

		// Maybe get delivery options for the checkout page
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			$delivery_options = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( '_myparcel_delivery_options' );
		}
		// Maybe get delivery options for the order details pages
		else if ( is_order_received_page() || is_view_order_page() ) {
			// Get order id
			$order_id = array_key_exists( 'view-order', $wp->query_vars ) ? $wp->query_vars[ 'view-order' ] : false;
			if ( ! $order_id ) { $order_id = array_key_exists( 'order-received', $wp->query_vars ) ? $wp->query_vars[ 'order-received' ] : false; }

			// Bail if order id is not available
			if ( ! $order_id ) { return false; }

			// Get order object
			$order = wc_get_order( $order_id );

			// Bail if order object is not available
			if ( ! $order ) { return false; }

			// Get delivery options data
			$delivery_options = $order->get_meta( '_myparcel_delivery_options' );
		}

		// Otherwise, return the delivery options object
		return $this->parse_delivery_options_data( $delivery_options );
	}

	/**
	 * Get the selected pickup location address data.
	 */
	public function get_selected_pickup_location_address( $delivery_options ) {
		// Intialize address data
		$address_data = false;

		// Maybe add pickup location to substep review text lines
		if ( ! array_key_exists( 'isPickup', $delivery_options ) || ! $delivery_options[ 'isPickup' ] || ! array_key_exists( 'pickupLocation', $delivery_options ) ) { return $address_data; }

		// Get address data object
		$address_data = array(
			'postcode' => $delivery_options[ 'pickupLocation' ][ 'postal_code' ],
			'country' => $delivery_options[ 'pickupLocation' ][ 'cc' ],
			'city' => $delivery_options[ 'pickupLocation' ][ 'city' ],
			'address_1' => $delivery_options[ 'pickupLocation' ][ 'street' ] . ' ' . $delivery_options[ 'pickupLocation' ][ 'number' ] . $delivery_options[ 'pickupLocation' ][ 'number_suffix' ],
		);

		return $address_data;
	}

	/**
	 * Maybe change the shipping methods substep text to display information from the selected MyParcel shipping method.
	 */
	public function maybe_change_substep_text_lines_shipping_methods( $text_lines ) {
		// Bail if class not available
		if ( ! class_exists( 'MyParcelNL\Sdk\src\Model\Carrier\CarrierFactory' ) ) { return $text_lines; }

		// Bail if selected shipping method is not associated with MyParcel
		if ( ! $this->is_shipping_method_selected() ) { return $text_lines; }

		// Get delivery options data
		$delivery_options = $this->get_delivery_options_data();

		// Bail if delivery options data is not available
		if ( ! is_array( $delivery_options ) ) { return $text_lines; }

		// Get list of carriers
		$carrier_classes = MyParcelNL\Sdk\src\Model\Carrier\CarrierFactory::CARRIER_CLASSES;
		$carriers = array();
		foreach ( $carrier_classes as $carrier_class ) {
			$carrier = MyParcelNL\Sdk\src\Model\Carrier\CarrierFactory::create( $carrier_class );
			$carriers[ $carrier->getName() ] = $carrier->getHuman();
		}

		// Add carrier to substep review text lines
		if ( array_key_exists( 'carrier', $delivery_options ) && ! empty( $delivery_options[ 'carrier' ] ) ) {
			$text_lines[] = '<strong>' . __( 'Carrier:', 'fluid-checkout' ) . '</strong>';
			$text_lines[] = $carriers[ $delivery_options[ 'carrier' ] ];
		}

		// Maybe add pickup location to substep review text lines
		if ( array_key_exists( 'isPickup', $delivery_options ) && $delivery_options[ 'isPickup' ] ) {
			// Add pickup point to substep review text lines
			$text_lines[] = '<strong>' . __( 'Pickup point:', 'fluid-checkout' ) . '</strong>';

			// Maybe add notice for pickup location not selected
			if ( array_key_exists( 'pickupLocation', $delivery_options ) ) {
				// Get address data object
				$address_data = $this->get_selected_pickup_location_address( $delivery_options );

				// Add pickup location data to substep review text lines
				$text_lines[] = $delivery_options[ 'pickupLocation' ][ 'location_name' ];
				$text_lines[] = WC()->countries->get_formatted_address( $address_data );
			}
			else {
				$text_lines[] = '<em>' . __( 'Pickup point not selected yet.', 'fluid-checkout' ) . '</em>';
			}
		}

		return $text_lines;
	}

}

FluidCheckout_WooCommerceMyParcel::instance();

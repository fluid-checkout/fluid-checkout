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
		// Shipping methods
		add_filter( 'wc_wcmp_delivery_options_location', array( $this, 'change_hook_delivery_options_location' ), 10 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete_shipping' ), 10 );
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
	public function is_shipping_method_associated_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if `vp_pont` shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_myparcel( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Set the shipping step as always incomplete when shipping method is associated with MyParcel.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_shipping( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }

		// Maybe set step as incomplete if shipping method associated with MyParcel is selected
		if ( $this->is_shipping_method_associated_selected() ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}

}

FluidCheckout_WooCommerceMyParcel::instance();

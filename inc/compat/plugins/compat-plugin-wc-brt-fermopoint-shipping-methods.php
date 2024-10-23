<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: BRT Fermopoint (by BRT)
 */
class FluidCheckout_WC_BRT_FermopointShippingMethods extends FluidCheckout {

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
		// Bail if Fermopoint classes are not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Move fermopoint details section
		remove_action( 'woocommerce_review_order_after_shipping', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_maps_or_list' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'add_maps_or_list' ), 10 );

		// Output hidden fields
		remove_action( 'woocommerce_review_order_before_submit', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'my_custom_checkout_field' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Add substep text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wc_brt_fermopoint_shipping_methods_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-brt-fermopoint-shipping-methods/wc_brt_fermopoint_shipping_methods_js' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-fermopoint', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-brt-fermopoint-shipping-methods/checkout-validation-fermopoint' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-fermopoint', 'window.addEventListener("load",function(){CheckoutValidationFermopoint.init(fcSettings.checkoutValidationFermopoint);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-fermopoint' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		// Add validation settings
		$settings[ 'checkoutValidationFermopoint' ] = array(
			'validationMessages'  => array(
				'fermopoint_not_selected' => __( 'Selecting a collection point is required when shipping with FermoPoint.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Add maps or list output from the plugin, replacing `tr` elements with `div`.
	 */
	public function add_maps_or_list() {
		// Get the maps or list output from the plugin
		ob_start();
		WC_BRT_FermoPoint_Shipping_Methods::instance()->core->add_maps_or_list();
		$html = ob_get_clean();

		// Replace `tr` elements with `div`
		$replace = array(
			'<tr' => '<div',
			'</tr' => '</div',
			'<td' => '<div',
			'</td' => '</div',
		);
		$html = str_replace( array_keys( $replace ), array_values( $replace ), $html );

		// Output
		echo $html;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Get fields values from session
		$pudo_data = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-selected_pudo' );
		$pudo_id = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-pudo_id' );

		// Output custom hidden fields
		echo '<div id="wc_brt_fermopoint-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="wc_brt_fermopoint-selected_pudo" name="wc_brt_fermopoint-selected_pudo" value="'. esc_attr( $pudo_data ) .'">';
		echo '<input type="hidden" id="wc_brt_fermopoint-pudo_id" name="wc_brt_fermopoint-pudo_id" value="'. esc_attr( $pudo_id ) .'" class="validate-fermopoint">';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $i => $package ) {
			// Get shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;

			// Check whether the chosen method is fermpoint
			if ( $method && 'wc_brt_fermopoint_shipping_methods_custom' === $method->method_id ) {
				// Get the selected fermopoint data
				$pudo_data = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-selected_pudo' );

				// Check whether the selected fermopoint data is available
				if ( ! empty( $pudo_data ) ) {
					// Try to convert pudo data from JSON
					$pudo_data = json_decode( $pudo_data, true );

					if ( is_array( $pudo_data ) && array_key_exists( 'pointName', $pudo_data ) ) {
						// Add the chosen fermopoint PUDO
						$review_text_lines[] = $pudo_data[ 'pointName' ];
					}
				}
			}
		}

		return $review_text_lines;
	}



	/**
	 * Set the shipping substep as incomplete when shipping method is Fermopoint but a location has not yet been selected.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get shipping packages
		$packages = WC()->shipping->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;

			// Skip package if shipping method selected is not UPS pickup location
			if ( ! $method || 'wc_brt_fermopoint_shipping_methods_custom' !== $method->method_id ) { continue; }

			// Get the selected pudo ID
			$pudo_id = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-pudo_id' );

			// Maybe mark substep as incomplete if pickup location code is empty
			if ( empty( $pudo_id ) ) {
				$is_substep_complete = false;
				break;
			}
		}

		return $is_substep_complete;
	}

}

FluidCheckout_WC_BRT_FermopointShippingMethods::instance();

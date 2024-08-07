<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: GLS Shipping for WooCommerce (by Inchoo).
 */
class FluidCheckout_GLSShippingForWooCommerce extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'gls_shipping_method';

	/**
	 * Session field name for the selected pickup location.
	 */
	public const SESSION_FIELD_NAME = 'gls_pickup_info';



	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'GLS_Shipping_Checkout';



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
		// Shipping methods hooks
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Output hidden fields
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete_shipping' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Move shipping method hooks
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( $class_object, 'add_gls_button_to_shipping_method' ), 10 );
		remove_filter( 'woocommerce_review_order_after_shipping', array( $class_object, 'display_pickup_information'), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $class_object, 'display_pickup_information' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-gls-shipping-for-woocommerce', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/gls-shipping-for-woocommerce/checkout-gls-shipping-for-woocommerce' ), array( 'jquery', 'fc-utils' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-gls-shipping-for-woocommerce', 'window.addEventListener("load",function(){CheckoutGLSShippingForWooCommerce.init(fcSettings.checkoutGLSShippingForWooCommerce);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-gls-shipping-for-woocommerce', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/gls-shipping-for-woocommerce/checkout-validation-gls-shipping-for-woocommerce' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-validation-gls-shipping-for-woocommerce', 'window.addEventListener("load",function(){CheckoutValidationGLSShippingForWooCommerce.init(fcSettings.checkoutValidationGLSShippingForWooCommerce);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-gls-shipping-for-woocommerce' );
		wp_enqueue_script( 'fc-checkout-validation-gls-shipping-for-woocommerce' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationGLSShippingForWooCommerce' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Check whether the shipping method ID is GLS Shipping method.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_gls_shipping( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Maybe get selected shipping method object if it matches the target method.
	 */
	public function maybe_get_selected_shipping_method() {
		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get available shipping methods
			$available_methods = $package['rates'];

			// Check if target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_gls_shipping( $chosen_method ) && ! empty( $available_methods[ $chosen_method ] ) ) {
				// Return the selected shipping method object
				return $available_methods[ $chosen_method ];
			}
		}

		return false;
	}



	/**
	 * Get whether the shipping method is a local pickup method from this plugin.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 * @param  object  $method              The shipping method object.
	 * @param  object  $order               The order object.
	 */
	public function is_shipping_method_local_pickup( $shipping_method_id, $method = null, $order = null ) {
		// Bail if plugin constants are not defined
		if ( ! defined( 'GLS_SHIPPING_METHOD_PARCEL_LOCKER_ID' ) || ! defined( 'GLS_SHIPPING_METHOD_PARCEL_SHOP_ID' ) ) { return false; }

		// Check whether shipping method ID contains one of the constants value
		if ( 0 === strpos( $shipping_method_id, GLS_SHIPPING_METHOD_PARCEL_LOCKER_ID ) || 0 === strpos( $shipping_method_id, GLS_SHIPPING_METHOD_PARCEL_SHOP_ID ) ) {
			return true;
		}

		return false;
	}



	/**
	 * Output the pickup point selection UI from the plugin.
	 */
	public function output_pickup_point_selection_ui() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if plugin's class object is not available
		if ( ! $class_object ) { return; }

		// Bail if plugin's class method is not available
		if ( ! method_exists( $class_object, 'add_gls_button_to_shipping_method' ) ) { return; }

		// Get selected shipping method object
		$shipping_method = $this->maybe_get_selected_shipping_method();

		// Bail if selected shipping method object is not available
		if ( ! is_object( $shipping_method ) ) { return; }

		// Set default value
		$label = '';

		// Get the pickup point selection UI
		ob_start();
		echo $class_object->add_gls_button_to_shipping_method( $label, $shipping_method );
		$html = ob_get_clean();

		// Remove line breaks from the output
		$html = preg_replace( '/<br[^>]*>/', '', $html );

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Output selected terminal data if associated with the currently selected shipping method
		if ( ! empty( $terminal_data ) && ! empty( $terminal_data['address_1'] ) ) {
			$html .= '<div id="gls-pickup-info">';
			$html .= '<strong>' . __('Pickup Location', 'gls-shipping-for-woocommerce') . ':</strong>' . '<br>';
			$html .= __('Name', 'gls-shipping-for-woocommerce') . ': ' . esc_html( $terminal_data['company'] ) . '<br>';
			$html .= __('Address', 'gls-shipping-for-woocommerce') . ': ' . esc_html( $terminal_data['address_1'] ) . ', ' . esc_html( $terminal_data['city'] ) . ', ' . esc_html( $terminal_data['postcode'] ) . '<br>';
			$html .= __('Country', 'gls-shipping-for-woocommerce') . ': ' . esc_html( $terminal_data['country'] );
			$html .= '</div>';
		}

		// Output the pickup point selection UI
		echo $html;
	}



	/**
	 * Get the selected terminal data.
	 */
	public function get_selected_terminal_data() {
		// Get session field name
		$session_field_name = $this->get_session_field_name();

		// Bail if session field name is not available
		if ( empty( $session_field_name ) ) { return $posted_data; }

		// Get session field value
		$terminal_data = WC()->session->get( $session_field_name );

		// Bail if terminal data is empty
		if ( empty( $terminal_data ) ) { return; }

		// Assign terminal object property values to the corresponding array keys
		$selected_terminal_data = array(
			'company' => isset( $terminal_data['name'] ) ? esc_html( $terminal_data['name'] ) : '',
			'address_1' => isset( $terminal_data['contact']['address'] ) ? $terminal_data['contact']['address'] : '',
			'postcode' => isset( $terminal_data['contact']['postalCode'] ) ? esc_html( $terminal_data['contact']['postalCode'] ) : '',
			'city' => isset( $terminal_data['contact']['city'] ) ? esc_html( $terminal_data['contact']['city'] ) : '',
			'country' => isset( $terminal_data['contact']['countryCode'] ) ? esc_html( $terminal_data['contact']['countryCode'] ) : '',
		);

		return $selected_terminal_data;
	}



	/**
	 * Get the session field name based on the selected shipping method.
	 */
	public function get_session_field_name() {
		// Get selected shipping method
		$shipping_method = $this->maybe_get_selected_shipping_method();

		// Bail if selected shipping method is not available
		if ( ! is_object( $shipping_method ) ) { return; }

		// Get the session field name based on the selected shipping method
		$session_field_name = self::SESSION_FIELD_NAME . '_' . $shipping_method->id;

		return $session_field_name;
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field value was not posted or is empty
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) || empty( $posted_data[ self::SESSION_FIELD_NAME ] ) ) { return $posted_data; }

		// Get session field name
		$session_field_name = $this->get_session_field_name();

		// Bail if session field name is not available
		if ( empty( $session_field_name ) ) { return $posted_data; }

		// Get decoded terminal data from the posted data
		$terminal_data = json_decode( $posted_data[ self::SESSION_FIELD_NAME ], true );

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( $session_field_name, $terminal_data );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $review_text_lines; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Bail if there is no selected terminal
		if ( empty( $terminal_data ) ) { return $review_text_lines; }

		// Format data
		$formatted_address = WC()->countries->get_formatted_address( $terminal_data );

		// Add formatted address to the review text lines
		$review_text_lines[] = $formatted_address;

		return $review_text_lines;
	}



	/**
	 * Set the shipping step as incomplete when shipping method is Hungarian Pickup Points and no pickup point is selected.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_shipping( $is_step_complete ) {
		// Get selected shipping method
		$shipping_method = $this->maybe_get_selected_shipping_method();

		// Bail if selected shipping method is not available
		if ( ! is_object( $shipping_method ) ) { return $is_step_complete; }

		// Bail if selected shipping method is not a local pickup method
		if ( ! $this->is_shipping_method_local_pickup( $shipping_method->id ) ) { return $is_step_complete; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Maybe set step as incomplete if terminal data is not set
		if ( empty( $terminal_data ) ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Maybe get selected shipping method
		$shipping_method = $this->maybe_get_selected_shipping_method();

		// Bail if target shipping method is not selected
		if ( ! is_object( $shipping_method ) ) { return; }

		// Bail if target shipping method is not local pickup
		if ( ! $this->is_shipping_method_local_pickup( $shipping_method->id ) ) { return; }

		// Check if terminal data is set
		$is_terminal_data_set = ! empty( $this->get_selected_terminal_data() );

		// Output custom hidden fields
		echo '<div id="gls_shipping-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="gls_shipping-terminal" name="gls_shipping-terminal" value="'. $is_terminal_data_set .'" class="validate-gls-shipping">';
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="gls_shipping-terminal"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="gls_shipping-terminal"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="gls_shipping-terminal"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_GLSShippingForWooCommerce::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mondial Relay - WordPress (by Kasutan).
 */
class FluidCheckout_MondialRelayWordpress extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'mondialrelay';

	/**
	 * Session field name.
	 */
	public const SESSION_FIELD_NAME = 'mrwp_parcel_shop_address';


	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'class_MRWP_public';



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

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Shipping methods
		add_filter( 'fc_shipping_method_option_image_html', array( $this, 'maybe_change_shipping_method_option_image_html' ), 10, 2 );

		// Output hidden fields
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
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
		remove_action( 'woocommerce_review_order_after_shipping', array( $class_object, 'modaal_link' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );

		// Remove Mondial Relay logo from order overview
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( 'MRWP_Shipping_Method', 'embellish_label' ), 10, 2 );

		// Remove Mondial Relay button from order overview
		add_filter( 'mrwp_modaal_button', '__return_empty_string' );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-mondial-relay', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mondialrelay/checkout-mondialrelay' ), array( 'jquery', 'fc-utils', 'mondialrelay-wp' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-mondial-relay', 'window.addEventListener("load",function(){CheckoutMondialRelay.init(fcSettings.checkoutMondialRelay);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-mondial-relay', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mondialrelay/checkout-validation-mondialrelay' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-mondial-relay', 'window.addEventListener("load",function(){CheckoutValidationMondialRelay.init(fcSettings.checkoutValidationMondialRelay);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-mondial-relay' );
		wp_enqueue_script( 'fc-checkout-validation-mondial-relay' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationMondialRelay' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		// Add checkout settings
		$settings[ 'checkoutMondialRelay' ] = array(
			'checkoutMessages'  => array(
				'pickup_point_selected' => __( 'Livraison en Point Relais®', 'mondialrelay-wordpress' ),
			),
		);

		return $settings;
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="mondial_relay-terminal"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="mondial_relay-terminal"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="mondial_relay-terminal"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Check whether the shipping method ID is Mondial Relay.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_mondial_relay( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Check whether Mondial Relay is selected as a shipping method.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if a Mondial Relay shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_mondial_relay( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Output custom hidden fields
		echo '<div id="mondial_relay-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="mondial_relay-terminal" name="mondial_relay-terminal" value="'. esc_attr( $selected_terminal ) .'" class="validate-mondial-relay">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Output the pickup point selection UI from Mondial Relay.
	 */
	public function output_pickup_point_selection_ui() {
		// Bail if selected shipping method is not a Mondial Relay shipping method
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if plugin's class object is not available
		if ( ! $class_object ) { return; }

		// Bail if plugin's class method is not available
		if ( ! method_exists( $class_object, 'modaal_link' ) ) { return; }

		// Get the pickup point selection UI
		ob_start();
		$class_object->modaal_link();
		$html = ob_get_clean();

		// Replace table elements with `div`
		$replace = array(
			'<tr' => '<div',
			'</tr' => '</div',
			'<td' => '<div',
			'</td' => '</div',
		);
		$html = str_replace( array_keys( $replace ), array_values( $replace ), $html );

		// Get selected terminal info
		$selected_terminal_info = $this->get_selected_terminal_info();

		// If selected terminal info is available, use it to replace the default plugin output
		if ( ! empty( $selected_terminal_info ) ) {
			// Turn into a string and separate array elements by line breaks (use array_filter to avoid using empty elements)
			$terminal_location = implode( '<br>', array_filter( $selected_terminal_info ) );

			// Prepend translated text to selected terminal info
			$terminal_location = __( 'Livraison en Point Relais®', 'mondialrelay-wordpress' ) . '<br>' . $terminal_location;

			// Replace content of the "em" element with the selected terminal location
			$html = preg_replace( '/<em id="parcel_shop_info" class="parcel_shop_info">.*<\/em>/', '<em id="parcel_shop_info" class="parcel_shop_info">' . $terminal_location . '</em>', $html );
		}

		// Output
		echo $html;
	}



	/**
	 * Maybe change the shipping method option image HTML.
	 * 
	 * @param  string  $html     The HTML of the shipping method option image.
	 * @param  object  $method   The shipping method object.
	 */
	public function maybe_change_shipping_method_option_image_html( $html, $method ) {
		// Bail if not a shipping method from this plugin
		if ( ! $this->is_shipping_method_mondial_relay( $method->id ) ) { return $html; }

		// Get shipping method instance
		$method_instance = new MRWP_Shipping_Method( $method->get_instance_id() );

		// Bail if not set to display the logo
		if ( $method_instance && 'yes' !== $method_instance->get_option( 'display_logo' ) ) { return $html; }

		// Get image URL
		$image_url = trailingslashit( plugins_url() ) . 'mondialrelay-wordpress/public/img/mondial-relay-logo.png';

		// Define image HTML
		$html = '<img class="shipping_logo" src="' . $image_url . '" alt="Mondial Relay"/>';

		return $html;
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field value was not posted or is empty
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) || empty( $posted_data[ self::SESSION_FIELD_NAME ] ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( self::SESSION_FIELD_NAME, $posted_data[ self::SESSION_FIELD_NAME ] );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Get the selected terminal info.
	 */
	public function get_selected_terminal_info() {
		// Set default value to empty array
		$selected_terminal_info = array();

		// Get session field value
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if there is no selected terminal
		if ( empty( $selected_terminal ) ) { return $selected_terminal_info; }

		// Bail if the session value is not in the expected format
		if ( 0 === strpos( $selected_terminal, "-MRWP-" ) ) { return; }

		// Split session value received from the API into parts by using "-MRWP-" as separator
		$terminal_parts = explode( '-MRWP-', $selected_terminal );

		// Assign terminal parts to variables if they exist
		$selected_terminal_info = array(
			'company' => isset( $terminal_parts[0] ) ? $terminal_parts[0] : '',
			'address_1' => isset( $terminal_parts[1] ) ? $terminal_parts[1] : '',
			'address_2' => isset( $terminal_parts[2] ) ? $terminal_parts[2] : '',
			'postcode' => isset( $terminal_parts[3] ) ? $terminal_parts[3] : '',
			'city' => isset( $terminal_parts[4] ) ? $terminal_parts[4] : '',
			'country' => isset( $terminal_parts[5] ) ? $terminal_parts[5] : '',
		);

		return $selected_terminal_info;
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

		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get selected terminal info
		$selected_terminal_info = $this->get_selected_terminal_info();

		// Bail if there is no selected terminal
		if ( empty( $selected_terminal_info ) ) { return $review_text_lines; }

		// Format terminal info
		$selected_terminal_info = WC()->countries->get_formatted_address( $selected_terminal_info );

		// Add terminal info as review text line
		$review_text_lines[] = $selected_terminal_info;

		return $review_text_lines;
	}

}

FluidCheckout_MondialRelayWordpress::instance();

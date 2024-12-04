<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Packlink PRO Shipping (by Packlink Shipping S.L.).
 */
class FluidCheckout_PacklinkPROShipping extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'packlink_shipping_method';

	/**
	 * Session field name.
	 */
	public const SESSION_FIELD_NAME = 'packlink_drop_off_extra';


	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper';



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
		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping', array( $this, 'maybe_set_substep_incomplete_shipping' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Shipping methods
		add_filter( 'fc_shipping_method_option_image_html', array( $this, 'maybe_change_shipping_method_option_image_html' ), 10, 2 );

		// Alter class method from Packlink PRO
		$this->remove_action_for_class( 'woocommerce_after_shipping_rate', array( 'Packlink\WooCommerce\Components\Checkout\Checkout_Handler', 'after_shipping_rate' ), 10 );
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'alter_packlink_after_shipping_rate' ), 10, 2 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-packlink-pro-shipping', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/packlink-pro-shipping/checkout-packlink-pro-shipping' ), array( 'jquery', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-packlink-pro-shipping', 'window.addEventListener("load",function(){CheckoutPacklinkProShipping.init(fcSettings.checkoutPacklinkProShipping);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-packlink-pro-shipping', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/packlink-pro-shipping/checkout-validation-packlink-pro-shipping' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-packlink-pro-shipping', 'window.addEventListener("load",function(){CheckoutValidationPacklinkProShipping.init(fcSettings.checkoutValidationPacklinkProShipping);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-packlink-pro-shipping' );
		wp_enqueue_script( 'fc-checkout-validation-packlink-pro-shipping' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationPacklinkProShipping' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param  array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'input[name="packlink_drop_off_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="packlink_drop_off_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="packlink_drop_off_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Alter `after_shipping_rate` method from Packlink.
	 * 
	 * @param  WC_Shipping_Rate  $rate   The shipping rate.
	 * @param  int               $index  The index of the shipping rate.
	 */
	function alter_packlink_after_shipping_rate( $rate, $index ) {
		// Bail if class is not available
		if ( ! class_exists( 'Packlink\WooCommerce\Components\Checkout\Checkout_Handler' ) ) { return; }

		// Bail if shipping method is not Packlink PRO
		if ( ! $this->is_shipping_method_packlink( $rate->id ) ) { return; }

		// Initialize flag
		$is_target_method_selected = false;

		// Get shipping packages
		$packages = WC()->shipping()->get_packages();

		// Iterate shipping packages
		foreach ( $packages as $i => $package ) {
			// Get selected shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;

			// Skip if not a chosen method
			if ( null === $method || $chosen_method !== $rate->id ) { continue; }

			// Maybe set flag to true if the shipping method is a local pickup method from this plugin.
			if ( $this->is_shipping_method_local_pickup( $chosen_method, $method ) ) {
				$is_target_method_selected = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $is_target_method_selected ) { return; }

		// Get Packlink checkout handler instance
		$handler = new Packlink\WooCommerce\Components\Checkout\Checkout_Handler();

		// Bail if method is not available
		if ( ! method_exists( $handler, 'after_shipping_rate' ) ) { return; }

		// Get default method output
		ob_start();
		$handler->after_shipping_rate( $rate, $index );
		$output = ob_get_clean();

		// Remove image fields
		$output = preg_replace( '/<img[^>]+>/', '', $output );
		$output = preg_replace( '/<input[^>]+name="packlink_image_url"[^>]+>/', '', $output );

		// Print the output
		echo $output;
	}



	/**
	 * Check whether the shipping method ID is Packlink PRO.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_packlink( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Get whether the shipping method is a local pickup method from this plugin.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 * @param  object  $method              The shipping method object.
	 * @param  object  $order               The order object.
	 */
	public function is_shipping_method_local_pickup( $shipping_method_id, $method = null, $order = null ) {
		// Bail if plugin class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return false; }

		// Bail if method is not available
		if ( ! method_exists( self::CLASS_NAME, 'get_packlink_shipping_method' ) ) { return false; }

		// Get shipping method instance ID
		$instance_id_parts = explode( ':', $shipping_method_id );
		$instance_id = (int) end( $instance_id_parts );

		// Get Packlink shipping method object
		$packlink_method = Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper::get_packlink_shipping_method( $instance_id );

		// Check if shipping method is a local pickup method
		if ( is_object( $packlink_method ) && method_exists( $packlink_method, 'isDestinationDropOff' ) && $packlink_method->isDestinationDropOff() ) {
			return true;
		}

		return false;
	}



	/**
	 * Maybe get selected shipping method object if it matches the target method.
	 */
	public function maybe_get_selected_shipping_method() {
		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if a target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_packlink( $chosen_method ) ) {
				return $chosen_method;
			}
		}

		// Return false if the chosen shipping method is not a target shipping method
		return false;
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
	 * Get the selected terminal data.
	 */
	public function get_selected_terminal_data() {
		// Get session field value
		$terminal_data = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if terminal data is not available
		if ( empty( $terminal_data ) ) { return; }

		// Decode terminal data
		$terminal_data = json_decode( $terminal_data, true );

		// Assign terminal object property values to the corresponding array keys
		$selected_terminal_data = array(
			'company' => isset( $terminal_data['name'] ) ? esc_html( $terminal_data['name'] ) : '',
			'address_1' => isset( $terminal_data['address'] ) ? $terminal_data['address'] : '',
			'postcode' => isset( $terminal_data['zip'] ) ? esc_html( $terminal_data['zip'] ) : '',
			'city' => isset( $terminal_data['city'] ) ? esc_html( $terminal_data['city'] ) : '',
			'state' => isset( $terminal_data['state'] ) ? esc_html( $terminal_data['state'] ) : '',
		);

		return $selected_terminal_data;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get selected shipping method ID
		$shipping_method_id = $this->maybe_get_selected_shipping_method();

		// Bail if target shipping method is not selected
		if ( empty( $shipping_method_id ) ) { return $review_text_lines; }

		// Bail if not a local pickup method
		if ( ! $this->is_shipping_method_local_pickup( $shipping_method_id ) ) { return $review_text_lines; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Bail if there terminal data is not available
		if ( empty( $terminal_data ) ) { return $review_text_lines; }

		// Format data
		$formatted_address = WC()->countries->get_formatted_address( $terminal_data );

		// Add formatted address to the review text lines
		$review_text_lines[] = $formatted_address;

		return $review_text_lines;
	}



	/**
	 * Set the shipping substep as incomplete when no pickup point is selected for the target shipping method.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping( $is_substep_complete ) {
		// Get selected shipping method
		$shipping_method = $this->maybe_get_selected_shipping_method();

		// Bail if selected shipping method is not available
		if ( ! is_object( $shipping_method ) ) { return $is_substep_complete; }

		// Bail if selected shipping method is not a local pickup method
		if ( ! $this->is_shipping_method_local_pickup( $shipping_method->id ) ) { return $is_substep_complete; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Maybe set step as incomplete if terminal data is not set
		if ( empty( $terminal_data ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Maybe change the shipping method option image HTML.
	 * 
	 * @param  string  $html     The HTML of the shipping method option image.
	 * @param  object  $method   The shipping method object.
	 */
	public function maybe_change_shipping_method_option_image_html( $html, $method ) {
		// Bail if not a shipping method from this plugin
		if ( ! $this->is_shipping_method_packlink( $method->id ) ) { return $html; }

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $html; }

		// Bail if method is not available
		if ( ! method_exists( self::CLASS_NAME, 'get_packlink_shipping_method' ) ) { return $html; }

		// Get plugin's shipping method object
		$method_id = $method->get_instance_id();
		$packling_method = Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper::get_packlink_shipping_method( $method_id );

		// Bail if method is not available
		if ( ! method_exists( $packling_method, 'isDisplayLogo' ) ) { return $html; }

		// Bail if image should not be displayed
		if ( ! $packling_method->isDisplayLogo() ) { return $html; }

		// Get image URL for the chosen carrier
		$image_url = $packling_method->getLogoUrl();

		// If no image is available, use the default one
		if ( ! $image_url ) {
			$image_url = trailingslashit( plugins_url() ) . 'packlink-pro-shipping/resources/images/box.svg';
		}

		// Define image HTML
		$html = '<img class="shipping_logo" src="' . $image_url . '" alt="Packlink PRO Shipping"/>';

		return $html;
	}

}

FluidCheckout_PacklinkPROShipping::instance();

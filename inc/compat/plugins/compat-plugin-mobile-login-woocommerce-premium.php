<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: OTP Login/Signup Woocommerce Premium (by XootiX).
 */
class FluidCheckout_MobileLoginWoocommercePremium extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'Xoo_Ml_Phone_Frontend';



	/**
	 * Class instance for the plugin which this compatibility class is related to.
	 */
	public $class_instance;

	/**
	 * Plugin settings array.
	 */
	public $plugin_settings;



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->get_plugin_settings();

		$this->hooks();
	}



	/**
	 * Get plugin instance.
	 */
	public function get_plugin_settings() {
		// Bail if plugin class or method is not available
		if ( ! class_exists( self::CLASS_NAME ) || ! method_exists( self::CLASS_NAME, 'get_instance' ) ) { return; }

		// Get plugin class instance
		$this->class_instance = call_user_func( array( self::CLASS_NAME, 'get_instance' ) );

		// Bail if plugin settings are not avaialble
		if ( ! isset( $this->class_instance->settings ) ) { return; }

		// Get plugin settings
		$this->plugin_settings = $this->class_instance->settings;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Contact step hooks
		$this->contact_step_hooks();

		// Billing step hooks
		$this->billing_step_hooks();

		// Add additional condition to display plugin's phone field
		$this->maybe_add_plugin_phone_field_to_checkout();

		// Plugin's list of forms with phone input field
		add_filter( 'xoo_ml_get_phone_forms', array( $this, 'maybe_add_form_for_verified_users' ), 10 );

		// Plugin phone field args
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_plugin_phone_field_args' ), 10000 ); // Set hight priority to run after the plugin's function

		// Add hidden fields fragment
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_hidden_fields_fragment' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Default plugin phone field args
		add_filter( 'xoo_ml_phone_input_field_args', array( $this, 'change_default_plugin_phone_field_args' ), 10 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Change plugin's scripts hook priority
		remove_action( 'wp_enqueue_scripts' , array( $this->class_instance, 'enqueue_scripts' ), 0 );
		add_action( 'wp_enqueue_scripts' , array( $this->class_instance, 'enqueue_scripts' ), 6 );
	}



	/*
	* Add or remove hooks when phone field is displayed on the contact step.
	*/
	public function contact_step_hooks() {
		// Bail if phone field is not set to be displayed on the contact step
		if ( 'contact' !== FluidCheckout_Settings::instance()->get_option( 'fc_billing_phone_field_position' ) ) { return; }

		// Add plugin's phone field to contact fields
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_plugin_phone_field_to_contact_fields' ), 10 );

		// Add hidden fields
		add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_contact', array( $this, 'maybe_set_substep_incomplete' ), 10 );

		// Review text lines
		add_filter( 'fc_substep_text_contact_field_keys_skip_list', array( $this, 'maybe_remove_phone_number_from_text_lines' ), 10 );
		add_filter( 'fc_substep_contact_text_lines', array( $this, 'add_substep_text_lines' ), 10 );

		// Review text lines on the order pay page
		add_filter( 'fc_pro_order_pay_substep_contact_text_lines', array( $this, 'maybe_add_phone_field_to_review_text_lines' ), 20, 2 );
	}

	/*
	* Add or remove hooks when phone field is displayed on the billing step.
	*/
	public function billing_step_hooks() {
		// Bail if phone field is not set to be displayed on the billing step
		if ( 'billing_address' !== FluidCheckout_Settings::instance()->get_option( 'fc_billing_phone_field_position' ) ) { return; }

		// Add hidden fields
		add_action( 'woocommerce_checkout_billing', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Review text lines
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'maybe_remove_phone_number_from_text_lines' ), 10 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'add_substep_text_lines' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete' ), 10 );

		// Review text lines on the order pay page
		add_filter( 'fc_pro_order_pay_substep_billing_address_text_lines', array( $this, 'maybe_add_phone_field_to_review_text_lines' ), 20, 2 );
	}



	/**
	 * Maybe add the plugin's phone field to the checkout page even if a user already verified phone number.
	 */
	public function maybe_add_plugin_phone_field_to_checkout() {
		// Bail if plugin class instance is not available
		if ( ! $this->class_instance ) { return; }

		// Get phone number from user meta
		$user_phone_number = xoo_ml_get_user_phone( get_current_user_id(), 'number' );

		// Bail if user's phone number is not set
		if ( ! $user_phone_number ) { return; }

		// Add plugin's phone field to the checkout page
		add_filter( 'woocommerce_form_field', array( $this->class_instance, 'wc_checkout_otp_field_html' ), 10, 4 );
		add_filter( 'woocommerce_checkout_fields' , array( $this->class_instance, 'wc_checkout_add_otp_field' ), 9999 );
	}



	/**
	 * Maybe add form for verified users.
	 * This is for repeated verification of the phone number on the checkout page.
	 *
	 * @param   array  $forms  List of forms with phone input field.
	 */
	public function maybe_add_form_for_verified_users( $forms ) {
		// Bail if plugin class instance or plugin settings are not available
		if ( ! $this->class_instance || ! $this->plugin_settings ) { return; }

		// Get phone number from user meta
		$user_phone_number = xoo_ml_get_user_phone( get_current_user_id(), 'number' );

		// Bail if user's phone number is not set
		if ( ! $user_phone_number ) { return $forms; }

		// Bail if array with the same 'key' already exists
		foreach ( $forms as $form ) {
			if ( 'woocommerce-process-checkout-nonce' === $form[ 'key' ] ) {
				return $forms;
			}
		}

		// Add form for users with verified phone number
		$forms[] = array(
			'key'         => 'woocommerce-process-checkout-nonce',
			'value'       => '',
			'form'        => is_user_logged_in() ? 'update_user' : 'register_user',
			'required'    => $this->is_phone_field_required() ? 'yes' : 'no',
			'cc_required' => $this->is_country_code_field_enabled() ? 'yes' : 'no',
		);

		return $forms;
	}



	/**
	 * Check if plugin's phone field is required.
	 */
	public function is_phone_field_required() {
		$is_required = false;

		// Bail if plugin settings are not available
		if ( ! $this->plugin_settings ) { return $is_required; }

		// Check if phone field is required
		if ( isset( $this->plugin_settings[ 'r-phone-field' ] ) && 'required' === $this->plugin_settings[ 'r-phone-field' ] ) {
			$is_required = true;
		}

		return $is_required;
	}

	/**
	 * Check if country code field is enabled.
	 */
	public function is_country_code_field_enabled() {
		$is_enabled = false;

		// Bail if plugin settings are not available
		if ( ! $this->plugin_settings ) { return $is_enabled; }

		// Check if country code field is enabled
		if ( isset( $this->plugin_settings[ 'r-enable-cc-field' ] ) && 'yes' === $this->plugin_settings[ 'r-enable-cc-field' ] ) {
			$is_enabled = true;
		}

		return $is_enabled;
	}



	/**
	 * Get default country code.
	 */
	public function get_default_country_code() {
		$country_code = '';

		// Bail if plugin settings are not available
		if ( ! $this->plugin_settings ) { return $country_code; }

		// Bail required class and method are not available
		if ( ! class_exists( 'Xoo_Ml_Geolocation' ) || ! method_exists( 'Xoo_Ml_Geolocation', 'get_phone_code' )) { return $country_code; }

		// Maybe get default country code based on the plugin settings
		if ( 'geolocation' === $this->plugin_settings[ 'r-default-country-code-type' ] ) {
			$country_code = Xoo_Ml_Geolocation::get_phone_code();
		} else {
			$country_code = $this->plugin_settings[ 'r-default-country-code' ];
		}

		return $country_code;
	}



	/**
	 * Check if phone number is verified.
	 *
	 * @param   string  $phone_number  Phone number to check.
	 * @param   string  $country_code  Country code of the phone number.
	 */
	public function is_phone_number_verified( $phone_number, $country_code = '' ) {
		$is_verified = false;

		// Bail if plugin class or method is not available
		if ( ! class_exists( 'Xoo_Ml_Otp_Handler' ) || ! method_exists( 'Xoo_Ml_Otp_Handler', 'get_otp_data' ) ) { return $is_verified; }

		// Bail if plugin function is not available
		if ( ! function_exists( 'xoo_ml_get_user_phone' ) ) { return $is_verified; }

		// Bail if phone number is not set
		if ( ! $phone_number ) { return $is_verified; }

		// Maybe get default country code
		if ( ! $this->is_country_code_field_enabled() ) {
			$country_code = $this->get_default_country_code();
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			// Get user's phone number
			$user_phone_number = xoo_ml_get_user_phone( $user_id, 'number' );
			$user_country_code = xoo_ml_get_user_phone( $user_id, 'code' );

			// Check if phone number belongs to the logged in user
			if ( $user_phone_number && $user_country_code && $user_phone_number === $phone_number && $user_country_code === $country_code ) {
				$is_verified = true;
				return $is_verified;
			}
		}

		// Get OTP verification data based on user's IP address
		$phone_otp_data = Xoo_Ml_Otp_Handler::get_otp_data();
		if( ! is_array( $phone_otp_data ) ){
			$phone_otp_data = array();
		}

		// Check if phone number is verified
		if( isset( $phone_otp_data[ 'phone_no' ] ) && $phone_otp_data[ 'phone_no' ] === $phone_number && isset( $phone_otp_data[ 'phone_code' ] ) && $phone_otp_data[ 'phone_code' ] === $country_code && isset( $phone_otp_data[ 'verified' ] ) && $phone_otp_data[ 'verified' ] ){
			$is_verified = true;
		}

		return $is_verified;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get entered phone number
		$phone_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone' );
		$country_code = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone-cc' );

		// Check if phone_number is verified
		$is_verified = $this->is_phone_number_verified( $phone_number, $country_code );

		// Output custom hidden fields
		echo '<div id="mobile-login-woo-custom_checkout_fields" class="form-row fc-no-validation-icon mobile-login-woo-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="mobile-login-woo-is_verified" name="mobile-login-woo-is_verified" value="'. esc_attr( $is_verified ) .'" class="validate-mobile-login-woo">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Add hidden fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_contact_hidden_fields_fragment( $fragments ) {
		// Get custom hidden fields HTML
		ob_start();
		$this->output_custom_hidden_fields();
		$html = ob_get_clean();

		// Add fragment
		$fragments[ '.mobile-login-woo-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Change the plugin's default phone field args to be able to update phone number from the checkout page.
	 *
	 * @param   array  $args  The phone field arguments.
	 */
	public function change_default_plugin_phone_field_args( $args ) {
		// Bail if plugin function is not available
		if ( ! function_exists( 'xoo_ml_get_user_phone' ) ) { return $args; }

		// New args to set
		$new_args = array();

		// Get phone number from session
		$phone_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone' );
		$country_code = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone-cc' );

		// Maybe get verified phone number for logged in users
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			// Get user's phone number
			$user_phone_number = xoo_ml_get_user_phone( $user_id, 'number' );
			$user_country_code = xoo_ml_get_user_phone( $user_id, 'code' );

			// Maybe set existing phone number and country code
			if ( $user_phone_number && $user_country_code ) {
				$phone_number = $user_phone_number;
				$country_code = $user_country_code;
			}
		}

		// Maybe set default phone number
		if ( $phone_number ) {
			$new_args[ 'default_phone' ] = $phone_number;
		}

		// Set default country code
		$new_args[ 'default_cc' ] = $this->get_default_country_code();

		// Change default country code if the field is enabled and has a value
		if ( $this->is_country_code_field_enabled() && $country_code ) {
			$new_args[ 'default_cc' ] = $country_code;
		}

		// Merge new args with the original args
		$args = array_merge( $args, $new_args );

		return $args;
	}



	/**
	 * Add the phone field from the plugin to the list of fields to display on the contact step.
	 *
	 * @param   array  $display_fields  List of fields to display on the contact step.
	 */
	public function add_plugin_phone_field_to_contact_fields( $display_fields ) {
		$display_fields[] = 'xoo-ml-reg-phone';
		return $display_fields;
	}



	/**
	 * Maybe change the plugin's phone field arguments.
	 *
	 * @param   array  $fields  The checkout fields.
	 */
	public function maybe_change_plugin_phone_field_args( $fields ) {
		// Define variables
		$field_key = 'xoo-ml-reg-phone';

		// Bail if field is not present
		if ( ! array_key_exists( $field_key, $fields[ 'billing' ] ) ) { return $fields; }

		// Get default phone field priority
		$default_phone_field_priority = 30;
		if ( array_key_exists( 'billing_phone', $fields ) ) {
			$default_phone_field_priority = $fields[ 'billing_phone' ][ 'priority' ];
		}

		// Set priority to a higher value to display after the default phone field if both are displayed
		$fields[ 'billing' ][ $field_key ][ 'priority' ] = $default_phone_field_priority + 5;

		// Maybe remove 'form-row-first' class inherited from the default phone field
		$index = array_search( 'form-row-first', $fields[ 'billing' ][ $field_key ][ 'wc_cont_class' ], true );
		if ( false !== $index ) {
			unset( $fields[ 'billing' ][ $field_key ][ 'wc_cont_class' ][ $index ] );
		}

		// Set field as required
		$fields[ 'billing' ][ $field_key ][ 'required' ] = true;

		return $fields;
	}



	/**
	 * Set the substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if phone field is not required
		if ( ! $this->is_phone_field_required() ) { return $is_substep_complete; }

		// Get entered phone number
		$phone_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone' );

		// Maybe get entered country code
		$country_code = '';
		if ( $this->is_country_code_field_enabled() ) {
			$country_code = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'xoo-ml-reg-phone-cc' );
		}

		// Check if phone_number is verified
		$is_verified = $this->is_phone_number_verified( $phone_number, $country_code );

		// Maybe set step as incomplete
		if ( ! $is_verified ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Maybe remove the plugin's phone field from the substep review text lines.
	 * 
	 * @param  array  $field_keys_skip_list  The list of field keys to skip in the substep review text.
	 */
	public function maybe_remove_phone_number_from_text_lines( $field_keys_skip_list ) {
		$field_keys_skip_list[] = 'xoo-ml-reg-phone';
		return $field_keys_skip_list;
	}

	/**
	 * Add the plugin's phone field value to the substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines( $review_text_lines = array() ) {
		// Bail if default billing field is not disabled in the plugin setttings to avoid phone number duplication
		if ( isset( $this->plugin_settings[ 'wc-chk-bphone' ] ) && 'nothing' === $this->plugin_settings[ 'wc-chk-bphone' ] ) { return $review_text_lines; }

		$phone_number_field_key = 'xoo-ml-reg-phone';
		$country_code_field_key = 'xoo-ml-reg-phone-cc';

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get entered phone number
		$country_code = WC()->checkout->get_value( $country_code_field_key );
		$phone_number = WC()->checkout->get_value( $phone_number_field_key );

		// Add phone number with country code to the review text
		$review_text_lines[] = $country_code . $phone_number;

		return $review_text_lines;
	}



	/**
	 * Myabe add plugin's phone field value to the substep review text lines.
	 * 
	 * @param   array     $review_text_lines   The list of lines to show in the substep review text.
	 * @param   WC_Order  $order               The order object.
	 */
	public function maybe_add_phone_field_to_review_text_lines( $review_text_lines, $order ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if default billing field is not replaced by the plugin field
		if ( ! isset( $this->plugin_settings[ 'wc-chk-bphone' ] ) || ! 'disable_merge' === $this->plugin_settings[ 'wc-chk-bphone' ] ) { return $review_text_lines; }

		// Get the default billing phone key since the plugin replaces it after order is created
		$field_key = 'billing_phone';

		// Add field value to the review text
		$field_value = FluidCheckout_PRO_OrderPayPage::instance()->get_order_data( $field_key, $order );
		$review_text_lines[] = FluidCheckout_Steps::instance()->get_field_display_value( $field_value, $field_key, array() );

		return $review_text_lines;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Plugin's phone field script
		wp_register_script( 'xoo-ml-phone-js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mobile-login-woocommerce-premium/xoo-ml-phone-js' ), array( 'jquery' ), NULL );

		// Checkout scripts
		wp_register_script( 'fc-checkout-mobile-login-woocommerce-premium', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mobile-login-woocommerce-premium/checkout-mobile-login-woocommerce-premium' ), array( 'jquery', 'fc-utils' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-mobile-login-woocommerce-premium', 'window.addEventListener("load",function(){CheckoutMobileLoginWoocommercePremium.init(fcSettings.checkoutMobileLoginWoocommercePremium);})' );

		// Validation scripts
		wp_register_script( 'fc-checkout-validation-mobile-login-woocommerce-premium', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mobile-login-woocommerce-premium/checkout-validation-mobile-login-woocommerce-premium' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-validation-mobile-login-woocommerce-premium', 'window.addEventListener("load",function(){CheckoutValidationMobileLoginWoocommercePremium.init(fcSettings.checkoutValidationMobileLoginWoocommercePremium);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-mobile-login-woocommerce-premium' );

		// Add validation script if phone field is required
		if ( $this->is_phone_field_required() ) {
			wp_enqueue_script( 'fc-checkout-validation-mobile-login-woocommerce-premium' );
		}
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add checkout settings
		$settings[ 'checkoutMobileLoginWoocommercePremium' ] = array(
			// Use the same values as in the plugin to replicate the same functionality
			'strings'  => array(
				'verified' => __( '<span class="dashicons dashicons-yes"></span>', 'mobile-login-woocommerce' ),
				'verify' => __( 'Verify', 'mobile-login-woocommerce' ),
			),
		);

		// Add validation settings
		$settings[ 'checkoutValidationMobileLoginWoocommercePremium' ] = array(
			'validationMessages'  => array(
				'phone_number_not_verified' => __( 'Please verify your phone number.', 'fluid-checkout' ),
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
		$settings[ 'validateFieldsSelector' ] = '.xoo-ml-phone-input' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = '.xoo-ml-phinput-cont' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = '.xoo-ml-phone-input' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_MobileLoginWoocommercePremium::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Wawp - OTP Verification, Order Notifications, and Country Code Selector for WooCommerce (by Wawp).
 */
class FluidCheckout_WawpOTPVerification extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'awp_checkout_otp';



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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
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

		// OTP verification popup
		$this->otp_verification_hooks();
	}

	/**
	 * Add or remove hooks when OTP verification is enabled.
	 */
	public function otp_verification_hooks() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if class object is not available
		if ( ! is_object( $class_object ) ) { return; }

		$is_enabled = FluidCheckout_Settings::instance()->get_option( 'awp_enable_otp', 'no' );
		$is_visitors_only = FluidCheckout_Settings::instance()->get_option( 'awp_enable_otp_for_visitors', 'no' );

		// Bail if OTP verification is not enabled
		if ( 'yes' !== $is_enabled ) { return; }

		// Bail if OTP verification is enabled for visitors only and the user is logged in
		if ( 'yes' === $is_visitors_only && is_user_logged_in() ) { return; }
		
		// Move OTP verification popup
		remove_action( 'woocommerce_after_order_notes', array( $class_object, 'add_otp_verification_popup' ), 10 );
		add_action( 'the_content', array( $this, 'add_otp_verification_popup' ), 10, 2 );

		// Output hidden fields
		add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Add substep fragments
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_hidden_fields_fragment' ), 10 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_contact', array( $this, 'maybe_set_step_incomplete_contact' ), 10 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );
	}



	/**
	 * Add OTP verification popup to the page content.
	 * 
	 * @param  string  $content  The content.
	 */
	public function add_otp_verification_popup( $content ) {
		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if class method is not available
		if ( ! method_exists( $class_object, 'add_otp_verification_popup' ) ) { return $content; }

		// Get OTP verification popup
		ob_start();
		$class_object->add_otp_verification_popup();
		$popup_content = ob_get_clean();

		// Replace the button label
		$popup_content = str_replace( __( 'Confirm order', 'awp' ), __( 'Back to checkout', 'fluid-checkout' ), $popup_content );

		// Prepend to the page content
		$content .= $popup_content;

		return $content;
	}



	/**
	 * Check if phone number is verified.
	 *
	 * @param   string  $phone_number  Phone number to check.
	 */
	public function is_phone_number_verified( $phone_number ) {
		$is_verified = false;

		// Bailf if phone number is empty
		if ( empty( $phone_number ) ) { return $is_verified; }

		// Get blocked numbers and format them the same way as in the plugin
		$blocked_numbers = FluidCheckout_Settings::instance()->get_option( 'awp_blocked_numbers', '' );
		$blocked_numbers = explode( ',', $blocked_numbers );
		$blocked_numbers = array_map( 'trim', $blocked_numbers );

		// Bail if phone number is blocked
		if ( in_array( $phone_number, $blocked_numbers ) ) { return $is_verified; }

		// Check if phone number is verified for the current user or in the session
		if ( is_user_logged_in() ) {
			// Get current user phone number
			$current_user = wp_get_current_user();
			$user_phone_number = get_user_meta( $current_user->ID, 'billing_phone', true );
			$phone_verified = get_user_meta( $current_user->ID, 'phone_verified', true );

			// Check if the phone number belongs to the current user and is verified
			if ( $phone_number === $user_phone_number && $phone_verified ) {
				$is_verified = true;
			}
		} elseif ( WC()->session->get( 'otp_verified' ) ) {
			$is_verified = true;
		}

		return $is_verified;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get entered phone number
		$phone_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_phone' );

		// Check if phone_number is verified
		$is_verified = $this->is_phone_number_verified( $phone_number );

		// Output custom hidden fields
		echo '<div id="wawp-custom_checkout_fields" class="form-row fc-no-validation-icon wawp-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="wawp-is_verified" name="wawp-is_verified" value="'. esc_attr( $is_verified ) .'" class="validate-wawp">';
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
		$fragments[ '.wawp-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Set the contact step as incomplete.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_contact( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }

		// Get entered phone number
		$phone_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_phone' );

		// Check if phone_number is verified
		$is_verified = $this->is_phone_number_verified( $phone_number );

		// Maybe set step as incomplete
		if ( ! $is_verified ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'awp-checkout-js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/automation-web-platform/checkout' ), array( 'jquery' ), NULL );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-automation-web-platform', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/automation-web-platform/checkout-validation-automation-web-platform' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-validation-automation-web-platform', 'window.addEventListener("load",function(){CheckoutValidationWAWP.init(fcSettings.checkoutValidationWAWP);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-automation-web-platform' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationWAWP' ] = array(
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="wawp-is_verified"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="wawp-is_verified"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="wawp-is_verified"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_WawpOTPVerification::instance();

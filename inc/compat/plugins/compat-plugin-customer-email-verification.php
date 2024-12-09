<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Customer Email Verification (PRO) (by zorem).
 */
class FluidCheckout_CustomerEmailVerification extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'Customer_Email_Verification_Email_Settings';



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
		// Maybe subset step as incomplete
		add_filter( 'fc_is_substep_complete_contact', array( $this, 'maybe_set_substep_incomplete_contact' ), 10 );

		// Output hidden fields
		add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Add substep fragments
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_hidden_fields_fragment' ), 10 );

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
	 * Check if email is verified.
	 *
	 * @param   string  $email  Email address to check.
	 */
	public function is_email_verified( $email ) {
		$is_verified = false;

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $is_verified; }

		// Bail if class methods are not available
		if ( ! method_exists( self::CLASS_NAME, 'get_instance' ) ) { return $is_verified; }

		// Get object
		$class_object = call_user_func( array( self::CLASS_NAME, 'get_instance' ) );

		// Bail if object or method are not available
		if ( ! $class_object || ! method_exists( $class_object, 'check_email_verify' ) ) { return $is_verified; }

		// Check if email is verified
		$is_verified = $class_object->check_email_verify( $email );

		// Check if email is verified in session
		if ( ! $is_verified ) {
			$session_data = json_decode( WC()->session->get( 'cev_user_verified_data' ) );

			if ( isset( $session_data->email ) && $session_data->email === $email && true === $session_data->verified ) {
				$is_verified = true;
			}
		}

		return $is_verified;
	}



	/**
	 * Check if inline verification is enabled.
	 */
	public function is_inline_verification_enabled() {
		// Initialize variables
		$is_enabled = false;

		// Bail if user is logged in
		if ( is_user_logged_in() ) { return $is_enabled; }

		// Get plugin settings
		$cev_enable_email_verification_checkout = FluidCheckout_Settings::instance()->get_option( 'cev_enable_email_verification_checkout', 1 );
		$cev_inline_email_verification_checkout = FluidCheckout_Settings::instance()->get_option( 'cev_verification_checkout_dropdown_option' );
		$cev_enable_email_verification_free_orders = FluidCheckout_Settings::instance()->get_option( 'cev_enable_email_verification_free_orders' );

		// Get order subtotal
		$order_subtotal = WC()->cart->subtotal;

		// Use the same conditions as the plugin's checks for 'cev-inline-front-js' script enqueuing
		$need_inline_verification = false;
		if ( ( $order_subtotal > 0 && 1 != $cev_enable_email_verification_free_orders ) ) {
			$need_inline_verification = true;
		} elseif ( 0 == $order_subtotal && 1 == $cev_enable_email_verification_checkout && 2 == $cev_inline_email_verification_checkout ) {			
			$need_inline_verification = true;
		}

		if ( 1 == $cev_enable_email_verification_checkout && 2 == $cev_inline_email_verification_checkout && $need_inline_verification && ! is_user_logged_in() ) {
			$is_enabled = true;
		}

		return $is_enabled;
	}



	/**
	 * Set the contact substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_contact( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if inline verification is not enabled
		if ( ! $this->is_inline_verification_enabled() ) { return $is_substep_complete; }

		// Get entered email
		$email = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_email' );

		// Check if email is verified
		$is_verified = $this->is_email_verified( $email );

		// Maybe set step as incomplete
		if ( ! $is_verified ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Bail if inline verification is not enabled
		if ( ! $this->is_inline_verification_enabled() ) { return; }

		// Get entered email
		$email = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_email' );

		// Check if email is verified
		$is_verified = $this->is_email_verified( $email );

		// Output custom hidden fields
		echo '<div id="customer_email_verification-custom_checkout_fields" class="form-row fc-no-validation-icon customer_email_verification-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="customer_email_verification-is_verified" name="customer_email_verification-is_verified" value="' . esc_attr( $is_verified ) . '" class="validate-customer-email-verification">';
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
		$fragments[ '.customer_email_verification-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-customer-email-verification', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/customer-email-verification/checkout-customer-email-verification' ), array( 'jquery', 'fc-utils', 'cev-inline-front-js' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-customer-email-verification', 'window.addEventListener("load",function(){CheckoutCustomerEmailVerification.init(fcSettings.checkoutCustomerEmailVerification);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-customer-email-verification', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/customer-email-verification/checkout-validation-customer-email-verification' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-customer-email-verification', 'window.addEventListener("load",function(){CheckoutValidationCustomerEmailVerification.init(fcSettings.checkoutValidationCustomerEmailVerification);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if inline verification is not enabled
		if ( ! $this->is_inline_verification_enabled() ) { return; }

		// Scripts
		wp_enqueue_script( 'fc-checkout-customer-email-verification' );
		wp_enqueue_script( 'fc-checkout-validation-customer-email-verification' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationCustomerEmailVerification' ] = array(
			'validationMessages'  => array(
				'email_not_verified' => __( 'Please verify your email address.', 'customer-email-verification' ),
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="customer_email_verification-is_verified"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="customer_email_verification-is_verified"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="customer_email_verification-is_verified"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_CustomerEmailVerification::instance();

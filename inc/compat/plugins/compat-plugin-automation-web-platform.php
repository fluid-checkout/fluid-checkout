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
		$this->maybe_move_OTP_verification_popup();
	}



	/**
	 * Maybe move OTP verification popup.
	 */
	public function maybe_move_OTP_verification_popup() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if class object is not available
		if ( ! is_object( $class_object ) ) { return; }

		$enable_otp = FluidCheckout_Settings::instance()->get_option( 'awp_enable_otp', 'no' );
		$enable_otp_for_visitors = FluidCheckout_Settings::instance()->get_option( 'awp_enable_otp_for_visitors', 'no' );

		// Bail if OTP verification is not enabled
		if ( 'yes' !== $enable_otp && ( 'yes' !== $enable_otp_for_visitors || is_user_logged_in() ) ) { return; }
		
		// Move OTP verification popup
		remove_action( 'woocommerce_after_order_notes', array( $class_object, 'add_otp_verification_popup' ), 10 );
		add_action( 'the_content', array( $this, 'add_otp_verification_popup' ), 10, 2 );
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

		// Prepend to the page content
		$content .= $popup_content;

		return $content;
	}

}

FluidCheckout_WawpOTPVerification::instance();

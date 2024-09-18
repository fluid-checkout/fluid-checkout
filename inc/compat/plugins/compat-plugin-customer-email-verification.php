<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Customer Email Verification (by zorem).
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
		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_contact', array( $this, 'maybe_set_step_incomplete_contact' ), 10 );
	}



	/**
	 * Set the contact step as incomplete.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_contact( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $is_step_complete; }

		// Bail if class methods are not available
		if ( ! method_exists( self::CLASS_NAME, 'get_instance' ) || ! method_exists( self::CLASS_NAME, 'check_email_verify' ) ) { return $is_step_complete; }

		// Get object
		$class_object = self::CLASS_NAME::get_instance();

		// Get entered email
		$email = '';
		if ( isset( $_POST['email'] ) ) {
			$email = wc_clean( $_POST['email'] );
		}

		// Check if email is verified
		$is_verified = $class_object->check_email_verify( $email );

		// Maybe set step as incomplete
		if ( ! $is_verified ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}

}

FluidCheckout_CustomerEmailVerification::instance();

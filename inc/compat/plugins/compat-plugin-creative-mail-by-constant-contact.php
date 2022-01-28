<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Creative Mail (by Constant Contact).
 */
class FluidCheckout_CreativeMailByConstantContact extends FluidCheckout {

	public $creative_mail_email_manager;



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
		// Get the Creative Mail objects
		$this->creative_mail_email_manager = $this->get_object_by_class_name_from_hooks( 'CreativeMail\Managers\EmailManager' );

		if ( null !== $this->creative_mail_email_manager ) {

			// Move checkout consent checkbox to after the email field
			if ( CreativeMail\Helpers\OptionsHelper::get_checkout_checkbox_enabled() === '1' ) {
				remove_action( 'woocommerce_after_order_notes', array( $this->creative_mail_email_manager, 'add_checkout_field' ), 10 );
				add_action( 'fc_checkout_contact_after_fields', array( $this, 'add_checkout_field' ), 10 );
			}

		}
	}



	/**
	 * Add the email consent checkbox field to the checkout page.
	 */
	public function add_checkout_field() {
		$this->creative_mail_email_manager->add_checkout_field( WC()->checkout() );
	}

}

FluidCheckout_CreativeMailByConstantContact::instance();

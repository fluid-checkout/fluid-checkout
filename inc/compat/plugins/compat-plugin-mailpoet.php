<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MailPoet integration.
 */
class FluidCheckout_MailPoet extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		add_action( 'init', array( $this, 'hooks' ) );
	}

	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Get MailPoet object instance
		$mailpoet_hooks_instance = $this->get_object_by_class_name_from_hooks( 'MailPoet\Config\HooksWooCommerce' );

		// Bail if MailPoet object is not found in hooks
		if ( ! $mailpoet_hooks_instance ) { return; }

		// Move subscribe checkbox position
		$this->remove_action_for_class( 'woocommerce_checkout_before_terms_and_conditions', array( $mailpoet_hooks_instance, 'extendWooCommerceCheckoutForm' ), 10 );
		add_action( 'fc_checkout_contact_after_fields', array( $mailpoet_hooks_instance, 'extendWooCommerceCheckoutForm' ), 10 );
	}

}

FluidCheckout_MailPoet::instance(); 

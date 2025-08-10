<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: FooEvents for WooCommerce (by FooEvents).
 */
class FluidCheckout_FooEvents extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Validation script settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'add_email_selector_to_validation_script_settings' ), 10 );
	}



	/**
	 * Add FooEvents email selector to Fluid Checkout validation script settings.
	 * The plugin's default validation is omitted because it outputs redundant information about attendees,
	 * which is already clear from the field's position/context.
	 *
	 * @param  string  $settings  The script settings.
	 */
	public function add_email_selector_to_validation_script_settings( $settings ) {
		$settings[ 'typeEmailSelector' ] = '.validate-email, .fooevents-attendee-email';
		return $settings;
	}

}

FluidCheckout_FooEvents::instance();

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Validation script settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'add_email_selector_to_validation_script_settings' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );
	}

	/**
	 * Replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Plugin's script
		wp_register_script( 'woocommerce-events-front-script', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/fooevents/events-frontend' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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

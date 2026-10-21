<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: German Market (by MarketPress).
 */
class FluidCheckout_WooCommerceGermanMarket extends FluidCheckout {

	/**
	 * Temporarily holds the place order button HTML.
	 *
	 * @var string
	 */
	public static $button_html;



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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Place order position
		add_filter( 'pre_option_fc_checkout_place_order_position', array( $this, 'change_place_order_position_option' ), 10, 3 );
		add_filter( 'fc_checkout_general_settings', array( $this, 'change_place_order_position_settings_args' ), 20 ); // Priority needs to be higher than that used by the PRO plugin to enable the settings fields (10)
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Has place order button placement changes
		if ( has_filter( 'woocommerce_order_button_html', array( 'WGM_Template', 'remove_order_button_html' ) ) ) {
			// General
			add_filter( 'body_class', array( $this, 'add_body_class_button_placement' ), 10 );

			// Place order
			remove_action( 'woocommerce_review_order_after_submit', array( 'WGM_Template', 'print_order_button_html' ), 9999 );
			add_filter( 'woocommerce_order_button_html', array( $this, 'retrieve_order_button_html' ), 9998 );
			add_filter( 'woocommerce_order_button_html', array( $this, 'restore_order_button_html' ), 10000 );

			// Legal checkboxes
			if ( 'on' === FluidCheckout_Settings::instance()->get_option( 'gm_order_review_checkboxes_before_order_review' ) ) {
				remove_action( 'woocommerce_de_checkout_payment', array( 'WGM_Template', 'add_review_order' ), 10 );
				add_action( 'woocommerce_checkout_before_order_review', array( 'WGM_Template', 'add_review_order' ), 10 );
			}
		}
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		$classes[] = 'has-fc-compat-german-market';
		return $classes;
	}

	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class_button_placement( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		$classes[] = 'has-fc-compat-german-market-button-placement';
		return $classes;
	}



	/**
	 * Change the option for the place order position to always `below_order_summary` when using German Market.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function change_place_order_position_option( $pre_option, $option, $default ) {
		return 'below_order_summary';
	}

	/**
	 * Disable the place order position setting and explain why it is forced when using German Market.
	 *
	 * @param   array  $settings  Admin settings args values.
	 */
	public function change_place_order_position_settings_args( $settings ) {
		// Define the settings field description
		$description = __( 'The place order position is always set to "Below the order summary" when using German Market. That plugin requires the place order button and legal checkboxes to be displayed below the order summary.', 'fluid-checkout' );

		return FluidCheckout_Settings::instance()->disable_settings_field( $settings, 'fc_checkout_place_order_position', $description );
	}



	/**
	 * Retrieve and save the contents of the place order button to a local variable.
	 *
	 * @param   string  $button_html  The place order button html.
	 */
	public function retrieve_order_button_html( $button_html ) {
		self::$button_html = $button_html;
		return $button_html;
	}

	/**
	 * Restore the contents of the place order button from the local variable after German Market has remove it.
	 *
	 * @param   string  $button_html  The place order button html.
	 */
	public function restore_order_button_html( $button_html ) {
		return self::$button_html;
	}

}

FluidCheckout_WooCommerceGermanMarket::instance();

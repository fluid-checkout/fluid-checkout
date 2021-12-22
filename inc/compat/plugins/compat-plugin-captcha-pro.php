<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Captcha Pro (by BestWebSoft).
 */
class FluidCheckout_CaptchaPro extends FluidCheckout {

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

		// Admin settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		remove_action( 'woocommerce_after_checkout_billing_form', 'cptch_woocommerce_checkout' );
		
		$captcha_position_args = $this->get_captcha_position_args();
		add_action( $captcha_position_args[ 'hook' ], 'cptch_woocommerce_checkout', $captcha_position_args[ 'priority' ], $captcha_position_args[ 'args_count' ] );
	}



	public function get_captcha_position_args() {
		$captcha_position = get_option( 'fc_integration_captcha_pro_captcha_position', 'before_place_order_section' );
		
		$captcha_position_hook_priority = array(
			'before_place_order_section' => array( 'hook' => 'fc_output_step_payment', 'priority' => 95, 'args_count' => 2 ),
			'before_place_order_button'  => array( 'hook' => 'woocommerce_review_order_before_submit', 'priority' => 20, 'args_count' => 1 ),
		);

		return $captcha_position_hook_priority[ $captcha_position ];
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		
		$settings[] = array(
			'title'          => __( 'Captcha Pro by BestWebSoft', 'fluid-checkout' ),
			'desc'           => __( 'Define the position to display the captcha section. Some positions might not work depending on the captcha type chosen.', 'fluid-checkout' ),
			'id'             => 'fc_integration_captcha_pro_captcha_position',
			'options'        => array(
				'before_place_order_section'     => _x( 'Before place order section', 'Captcha position', 'fluid-checkout' ),
				'before_place_order_button'      => _x( 'Before place order button', 'Captcha position', 'fluid-checkout' ),
			),
			'default'        => 'before_place_order_section',
			'type'           => 'select',
			'autoload'       => false,
		);

		return $settings;
	}

}

FluidCheckout_CaptchaPro::instance();

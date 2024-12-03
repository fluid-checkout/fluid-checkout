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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove hooks
		remove_action( 'woocommerce_after_checkout_billing_form', 'cptch_woocommerce_checkout', 10 );

		// Add hooks
		$captcha_position_args = $this->get_captcha_position_args();
		add_action( $captcha_position_args[ 'hook' ], $captcha_position_args[ 'callback' ], 'cptch_woocommerce_checkout', $captcha_position_args[ 'priority' ], $captcha_position_args[ 'args_count' ] );
	}



	/**
	 * Get captcha position arguments.
	 */
	public function get_captcha_position_args() {
		$captcha_position = FluidCheckout_Settings::instance()->get_option( 'fc_integration_captcha_pro_captcha_position' );

		$captcha_position_hook_priority = array(
			'before_place_order_section' => array( 'hook' => 'fc_checkout_end_step', 'callback' => array( $this, 'maybe_run_cptch_woocommerce_checkout_for_substep' ), 'priority' => 95, 'args_count' => 4 ),
			'before_place_order_button'  => array( 'hook' => 'woocommerce_review_order_before_submit', 'callback' => 'cptch_woocommerce_checkout', 'priority' => 20, 'args_count' => 1 ),
		);

		return $captcha_position_hook_priority[ $captcha_position ];
	}

	/**
	 * Maybe run cptch_woocommerce_checkout for substep positions.
	 * 
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 * @param   array   $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property `$checkout_steps` of this class.
	 * @param   array   $step_index  Position of the checkout step in the steps order, uses zero-based index,`0` is the first step.
	 * @param   string  $context     Context in which the function is running. Defaults to `checkout`.
	 */
	public function maybe_run_cptch_woocommerce_checkout_for_substep( $step_id, $step_args, $step_index, $context = 'checkout' ) {
		// Bail if not on the payment step
		if ( 'payment' !== $step_id ) { return; }

		// Bail if function not available
		if ( ! function_exists( 'cptch_woocommerce_checkout' ) ) { return; }

		// Run function
		cptch_woocommerce_checkout();
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Captcha Pro by BestWebSoft', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_captcha_pro_options',
			),

			array(
				'title'          => __( 'Captcha position', 'fluid-checkout' ),
				'desc'           => __( 'Define the position to display the captcha section. Some positions might not work depending on the captcha type chosen.', 'fluid-checkout' ),
				'id'             => 'fc_integration_captcha_pro_captcha_position',
				'type'           => 'select',
				'options'        => array(
					'before_place_order_section'     => _x( 'Before place order section', 'Captcha position', 'fluid-checkout' ),
					'before_place_order_button'      => _x( 'Before place order button', 'Captcha position', 'fluid-checkout' ),
				),
				'default'        => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_captcha_pro_captcha_position' ),
				'autoload'       => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_captcha_pro_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}

}

FluidCheckout_CaptchaPro::instance();

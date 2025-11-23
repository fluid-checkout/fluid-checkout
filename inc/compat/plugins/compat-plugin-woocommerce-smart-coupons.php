<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Smart Coupons (by StoreApps).
 */
class FluidCheckout_WooCommerceSmartCoupons extends FluidCheckout {

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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Checkout hooks
		$this->checkout_hooks();
	}

	/**
	 * Add or remove checkout hooks.
	 */
	public function checkout_hooks() {
		// Bail if class is not available
		$class_name = 'WC_SC_Display_Coupons';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class object is not found
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		if ( ! is_object( $class_object ) ) { return; }

		// Get position
		$position_hook = $this->get_position_to_display_available_coupons_on_checkout();

		// Available coupons section
		remove_action( 'woocommerce_checkout_before_customer_details', array( $class_object, 'show_available_coupons_on_classic_checkout' ), 11 );
		add_action( $position_hook['hook'], array( $class_object, 'show_available_coupons_on_classic_checkout' ), $position_hook['priority'] );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'WooCommerce Smart Coupons', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_woocommerce_smart_coupons_options',
			),
		);

		$settings_new = array_merge( $settings_new, apply_filters( 'fc_integrations_woocommerce_smart_coupons_settings',
			array(
				array(	
					'title'           => __( 'Position on checkout page', 'fluid-checkout' ),
					'desc'            => __( 'Choose where to display the available coupons section on the checkout page.', 'fluid-checkout' ),
					'id'              => 'fc_integration_woocommerce_smart_coupons_position_checkout',
					'type'            => 'select',
					'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_smart_coupons_position_checkout' ),
					'autoload'        => false,
					'options'         => array(
						'before_progress_bar'   => __( 'Before the progress bar', 'fluid-checkout' ),
						'after_progress_bar'    => __( 'After the progress bar', 'fluid-checkout' ),
						'before_steps'          => __( 'Before the checkout steps', 'fluid-checkout' ),
						'before_coupon_code'    => __( 'Before coupon codes section', 'fluid-checkout' ),
					),
				),
			),
		) );
			
		$settings_new[] = array(
			'type' => 'sectionend',
			'id'    => 'fc_integrations_woocommerce_smart_coupons_options',
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Get position to display available coupons on the checkout page.
	 *
	 * @return  string  Position to display available coupons on the checkout page.
	 */
	public function get_position_to_display_available_coupons_on_checkout() {
		// Get option
		$position = FluidCheckout_Settings::instance()->get_option( 'fc_integration_woocommerce_smart_coupons_position_checkout' );

		// Define position hook mapping
		$position_hook_map = array(
			'before_progress_bar'   => array( 'hook' => 'woocommerce_before_checkout_form', 'priority' => 3 ),
			'after_progress_bar'    => array( 'hook' => 'fc_checkout_before', 'priority' => 3 ),
			'before_steps'          => array( 'hook' => 'fc_checkout_before_steps', 'priority' => 5 ),
			'before_coupon_code'    => array( 'hook' => 'fc_before_substep_coupon_codes', 'priority' => 5 ),
		);

		// Maybe set default position
		if ( ! in_array( $position, array_keys( $position_hook_map ) ) ) {
			$position = FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_smart_coupons_position_checkout' );
		}

		// Get hook
		$position_hook = $position_hook_map[ $position ];

		// Return position
		return $position_hook;
	}

}

FluidCheckout_WooCommerceSmartCoupons::instance();

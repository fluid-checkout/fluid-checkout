<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Advanced Coupons for WooCommerce Free (by Rymera Web Co).
 */
class FluidCheckout_AdvancedCouponsForWooCommerceFree extends FluidCheckout {

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
		$class_name = 'ACFWF\Models\Checkout';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class object is not found
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		if ( ! $class_object ) { return; }

		// Add section position hooks
		$position_hook = $this->get_position_to_display_available_coupons_on_checkout();

		// Remove and add position hook for Advanced Coupons
		remove_action( 'woocommerce_checkout_order_review', array( $class_object, 'display_checkout_tabbed_box' ), 11 );
		add_action( $position_hook['hook'], array( $class_object, 'display_checkout_tabbed_box' ), $position_hook['priority'] );

		// Prevent hiding store credits behind a link button
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_store_credits' ), 10 );

		// Prevent Advanced Coupons from using the checkout block
		add_filter( 'acfw_filter_is_current_page_using_cart_checkout_block', '__return_false', 10 );
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
				'title' => __( 'Advanced Coupons for WooCommerce Free', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_advanced_coupons_for_woocommerce_free_options',
			),
			array(
				'title'   => __( 'Position on checkout page', 'fluid-checkout' ),
				'desc'    => __( 'Choose where to display the available coupons section on the checkout page.', 'fluid-checkout' ),
				'id'      => 'fc_integration_advanced_coupons_for_woocommerce_free_position',
				'type'    => 'select',
				'default' => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_advanced_coupons_for_woocommerce_free_position' ),
				'autoload' => false,
				'options' => array(
					'before_steps'          => __( 'Before the checkout steps', 'fluid-checkout' ),
					'before_coupon_code'    => __( 'Before coupon codes section', 'fluid-checkout' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'fc_integrations_advanced_coupons_for_woocommerce_free_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}

	/**
	 * Get the hook configuration for the selected position on checkout.
	 *
	 * @return  array|null  Hook details for the selected position.
	 */
	public function get_position_to_display_available_coupons_on_checkout() {
		// Get option
		$position = FluidCheckout_Settings::instance()->get_option( 'fc_integration_advanced_coupons_for_woocommerce_free_position' );

		// Define position hook mapping
		$position_hook_map = apply_filters( 'fc_integration_advanced_coupons_for_woocommerce_free_position_hook_map', array(
			'before_steps'          => array( 'hook' => 'fc_checkout_before_steps', 'priority' => 5 ),
			'before_coupon_code'    => array( 'hook' => 'fc_before_substep_coupon_codes', 'priority' => 5 ),
		) );

		// Maybe set default position
		if ( ! in_array( $position, array_keys( $position_hook_map ) ) ) {
			$position = FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_smart_coupons_position_checkout' );
		}

		// Get hook
		$position_hook = $position_hook_map[ $position ];

		// Return position
		return $position_hook;
	}



	/**
	 * Prevent hiding optional field for the store credits behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_store_credits( $skip_list ) {
		$skip_list[] = 'acfw_redeem_store_credit';
		return $skip_list;
	}



	/**
	 * Remove section position hooks.
	 */
	public function section_position_hooks() {
		// Bail if class is not available
		$class_name = 'ACFWF\Models\Checkout';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class object is not found
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		if ( ! $class_object ) { return; }

		remove_action( 'woocommerce_checkout_order_review', array( $class_object, 'display_checkout_tabbed_box' ), 11 );

		$position_hook = $this->get_position_to_display_available_coupons_on_checkout();
		if ( empty( $position_hook['hook'] ) ) { return; }

		$hook     = $position_hook['hook'];
		$priority = isset( $position_hook['priority'] ) ? (int) $position_hook['priority'] : 10;

		add_action( $hook, array( $class_object, 'display_checkout_tabbed_box' ), $priority );
	}
}

FluidCheckout_AdvancedCouponsForWooCommerceFree::instance();
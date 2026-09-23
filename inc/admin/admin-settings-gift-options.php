<?php
/**
 * Fluid Checkout Gift Options Settings
 *
 * @package fluid-checkout
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_GiftOptions_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_GiftOptions_Settings();
}

/**
 * WC_Settings_FluidCheckout_GiftOptions_Settings.
 */
class WC_Settings_FluidCheckout_GiftOptions_Settings extends WC_Settings_Page {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->id = 'fc_checkout';
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Sections
		add_filter( 'woocommerce_get_sections_fc_checkout', array( $this, 'add_sections' ), 10 );

		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public function add_sections( $sections ) {
		$sections = array_merge( $sections, array(
			'gift_options' => __( 'Gift Options', 'fluid-checkout' ),
		) );

		return $sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'gift_options' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_gift_options_settings',
			array(
				array(
					'title' => __( 'Gift Options', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_gift_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'gift-options' ),
				),

				array(
					'title'                 => __( 'Gift options', 'fluid-checkout' ),
					'desc'                  => __( 'Display gift message and other gift options at the checkout page', 'fluid-checkout' ),
					'desc_tip'              => __( 'Allow customers to add a gift message and other gift related options to the order.', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_gift_options',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_gift_options' ),
					'checkboxgroup'         => 'start',
					'show_if_checked'       => 'option',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Display the gift message fields always expanded', 'fluid-checkout' ),
					'id'                    => 'fc_default_gift_options_expanded',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_default_gift_options_expanded' ),
					'checkboxgroup'         => '',
					'show_if_checked'       => 'yes',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Display the gift message as part of the order details table instead of a separate section', 'fluid-checkout' ),
					'desc_tip'              => __( 'This option affects the order confirmation page (thank you page) and order details on account pages, emails and packing slips.', 'fluid-checkout' ),
					'id'                    => 'fc_display_gift_message_in_order_details',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_display_gift_message_in_order_details' ),
					'checkboxgroup'         => 'end',
					'show_if_checked'       => 'yes',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_gift_options',
				),
			)
		);
	}

}

return new WC_Settings_FluidCheckout_GiftOptions_Settings();

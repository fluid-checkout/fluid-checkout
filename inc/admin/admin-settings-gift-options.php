<?php
/**
 * Fluid Checkout Gift Options Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_GiftOptions', false ) ) {
	FluidCheckout_Settings_GiftOptions::hooks();
	return;
}

/**
 * FluidCheckout_Settings_GiftOptions.
 */
class FluidCheckout_Settings_GiftOptions {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
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
	public static function add_settings( $settings, $current_section ) {
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
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_enable_checkout_gift_options',
						'data-conditional-value' => 'yes',
					),
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
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_enable_checkout_gift_options',
						'data-conditional-value' => 'yes',
					),
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

FluidCheckout_Settings_GiftOptions::hooks();

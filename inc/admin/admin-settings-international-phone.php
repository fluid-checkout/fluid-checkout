<?php
/**
 * Fluid Checkout Phone Fields Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_InternationalPhone', false ) ) {
	FluidCheckout_Settings_InternationalPhone::hooks();
	return;
}

/**
 * FluidCheckout_Settings_InternationalPhone.
 */
class FluidCheckout_Settings_InternationalPhone {

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
			'international_phone' => __( 'Phone fields', 'fluid-checkout' ),
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
		// Bail if not the phone fields section
		if ( 'international_phone' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_international_phone_settings',
			array(

				array(
					'title' => __( 'Shipping phone', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_shipping_phone_fields_options',
				),

				array(
					'title'                 => __( 'Field visibility', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Add shipping phone field to the checkout form.', 'fluid-checkout' ) . '<br>' . __( 'The shipping phone field may be forced as "required" if the billing address section is displayed after the shipping address section, and the billing phone field is set as "required". This is needed to ensure the shipping address can be copied to the billing address when that option is checked, otherwise the customer might not be able to complete the checkout form.', 'fluid-checkout' ),
					'id'                    => 'fc_shipping_phone_field_visibility',
					'type'                  => 'select',
					'options'               => array(
						'hidden'   => __( 'Hidden (remove field)', 'fluid-checkout' ),
						'optional' => __( 'Optional', 'fluid-checkout' ),
						'required' => __( 'Required', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_phone_field_visibility' ),
					'autoload'              => false,
				),

				array(
					'title'                 => __( 'Field position', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Choose in which step to display the shipping phone field.', 'fluid-checkout' ),
					'id'                    => 'fc_shipping_phone_field_position',
					'type'                  => 'select',
					'options'               => array(
						'shipping_address' => __( 'Shipping address', 'fluid-checkout' ),
						'contact'          => __( 'Contact step', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_phone_field_position' ),
					'autoload'              => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_shipping_phone_fields_options',
				),



				array(
					'title' => __( 'Billing phone', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_billing_phone_fields_options',
				),

				array(
					'title'                 => __( 'Field visibility', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Add billing phone field to the checkout form.', 'fluid-checkout' ) . '<br>' . __( 'The billing phone field may be forced as "required" if the billing address section is displayed before the shipping address section, and the shipping phone field is set as "required". This is needed to ensure the shipping address can be copied to the billing address when that option is checked, otherwise the customer might not be able to complete the checkout form.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_checkout_phone_field',
					'type'                  => 'select',
					'options'               => array(
						'hidden'   => __( 'Hidden (remove field)', 'fluid-checkout' ),
						'optional' => __( 'Optional', 'fluid-checkout' ),
						'required' => __( 'Required', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_checkout_phone_field' ),
					'autoload'              => false,
				),

				array(
					'title'                 => __( 'Field position', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Choose in which step to display the billing phone field.', 'fluid-checkout' ),
					'id'                    => 'fc_billing_phone_field_position',
					'type'                  => 'select',
					'options'               => array(
						'billing_address' => __( 'Billing address', 'fluid-checkout' ),
						'contact'         => __( 'Contact step', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_billing_phone_field_position' ),
					'autoload'              => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_billing_phone_fields_options',
				),



				array(
					'title' => __( 'Phone country code & validation', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_international_phone_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'international-phone' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-international-phone-numbers/' ),
				),

				array(
					'title'                 => __( 'Country', 'fluid-checkout' ),
					'desc'                  => __( 'Enable country on phone fields', 'fluid-checkout' ),
					'desc_tip'              => __( 'Format phone numbers according to the rules for each country.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_fields',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_fields' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Show country code beside the flag', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_country_code',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_country_code' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Filter countries for shipping or billing', 'fluid-checkout' ),
					'desc_tip'              => __( 'Limit the country selector on each phone field to the countries allowed for shipping or billing on your store.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_country_list_filter',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_country_list_filter' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Validation', 'fluid-checkout' ),
					'desc'                  => __( 'Enable simple validation based on country rules', 'fluid-checkout' ),
					'desc_tip'              => __( 'Checks that the phone number is valid for the selected country, including country code and length.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_validation',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Use precise phone number validation', 'fluid-checkout' ),
					'desc_tip'              => __( 'Stricter check that the number is a valid mobile, fixed line, or other accepted type for the selected country. May reject some valid numbers.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://intl-tel-input.com/examples/validation.html' ),
					'id'                    => 'fc_pro_enable_international_phone_validation_precise',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation_precise' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => '',
					'desc_tip'              => __( 'Phone number validation types used when precise validation is enabled.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_validation_precise_types',
					'type'                  => 'fc_multiselect',
					'class'                 => 'fc-enhanced-select',
					'options'               => array(
						'MOBILE'           => __( 'Mobile', 'fluid-checkout' ),
						'FIXED_LINE'       => __( 'Fixed line', 'fluid-checkout' ),
						'TOLL_FREE'        => __( 'Toll free', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation_precise_types' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Placeholders', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Show an example of a valid phone number inside phone fields', 'fluid-checkout' ),
					'id'                    => 'fc_pro_international_phone_fields_placeholder',
					'type'                  => 'fc_select',
					'options'               => array(
						'OFF'              => __( 'Do not change placeholders', 'fluid-checkout' ),
						'POLITE'           => __( 'Show if not defined', 'fluid-checkout' ),
						'AGGRESSIVE'       => __( 'Always show', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_international_phone_fields_placeholder' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_international_phone_options',
				),

			)
		);
	}

}

FluidCheckout_Settings_InternationalPhone::hooks();

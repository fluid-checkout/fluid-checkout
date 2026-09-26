<?php
/**
 * Fluid Checkout International Phone Numbers Settings
 *
 * @package fluid-checkout
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_InternationalPhone_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_InternationalPhone_Settings();
}

/**
 * WC_Settings_FluidCheckout_InternationalPhone_Settings.
 */
class WC_Settings_FluidCheckout_InternationalPhone_Settings extends WC_Settings_Page {

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
			'international_phone' => __( 'International Phone Numbers', 'fluid-checkout' ),
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
		if ( 'international_phone' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_international_phone_settings',
			array(
				array(
					'title' => __( 'International Phone Numbers', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_international_phone_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'international-phone' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-international-phone-numbers/' ),
				),

				array(
					'title'                 => __( 'International phone numbers', 'fluid-checkout' ),
					'desc'                  => __( 'Enable international phone number fields', 'fluid-checkout' ),
					'desc_tip'              => __( 'Format phone numbers according to the rules for each country.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_fields',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_fields' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'desc'                  => __( 'Enable phone number validation based on country rules', 'fluid-checkout' ),
					'desc_tip'              => __( 'When disabled, the phone field validation will not check if country or area codes are valid for the country.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_validation',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Use precise phone number validation', 'fluid-checkout' ),
					'desc_tip'              => __( 'Try to ensure the phone number is a valid mobile or landline number based on the rules for the selected country code. This option uses the <code>intl-tel-input</code> precise validation feature, which may give false positives for some phone numbers.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://intl-tel-input.com/examples/validation.html' ),
					'id'                    => 'fc_pro_enable_international_phone_validation_precise',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation_precise' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => '',
					'desc_tip'          => __( 'Phone number validation types used when precise validation is enabled.', 'fluid-checkout' ),
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
					'desc'                  => __( 'Only show allowed countries for shipping or billing', 'fluid-checkout' ),
					'desc_tip'              => __( 'When enabled, only the countries allowed for shipping will be available in the shipping phone field, and only countries allowed for billing will be available for the billing phone field.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_international_phone_country_list_filter',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_country_list_filter' ),
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
					'desc'                  => '',
					'desc_tip'          => __( 'Show an example of a valid phone number inside phone fields', 'fluid-checkout' ),
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

return new WC_Settings_FluidCheckout_InternationalPhone_Settings();

<?php
/**
 * Fluid Checkout VAT Assistant Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_VATAssistant', false ) ) {
	FluidCheckout_Settings_VATAssistant::hooks();
	return;
}

/**
 * FluidCheckout_Settings_VATAssistant.
 * Settings are locked until Fluid Checkout PRO unlocks the feature `pro`.
 */
class FluidCheckout_Settings_VATAssistant {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'pro';



	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Get the tax class options for the digital goods setting.
	 */
	public static function get_tax_classes_options() {
		// Define standard tax classes
		$tax_classes_options = array(
			'standard' => __( 'Standard', 'fluid-checkout' ),
		);

		// Bail if WooCommerce tax classes are not available
		if ( ! class_exists( 'WC_Tax' ) ) { return $tax_classes_options; }

		// Add additional tax classes
		foreach ( WC_Tax::get_tax_classes() as $class ) {
			$tax_classes_options[ sanitize_title( $class ) ] = esc_html( $class );
		}

		return $tax_classes_options;
	}

	/**
	 * Get the shop VAT country name, from the VAT Assistant add-on when available, or the WooCommerce store country.
	 */
	public static function get_shop_vat_country_name() {
		// Maybe get country name from PRO EU-VAT feature
		if ( class_exists( 'FluidCheckout_PRO_Checkout_EU_VAT' ) ) {
			return FluidCheckout_PRO_Checkout_EU_VAT::instance()->get_shop_vat_country_name();
		}

		// Bail if WooCommerce is not available
		if ( ! function_exists( 'WC' ) ) { return ''; }

		$countries = WC()->countries->get_countries();
		$base_country = WC()->countries->get_base_country();

		return array_key_exists( $base_country, $countries ) ? $countries[ $base_country ] : $base_country;
	}

	/**
	 * Get the placeholder country options for the reverse charge exceptions setting.
	 *
	 * Lite lists only the store base country. PRO replaces this field's `options`
	 * through the `fc_vat_vat_assistant_settings` filter.
	 *
	 * @since 5.0.0
	 *
	 * @return  array  Country code => country name. Only the store base country.
	 */
	public static function get_reverse_charge_countries_skip_list_options() {
		// Bail if WooCommerce is not available
		if ( ! function_exists( 'WC' ) ) { return array(); }

		$countries = WC()->countries->get_countries();
		$base_country = WC()->countries->get_base_country();

		// Bail if the store has no base country
		if ( empty( $base_country ) ) { return array(); }

		return array(
			$base_country => array_key_exists( $base_country, $countries ) ? $countries[ $base_country ] : $base_country,
		);
	}

	/**
	 * Add the VAT Assistant settings.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_settings( $settings, $current_section ) {
		// Bail if not on the VAT Assistant section
		if ( 'vat_assistant' !== $current_section ) { return $settings; }

		/**
		 * Filters the settings for one VAT Assistant section.
		 *
		 * The dynamic portion of the hook name, `$current_section`, refers to the settings section slug.
		 * Possible hook names include:
		 *
		 * - `fc_vat_vat_assistant_settings`
		 *
		 * @since 5.0.0
		 */
		$settings = apply_filters(
			'fc_vat_' . $current_section . '_settings',
			array(
				array(
					'title'             => __( 'VAT number field', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => '',
					'id'                => 'fc_vat_number',
					'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'vat-assistant' ),
				),

				array(
					'title'             => __( 'Field visibility', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Add a VAT number field to the billing form.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_field_visibility',
					'type'              => 'select',
					'options'           => array(
						'no'            => _x( 'Hidden', 'VAT number field visibility', 'fluid-checkout' ),
						'optional'      => _x( 'Optional', 'VAT number field visibility', 'fluid-checkout' ),
						'required'      => _x( 'Required', 'VAT number field visibility', 'fluid-checkout' ),
					),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_field_visibility' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Field label', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Set the label of the VAT number field (ie. VAT number). On multi-language websites, it is better to leave this field empty and translate the original string.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_field_label',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_field_label' ),
					'placeholder'       => __( 'VAT Number', 'fluid-checkout' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_field_visibility',
						'data-conditional-value' => 'optional,required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_vat_number',
				),

				array(
					'title'             => __( 'EU-VAT', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => '',
					'id'                => 'fc_vat_eu_vat_options',
					'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'vat-assistant-eu-vat' ),
					'docs'              => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/fc-vat-assistant/' ),
				),

				array(
					'type'              => 'fc_paragraph',
					'desc'              => __( 'Enable the VAT number field to see more options', 'fluid-checkout' ),
					'id'                => 'fc_vat_eu_vat_enable_notice',
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_field_visibility',
						'data-conditional-value' => 'no',
					),
					'requires'          => self::FEATURE,
					'unlocked_only'     => true,
				),

				array(
					'title'             => __( 'VAT validation', 'fluid-checkout' ),
					'desc'              => __( 'Validate VAT number field for EU-VAT during checkout', 'fluid-checkout' ),
					'desc_tip'          => __( 'Checks if the VAT Number provided is a valid VAT number registered on the European VIES Database (VAT Information Exchange System).', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_validation',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_validation' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_field_visibility',
						'data-conditional-value' => 'optional,required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Shop VAT number', 'fluid-checkout' ),
					'desc'              => __( 'The shop\'s VAT number used for requesting EU-VAT validation, and printed on invoices. <br><strong>We strongly recommend adding your Shop VAT number</strong> as otherwise the VIES consultation results might not be valid for accounting purposes. <br>Ie.: <code>ATU99999999</code> or <code>EE999999999</code>.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_shop',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_shop' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_validation,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse charge', 'fluid-checkout' ),
					'desc'              => __( 'Apply reverse charge mechanism', 'fluid-checkout' ),
					'desc_tip'          => __( 'Set tax rate to zero and display "Reverse charge" on invoices when the customer\'s VAT number is valid. A different "Reverse charge" text might be required depending on the store\'s country.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_validation,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Same country', 'fluid-checkout' ),
					'desc'              => __( 'Apply reverse charge mechanism for transactions in the shop country:', 'fluid-checkout' ) . ' <strong>' . esc_html( self::get_shop_vat_country_name() ) . '</strong>',
					'desc_tip'          => __( 'Enables applying reverse charge mechanism for transactions between companies in the same country as the shop.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_same_country',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_same_country' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_reverse_charge,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse charge exceptions', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Select additional countries to skip applying the reverse charge mechanism.', 'fluid-checkout' ) . ' <br>' . __( 'Taxes will be charged to orders from these countries even when a valid VAT number is provided.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_countries_skip_list',
					'type'              => 'multi_select_countries',
					'options'           => self::get_reverse_charge_countries_skip_list_options(),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_countries_skip_list' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_reverse_charge,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse charge label on invoices', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Set the label of "reverse charge" on the checkout page and order details.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_label',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_label' ),
					'placeholder'       => __( 'Reverse charge', 'fluid-checkout' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_reverse_charge,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse charge text on invoices', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Set the label of "reverse charge" text on invoices.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_label_invoice',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_label_invoice' ),
					'placeholder'       => __( 'Tax to be paid on reverse charge basis', 'fluid-checkout' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_reverse_charge,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Company name', 'fluid-checkout' ),
					'desc'              => __( 'Autocomplete company name from VAT number', 'fluid-checkout' ),
					'desc_tip'          => __( 'Fill in the billing company name based on the valid VAT number provided by the customer.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_autocomplete_billing_company_name',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_autocomplete_billing_company_name' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_eu_vat_validation,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),
				array(
					'title'             => __( 'Company name editing', 'fluid-checkout' ),
					'desc'              => __( 'Allow editing of auto-filled company name', 'fluid-checkout' ),
					'desc_tip'          => __( 'When enabled, customers can change the company name associated with the provided VAT number.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_autocomplete_billing_company_name_editing',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_autocomplete_billing_company_name_editing' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_vat_number_autocomplete_billing_company_name,fc_vat_number_field_visibility',
						'data-conditional-value' => 'yes,optional|required',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_vat_eu_vat_options',
				),

				array(
					'title'             => __( 'EU-VAT for digital goods', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => __( 'From January 1st, 2015, modifications have been made to the EU VAT regulations concerning digital goods, impacting exclusively B2C transactions. The VAT on digital goods must be calculated based on the customer\'s location, and evidence of this needs to be collected (IP address and billing address).', 'fluid-checkout' ),
					'id'                => 'fc_vat_eu_vat_digital_goods_options',
					'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'vat-assistant-digital-goods' ),
				),

				array(
					'title'             => __( 'Tax classes for digital goods', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_digital_goods_tax_classes',
					'desc_tip'          => true,
					'type'              => 'multiselect',
					'class'             => 'fc-enhanced-select',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_digital_goods_tax_classes' ),
					'options'           => self::get_tax_classes_options(),
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select some tax classes', 'fluid-checkout' ),
					),
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Location evidence', 'fluid-checkout' ),
					'desc'              => __( 'Collect customer\'s location evidence', 'fluid-checkout' ),
					'desc_tip'          => __( 'Saves the IP address and validate it against the customer\'s billing address. Customer\'s must confirm their billing address by checking a checkbox on the checkout page if the country from their billing address and IP do not match.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_country_confirmation',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_country_confirmation' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_vat_eu_vat_digital_goods_options',
				),

				array(
					'title'             => __( 'Address book integration', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => '',
					'id'                => 'fc_vat_address_book_integration',
					'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'vat-assistant-address-book-integration' ),
				),

				array(
					'title'             => __( 'VAT number', 'fluid-checkout' ),
					'desc'              => '',
					'desc_tip'          => __( 'Define limit of VAT Numbers a customer can save to their account.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_per_account_limit',
					'type'              => 'select',
					'options'           => array(
						'one_per_account'       => __( 'One per account', 'fluid-checkout' ),
						'one_per_address_entry' => __( 'One per address entry', 'fluid-checkout' ),
					),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_per_account_limit' ),
					'autoload'          => false,
					'disabled'          => true,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_vat_address_book_integration',
				),
			)
		);

		return $settings;
	}

}

FluidCheckout_Settings_VATAssistant::hooks();

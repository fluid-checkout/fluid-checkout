<?php
/**
 * Fluid Checkout VAT Assistant Settings
 *
 * @package fluid-checkout
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_VATAssistant_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_VATAssistant_Settings();
}

/**
 * WC_Settings_FluidCheckout_VATAssistant_Settings.
 * Settings are locked until the VAT Assistant add-on unlocks the feature `vat_assistant`.
 */
class WC_Settings_FluidCheckout_VATAssistant_Settings extends WC_Settings_Page {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'vat_assistant';



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
		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 10, 2 );
	}



	/**
	 * Get the tax class options for the digital goods setting.
	 */
	public function get_tax_classes_options() {
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
	public function get_shop_vat_country_name() {
		// Maybe get country name from the VAT Assistant add-on
		if ( class_exists( 'FC_VAT_Assistant_Checkout_EU_VAT' ) ) {
			return FC_VAT_Assistant_Checkout_EU_VAT::instance()->get_shop_vat_country_name();
		}

		// Bail if WooCommerce is not available
		if ( ! function_exists( 'WC' ) ) { return ''; }

		$countries = WC()->countries->get_countries();
		$base_country = WC()->countries->get_base_country();

		return array_key_exists( $base_country, $countries ) ? $countries[ $base_country ] : $base_country;
	}

	/**
	 * Get the countries options for the reverse charge exceptions setting.
	 */
	public function get_reverse_charge_countries_skip_list_options() {
		$countries = function_exists( 'WC' ) ? WC()->countries->countries : array();
		return apply_filters( 'fc_vat_reverse_charge_countries_skip_list_options', $countries );
	}



	/**
	 * Add the VAT Assistant settings.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		// Bail if not on the VAT Assistant section
		if ( 'vat_number' !== $current_section ) { return $settings; }

		$settings = apply_filters(
			'fc_vat_' . $current_section . '_settings',
			array(
				array(
					'title'             => __( 'VAT Assistant', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => '',
					'id'                => 'fc_vat_number',
				),

				array(
					'desc'              => FluidCheckout_Admin::instance()->get_addon_locked_notice_html( __( 'VAT Assistant', 'fluid-checkout' ), 'https://fluidcheckout.com/fc-eu-vat-assistant/?mtm_campaign=addons&mtm_kwd=fc-vat-settings&mtm_source=lite-plugin' ),
					'id'                => 'fc_vat_number_locked_notice',
					'type'              => 'fc_paragraph',
					'requires'          => self::FEATURE,
					'locked_only'       => true,
				),

				array(
					'title'             => __( 'VAT Number', 'fluid-checkout' ),
					'desc'              => __( 'Add a VAT Number field to the billing form.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_field_visibility',
					'type'              => 'select',
					'options'           => array(
						'no'            => _x( 'Hidden', 'VAT Number field visibility', 'fluid-checkout' ),
						'optional'      => _x( 'Optional', 'VAT Number field visibility', 'fluid-checkout' ),
						'required'      => _x( 'Required', 'VAT Number field visibility', 'fluid-checkout' ),
					),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_field_visibility' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'VAT Number Label', 'fluid-checkout' ),
					'desc'              => __( 'Set the label of the VAT Number field (ie. VAT Number). On multi-language websites, it is better to leave this field empty and translate the original string.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_field_label',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_field_label' ),
					'placeholder'       => __( 'VAT Number', 'fluid-checkout' ),
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
				),

				array(
					'title'             => __( 'Shop VAT Number', 'fluid-checkout' ),
					'desc'              => __( 'The shop\'s VAT number used for requesting EU-VAT validation, and printed on invoices. <br><strong>We strongly recommend adding your Shop VAT number</strong> as otherwise the VIES consultation results might not be valid for accounting purposes. <br>Ie.: <code>ATU99999999</code> or <code>EE999999999</code>.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_shop',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_shop' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'VAT Validation', 'fluid-checkout' ),
					'desc'              => __( 'Validate VAT Number field for EU-VAT during checkout', 'fluid-checkout' ),
					'desc_tip'          => __( 'Checks if the VAT Number provided is a valid VAT number registered on the European VIES Database (VAT Information Exchange System).', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_validation',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_validation' ),
					'checkboxgroup'     => 'start',
					'show_if_checked'   => 'option',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'desc'              => __( 'Apply reverse charge mechanism', 'fluid-checkout' ),
					'desc_tip'          => __( 'Set tax rate to zero and display "Reverse charge" on invoices when the customer\'s VAT number is valid. A different "Reverse charge" text might be required depending on the store\'s country.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge' ),
					'checkboxgroup'     => '',
					'show_if_checked'   => 'yes',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'desc'              => __( 'Apply reverse charge mechanism for transactions in the shop country:', 'fluid-checkout' ) . ' <strong>' . esc_html( $this->get_shop_vat_country_name() ) . '</strong>',
					'desc_tip'          => __( 'Enables applying reverse charge mechanism for transactions between companies in the same country as the shop.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_same_country',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_same_country' ),
					'checkboxgroup'     => 'end',
					'show_if_checked'   => 'yes',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'desc'              => '<strong>' . __( 'Reverse Charge Exceptions', 'fluid-checkout' ) . ': </strong><br>' . __( 'Select additional countries to skip applying the reverse charge mechanism.', 'fluid-checkout' ) . ' <br>' . __( 'Taxes will be charged to orders from these countries even when a valid VAT number is provided.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_countries_skip_list',
					'type'              => 'multi_select_countries',
					'options'           => $this->get_reverse_charge_countries_skip_list_options(),
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_countries_skip_list' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Company Name', 'fluid-checkout' ),
					'desc'              => __( 'Autocomplete company name from VAT number', 'fluid-checkout' ),
					'desc_tip'          => __( 'Fill in the billing company name based on the valid VAT number provided by the customer.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_autocomplete_billing_company_name',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_autocomplete_billing_company_name' ),
					'checkboxgroup'     => 'start',
					'show_if_checked'   => 'option',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),
				array(
					'desc'              => __( 'Allow editing of auto-filled company name', 'fluid-checkout' ),
					'desc_tip'          => __( 'When enabled, customers can change the company name associated with the provided VAT number.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_autocomplete_billing_company_name_editing',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_autocomplete_billing_company_name_editing' ),
					'checkboxgroup'     => 'end',
					'show_if_checked'   => 'yes',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse Charge Label on Invoices', 'fluid-checkout' ),
					'desc'              => __( 'Set the label of "reverse charge" on the checkout page and order details.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_label',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_label' ),
					'placeholder'       => __( 'Reverse charge', 'fluid-checkout' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Reverse Charge Text on Invoices', 'fluid-checkout' ),
					'desc'              => __( 'Set the label of "reverse charge" text on invoices.', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_reverse_charge_label_invoice',
					'type'              => 'text',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_reverse_charge_label_invoice' ),
					'placeholder'       => __( 'Tax to be paid on reverse charge basis', 'fluid-checkout' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_vat_eu_vat_options',
				),

				array(
					'title'             => __( 'EU-VAT for Digital Goods', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => __( 'From January 1st, 2015, modifications have been made to the EU VAT regulations concerning digital goods, impacting exclusively B2C transactions. The VAT on digital goods must be calculated based on the customer\'s location, and evidence of this needs to be collected (IP address and billing address).', 'fluid-checkout' ),
					'id'                => 'fc_vat_eu_vat_digital_goods_options',
				),

				array(
					'title'             => __( 'Tax classes for digital goods', 'fluid-checkout' ),
					'id'                => 'fc_vat_number_eu_vat_digital_goods_tax_classes',
					'desc_tip'          => true,
					'type'              => 'multiselect',
					'class'             => 'chosen_select wp-enhanced-select',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_vat_number_eu_vat_digital_goods_tax_classes' ),
					'options'           => $this->get_tax_classes_options(),
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select some tax classes', 'fluid-checkout' ),
					),
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Location Evidence', 'fluid-checkout' ),
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
			)
		);

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_VATAssistant_Settings();

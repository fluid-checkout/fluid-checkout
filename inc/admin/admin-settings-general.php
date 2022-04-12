<?php
/**
 * Fluid Checkout General Settings
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_General_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_General_Settings();
}

/**
 * WC_Settings_FluidCheckout_General_Settings.
 */
class WC_Settings_FluidCheckout_General_Settings extends WC_Settings_Page {

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
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( empty( $current_section ) ) {

			$settings = apply_filters(
				'fc_checkout_general_settings',
				array(
					array(
						'title' => __( 'Layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_layout_options',
					),

					array(
						'title'             => __( 'Checkout Layout', 'fluid-checkout' ),
						'id'                => 'fc_checkout_layout',
						'type'              => 'fc_layout_selector',
						'options'           => FluidCheckout_Steps::instance()->get_allowed_checkout_layouts(),
						'default'           => 'multi-step',
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-layout',
						'class'             => 'fc-checkout-layout__option',
					),

					array(
						'title'             => __( 'Logo image', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose an image to be displayed on the checkout page header. Only applies when using the plugin\'s checkout header.', 'fluid-checkout' ),
						'id'                => 'fc_checkout_logo_image',
						'type'              => 'fc_image_uploader',
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-logo-image',
					),

					array(
						'title'             => __( 'Header background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page header. Only applies when using the plugin\'s checkout header.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_header_background_color',
						'type'              => 'text',
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'title'             => __( 'Page background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page. Color is applied to the <em>body</em> element.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_page_background_color',
						'type'              => 'text',
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_layout_options',
					),

					array(
						'title' => __( 'Features', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_features_options',
					),

					array(
						'title'             => __( 'Optional fields', 'fluid-checkout' ),
						'desc'              => __( 'Hide optional fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'It is recommended to keep this options checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_hide_optional_fields',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the "Add" link buttons in lowercase', 'fluid-checkout' ),
						'desc_tip'          => __( 'Make the labels of optional field "Add" link button as <code>lowercase</code>. (ie. "Add phone number" instead of "Add Phone number")', 'fluid-checkout' ),
						'id'                => 'fc_optional_fields_link_label_lowercase',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Do not hide "Address line 2" fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'Recommended only when most customers actually need the "Address line 2" field, or when getting the right shipping address is crucial (ie. if delivering food and other perishable products).', 'fluid-checkout' ),
						'id'                => 'fc_hide_optional_fields_skip_address_2',
						'default'           => 'no',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Billing Address', 'fluid-checkout' ),
						'desc'              => __( 'Billing address same as the shipping address checked by default', 'fluid-checkout' ),
						'desc_tip'          => __( 'It is recommended to leave this option checked. The billing address at checkout will start with the option "Billing same as shipping" checked by default. This will reduce significantly the number of open input fields at the checkout, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#3-default-billing--shipping-and-hide-the-fields-entirely" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_default_to_billing_same_as_shipping',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping phone', 'fluid-checkout' ),
						'desc'              => __( 'Add a phone field to the shipping address form', 'fluid-checkout' ),
						'id'                => 'fc_shipping_phone_field_visibility',
						'options'           => array(
							'no'            => _x( 'Hidden', 'Shipping phone field visibility', 'fluid-checkout' ),
							'optional'      => _x( 'Optional', 'Shipping phone field visibility', 'fluid-checkout' ),
							'required'      => _x( 'Required', 'Shipping phone field visibility', 'fluid-checkout' ),
						),
						'default'           => 'no',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Choose in which step to display the shipping phone', 'fluid-checkout' ),
						'id'                => 'fc_shipping_phone_field_position',
						'options'           => array(
							'shipping_address' => _x( 'Shipping address', 'Shipping phone field position', 'fluid-checkout' ),
							'contact'          => _x( 'Contact', 'Shipping phone field position', 'fluid-checkout' ),
						),
						'default'           => 'shipping_address',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping methods', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the shipping methods', 'fluid-checkout' ),
						'id'                => 'fc_shipping_methods_substep_position',
						'options'           => array(
							'before_shipping_address' => _x( 'Before shipping address', 'Shipping methods substep position', 'fluid-checkout' ),
							'after_shipping_address'  => _x( 'After shipping address', 'Shipping methods substep position', 'fluid-checkout' ),
						),
						'default'           => 'after_shipping_address',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Local Pickup', 'fluid-checkout' ),
						'desc'              => __( 'Removes shipping address section when a Local Pickup shipping method is selected', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_local_pickup',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Integrated Coupon Codes', 'fluid-checkout' ),
						'desc'              => __( 'Show coupon codes as a substep of the payment step', 'fluid-checkout' ),
						'desc_tip'          => __( 'Only applicable if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_coupon_codes',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the coupon codes section title', 'fluid-checkout' ),
						'id'                => 'fc_display_coupon_code_section_title',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Order notes', 'fluid-checkout' ),
						'desc'              => __( 'Define the visibility of the additional order notes field.', 'fluid-checkout' ),
						'id'                => 'woocommerce_enable_order_comments',
						'options'           => array(
							'no'            => _x( 'Hidden', 'Order notes field visibility', 'fluid-checkout' ),
							'yes'           => _x( 'Optional', 'Order notes field visibility', 'fluid-checkout' ),
						),
						'default'           => 'yes',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Express checkout', 'fluid-checkout' ),
						'desc'              => __( 'Enable the express checkout section at checkout', 'fluid-checkout' ),
						'desc_tip'          => __( 'Displays the express checkout section at checkout when supported payment gateways have this feature enabled.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_express_checkout',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Gift options', 'fluid-checkout' ),
						'desc'              => __( 'Display gift message and other gift options at the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'Allow customers to add a gift message and other gift related options to the order.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_gift_options',
						'default'           => 'no',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the gift message fields always expanded', 'fluid-checkout' ),
						'id'                => 'fc_default_gift_options_expanded',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the gift message as part of the order details table instead of a separate section', 'fluid-checkout' ),
						'desc_tip'          => __( 'This option affects the order confirmation page (thank you page), order details at account pages, emails and packing slips.', 'fluid-checkout' ),
						'id'                => 'fc_display_gift_message_in_order_details',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Checkout Widget Areas', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols on the checkout page.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_widget_areas',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_features_options',
					),
				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_General_Settings();

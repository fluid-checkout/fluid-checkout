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
						'title' => __( 'Checkout Layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_layout_options',
					),

					array(
						'title'             => __( 'Layout Options', 'fluid-checkout' ),
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
						'title'             => __( 'Header and Footer', 'fluid-checkout' ),
						'desc'              => __( 'We recommend using the Fluid Checkout header and footer to avoid distractions at the checkout page. <a href="https://baymard.com/blog/cart-abandonment" target="_blank">Read the research about cart abandonment</a>.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Controls whether to use the Fluid Checkout page header and footer or keep the currently active theme\'s.', 'fluid-checkout' ),
						'id'                => 'fc_hide_site_header_footer_at_checkout',
						'type'              => 'select',
						'options'           => array(
							'yes'           => __( 'Use Fluid Checkout header and footer', 'fluid-checkout' ),
							'no'            => __( '(Experimental) Use theme\'s page header and footer for the checkout page', 'fluid-checkout' ),
						),
						'default'           => 'yes',
						'autoload'          => false,
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
						'title'             => __( 'Footer background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page footer. Only applies when using the plugin\'s checkout footer.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_footer_background_color',
						'type'              => 'text',
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'title'             => __( 'Progress bar', 'fluid-checkout' ),
						'desc'              => __( 'Display the checkout progress bar', 'fluid-checkout' ),
						'desc_tip'          => __( 'Applies only to multi-step layouts.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_progress_bar',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Make the checkout progress bar stay visible while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_sticky_progress_bar',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the order summary section.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_order_review_highlight_color',
						'type'              => 'text',
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'desc'              => __( 'Make the order summary stay visible while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_sticky_order_summary',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Place order', 'fluid-checkout' ),
						'desc'              => __( 'Define the position to display "Place order" and terms checkbox section.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Some options might not be compatible with some plugins and themes.', 'fluid-checkout' ),
						'id'                => 'fc_checkout_place_order_position',
						'options'           => array(
							'below_payment_section'             => __( 'Below the payment section', 'fluid-checkout' ),
							'below_order_summary'               => __( 'Below the order summary', 'fluid-checkout' ),
							'both_payment_and_order_summary'    => __( 'Both below the payment section and the order summary', 'fluid-checkout' ),
						),
						'default'           => 'below_payment_section',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping address', 'fluid-checkout' ),
						'desc'              => __( 'Highlight the shipping address section in the checkout form', 'fluid-checkout' ),
						'id'                => 'fc_show_shipping_section_highlighted',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Billing address', 'fluid-checkout' ),
						'desc'              => __( 'Highlight the billing address section in the checkout form', 'fluid-checkout' ),
						'id'                => 'fc_show_billing_section_highlighted',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
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
						'desc_tip'          => __( 'It is recommended to keep this option checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_hide_optional_fields',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the "Add" link buttons in lowercase', 'fluid-checkout' ),
						'desc_tip'          => __( 'Make the labels of optional field "Add" link button as <code>lowercase</code>. (ie. "Add phone number" instead of "Add Phone Number")', 'fluid-checkout' ),
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
						'title'             => __( 'Billing address', 'fluid-checkout' ),
						'desc'              => __( 'Billing address same as the shipping address checked by default', 'fluid-checkout' ),
						'desc_tip'          => __( 'It is recommended to leave this option checked. The billing address at checkout will start with the option "Billing same as shipping" checked by default. This will significantly reduce the number of open input fields at the checkout, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#3-default-billing--shipping-and-hide-the-fields-entirely" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_default_to_billing_same_as_shipping',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Billing phone', 'fluid-checkout' ),
						'desc'              => __( 'Add a phone field to the billing address form', 'fluid-checkout' ),
						'id'                => 'woocommerce_checkout_phone_field',
						'options'           => array(
							'hidden'        => __( 'Hidden', 'fluid-checkout' ),
							'optional'      => __( 'Optional', 'fluid-checkout' ),
							'required'      => __( 'Required', 'fluid-checkout' ),
						),
						'default'           => 'required',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Choose in which step to display the billing phone field', 'fluid-checkout' ),
						'id'                => 'fc_billing_phone_field_position',
						'options'           => array(
							'billing_address' => __( 'Billing address', 'fluid-checkout' ),
							'contact'          => __( 'Contact step', 'fluid-checkout' ),
						),
						'default'           => 'billing_address',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping phone', 'fluid-checkout' ),
						'desc'              => __( 'Add a phone field to the shipping address form', 'fluid-checkout' ),
						'id'                => 'fc_shipping_phone_field_visibility',
						'options'           => array(
							'no'            => __( 'Hidden', 'fluid-checkout' ),
							'optional'      => __( 'Optional', 'fluid-checkout' ),
							'required'      => __( 'Required', 'fluid-checkout' ),
						),
						'default'           => 'no',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Choose in which step to display the shipping phone field', 'fluid-checkout' ),
						'id'                => 'fc_shipping_phone_field_position',
						'options'           => array(
							'shipping_address' => __( 'Shipping address', 'fluid-checkout' ),
							'contact'          => __( 'Contact step', 'fluid-checkout' ),
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
							'before_shipping_address' => __( 'Before shipping address', 'fluid-checkout' ),
							'after_shipping_address'  => __( 'After shipping address', 'fluid-checkout' ),
						),
						'default'           => 'after_shipping_address',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'              => __( 'Enable integrated coupon code section', 'fluid-checkout' ),
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
						'desc_tip'          => __( 'Only applicable when coupon code is displayed as a separate section on the checkout or cart pages.', 'fluid-checkout-pro' ),
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
							'no'            => __( 'Hidden', 'fluid-checkout' ),
							'yes'           => __( 'Optional', 'fluid-checkout' ),
						),
						'default'           => 'yes',
						'type'              => 'select',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Checkout widget areas', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols on the checkout page.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_widget_areas',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Only display checkout sidebar widgets when at the last checkout step on mobile devices', 'fluid-checkout' ),
						'desc_tip'          => __( 'Applies only to multi-step layouts.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_widget_area_sidebar_last_step',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
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

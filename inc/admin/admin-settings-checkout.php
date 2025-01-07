<?php
/**
 * Fluid Checkout General Settings
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Checkout_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Checkout_Settings();
}

/**
 * WC_Settings_FluidCheckout_Checkout_Settings.
 */
class WC_Settings_FluidCheckout_Checkout_Settings extends WC_Settings_Page {

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
			'checkout' => __( 'Checkout', 'fluid-checkout' ),
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
		if ( 'checkout' === $current_section ) {

			$settings = apply_filters(
				'fc_checkout_general_settings',
				array(
					array(
						'title' => __( 'Layout &amp; design', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_layout_options',
					),

					array(
						'title'             => __( 'Checkout layout', 'fluid-checkout' ),
						'id'                => 'fc_checkout_layout',
						'type'              => 'fc_layout_selector',
						'options'           => FluidCheckout_Steps::instance()->get_checkout_layout_options(),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_layout' ),
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-layout',
						'class'             => 'fc-checkout-layout__option',
					),

					array(
						'title'             => __( 'Design template', 'fluid-checkout' ),
						'desc'              => __( 'General styles for the checkout steps, order summary and other sections. Might also apply to other pages such as the Cart, Order Received and View Order pages.', 'fluid-checkout' ) . ' <br>' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-design-templates/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_design_template',
						'type'              => 'fc_template_selector',
						'options'           => FluidCheckout_DesignTemplates::instance()->get_design_template_options(),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_design_template' ),
						'autoload'          => false,
						'wrapper_class'     => 'fc-design-template',
						'class'             => 'fc-design-template__option',
					),

					array(
						'title'             => __( 'Dark mode', 'fluid-checkout' ),
						'desc'              => __( 'Enable dark mode', 'fluid-checkout' ),
						'id'                => 'fc_enable_dark_mode_styles',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_dark_mode_styles' ),
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_layout_options',
					),



					array(
						'title' => __( 'Header and footer', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_header_footer_options',
					),

					array(
						'title'             => __( 'Header and footer templates', 'fluid-checkout' ),
						'desc'              => __( 'We recommend using the distraction free header and footer to avoid distractions at the checkout page. <a href="https://baymard.com/blog/cart-abandonment" target="_blank">Read the research about cart abandonment</a>.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Controls whether to use the distraction free page header and footer or keep the currently active theme\'s header and footer.', 'fluid-checkout' ),
						'id'                => 'fc_hide_site_header_footer_at_checkout',
						'type'              => 'select',
						'options'           => array(
							'yes'           => __( 'Distraction free header and footer', 'fluid-checkout' ),
							'no'            => __( 'Theme\'s header and footer', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_hide_site_header_footer_at_checkout' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Logo image', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose an image to be displayed on the checkout page header. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'id'                => 'fc_checkout_logo_image',
						'type'              => 'fc_image_uploader',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_logo_image' ),
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-logo-image',
					),

					array(
						'title'             => __( 'Header background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page header. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_header_background_color',
						'type'              => 'text',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_header_background_color' ),
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'title'             => __( 'Page background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page. Color is applied to the <em>body</em> element.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_page_background_color',
						'type'              => 'text',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_page_background_color' ),
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'title'             => __( 'Footer background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page footer. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_footer_background_color',
						'type'              => 'text',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_footer_background_color' ),
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_header_footer_options',
					),



					array(
						'title' => __( 'Checkout elements', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_elements_options',
					),

					array(
						'title'             => __( 'Progress bar', 'fluid-checkout' ),
						'desc'              => __( 'Display the checkout progress bar when using multi-step checkout layout', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_progress_bar',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_progress_bar' ),
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Make the checkout progress bar stay visible while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_sticky_progress_bar',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_sticky_progress_bar' ),
						'type'              => 'checkbox',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Express checkout', 'fluid-checkout' ),
						'desc'              => __( 'Enable the express checkout section', 'fluid-checkout' ),
						'desc_tip'          => __( 'Displays the express checkout section at checkout when supported payment gateways have this feature enabled.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-express-checkout/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_enable_checkout_express_checkout',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout' ),
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Display express checkout buttons in one line for larger screens', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_express_checkout_inline_buttons',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_inline_buttons' ),
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Ignore additional checkout required fields when paying with a compatible express checkout payment gateway', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_express_checkout_ignore_required_fields',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_ignore_required_fields' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc'              => __( 'Make the order summary stay visible while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_sticky_order_summary',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_sticky_order_summary' ),
						'autoload'          => false,
					),

					array(
						'desc_tip'          => __( 'Choose a background color for the order summary section.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_order_review_highlight_color',
						'type'              => 'text',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_order_review_highlight_color' ),
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'desc'              => __( 'Highlight the order totals row in the order summary table', 'fluid-checkout' ),
						'desc_tip'          => __( 'Most useful when the order summary section does not have a highlighted background color. Might also apply to the Cart, Order Received and View Order pages when using Fluid Checkout PRO.', 'fluid-checkout' ),
						'id'                => 'fc_show_order_totals_row_highlighted',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_order_totals_row_highlighted' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Action link on the order summary at checkout.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_checkout_edit_cart_replace_edit_cart_link',
						'type'              => 'fc_select',
						'options'           => array(
							'edit_cart_link'           => array( 'label' => __( 'Edit cart link', 'fluid-checkout' ) ),
							'cart_items_count'         => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Cart items count', 'fluid-checkout' ), 'disabled' => true ),
							'shop_link'                => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Link to shop page', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_edit_cart_replace_edit_cart_link' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Cart items', 'fluid-checkout' ),
						'desc'              => __( 'Enable options to edit cart items on the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'Allow customers to change product quantities or removing items directly at the checkout page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-checkout-edit-cart/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_checkout_edit_cart',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_checkout_edit_cart' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Hide cart items errors at the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'Do not display the cart items errors message at the top of the checkout page. When submitting the checkout form to complete the purchase, these error messages will always be displayed.', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_items_error_messages_hide_at_checkout',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_items_error_messages_hide_at_checkout' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Place order', 'fluid-checkout' ),
						'desc'              => __( 'Define the position to display "Place order" and terms checkbox section.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Some options might not be compatible with some plugins and themes.', 'fluid-checkout' ),
						'id'                => 'fc_checkout_place_order_position',
						'type'              => 'select',
						'options'           => array(
							'below_payment_section'             => __( 'Below the payment section', 'fluid-checkout' ),
							'below_order_summary'               => __( 'Below the order summary', 'fluid-checkout' ),
							'both_payment_and_order_summary'    => __( 'Both below the payment section and the order summary', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_place_order_position' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Trust symbols &amp; badges', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the checkout page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
						'id'                => 'fc_enable_checkout_widget_areas',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_areas' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display checkout sidebar widgets only when viewing the last checkout step on mobile devices when using multi-step checkout layout', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_widget_area_sidebar_last_step',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_area_sidebar_last_step' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_elements_options',
					),



					array(
						'title' => __( 'Coupon codes', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_coupon_code_options',
					),

					array(
						'title'             => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'              => __( 'Enable integrated coupon code section', 'fluid-checkout' ),
						'desc_tip'          => __( 'Only applicable if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_coupon_codes',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_coupon_codes' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the coupon codes section title', 'fluid-checkout' ),
						'desc_tip'          => __( 'Only applicable when coupon code is displayed as a separate section on the checkout or cart pages.', 'fluid-checkout' ),
						'id'                => 'fc_display_coupon_code_section_title',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_display_coupon_code_section_title' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Select position where to display the coupon codes section on the checkout page.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_checkout_coupon_codes_position',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Hidden', 'fluid-checkout' ), 'disabled' => true ),
							'before_checkout_steps'    => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Before the checkout steps', 'fluid-checkout' ), 'disabled' => true ),
							'substep_before_payment'   => array( 'label' => __( 'As a substep before payment methods', 'fluid-checkout' ) ),
							'substep_after_payment'    => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a substep after payment methods', 'fluid-checkout' ), 'disabled' => true ),
							'inside_order_summary'     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Inside the order summary', 'fluid-checkout' ), 'disabled' => true ),
							'before_order_totals'      => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Before the order totals', 'fluid-checkout' ), 'disabled' => true ),
							'after_order_totals'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'After the order totals', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_coupon_codes_position' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Select style of the "apply coupon" button. Only applicable when the coupon code section is displayed "Before the checkout steps" on the checkout page, or "Before the cart items section" on the cart page.', 'fluid-checkout' ),
						'id'                => 'fc_pro_checkout_coupon_code_message_button_style',
						'type'              => 'fc_select',
						'options'           => array(
							'button'           => __( 'Default button style', 'fluid-checkout' ),
							'add_link_button'  => __( '"Add" link button', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_coupon_code_message_button_style' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_coupon_code_options',
					),



					array(
						'title' => __( 'Checkout fields', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_fields_options',
					),

					array(
						'title'             => __( 'Optional fields', 'fluid-checkout' ),
						'desc'              => __( 'Hide optional fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'It is recommended to keep this option checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_hide_optional_fields',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_hide_optional_fields' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Do not hide "Address line 2" fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'Recommended only when most customers actually need the "Address line 2" field, or when getting the right shipping address is crucial (ie. if delivering food and other perishable products).', 'fluid-checkout' ),
						'id'                => 'fc_hide_optional_fields_skip_address_2',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_hide_optional_fields_skip_address_2' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Display the "Add" link buttons in lowercase', 'fluid-checkout' ),
						'desc_tip'          => __( 'Make the labels of optional field "Add" link button as <code>lowercase</code> (ie. "Add phone number" instead of "Add Phone Number"). This option also affects the link buttons for coupon code fields.', 'fluid-checkout' ),
						'id'                => 'fc_optional_fields_link_label_lowercase',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_optional_fields_link_label_lowercase' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Billing address', 'fluid-checkout' ),
						'desc'              => __( 'Select position where to display the billing address section on the checkout page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-billing-address-positions/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_checkout_billing_address_position',
						'type'              => 'fc_select',
						'options'           => array(
							'step_before_shipping'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a step before shipping', 'fluid-checkout' ), 'disabled' => true ),
							'substep_before_shipping'    => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step before the shipping address section', 'fluid-checkout' ), 'disabled' => true ),
							'substep_after_shipping'     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step after the shipping address section', 'fluid-checkout' ), 'disabled' => true ),
							'step_after_shipping'        => array( 'label' => __( 'As a step after shipping', 'fluid-checkout' ) ),
							'substep_before_payment'     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step before the payment section', 'fluid-checkout' ), 'disabled' => true ),
							'force_single_address'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Force billing and shipping addresses to the same (single section)', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_billing_address_position' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Checkbox for "Same as shipping/billing address" checked by default', 'fluid-checkout' ),
						'desc_tip'          => __( 'The checkbox "Same as shiping address" will start as checked by default when the shipping address section is displayed first. <br>The checkbox "Same as billing address" will start as checked by default when the billing address section is displayed first. <br> It is recommended to leave this option checked as to significantly reduce the number of open input fields at checkout, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#3-default-billing--shipping-and-hide-the-fields-entirely" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_default_to_billing_same_as_shipping',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_default_to_billing_same_as_shipping' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Highlight the billing address section in the checkout form', 'fluid-checkout' ),
						'id'                => 'fc_show_billing_section_highlighted',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_billing_section_highlighted' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping address', 'fluid-checkout' ),
						'desc'              => __( 'Highlight the shipping address section in the checkout form', 'fluid-checkout' ),
						'id'                => 'fc_show_shipping_section_highlighted',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_shipping_section_highlighted' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping methods', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the shipping methods section.', 'fluid-checkout' ),
						'id'                => 'fc_shipping_methods_substep_position',
						'type'              => 'select',
						'options'           => array(
							'before_shipping_address' => __( 'Before shipping address', 'fluid-checkout' ),
							'after_shipping_address'  => __( 'After shipping address', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_methods_substep_position' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Prevent automatic selection of the first shipping method', 'fluid-checkout' ),
						'desc_tip'          => __( 'When enabled, the first shipping method available will not be automatically selected when no other shipping method was previously selected for each shipping package. <br>This option will be automatically enabled if the option for clearing the selected shipping method is enabled for the Local Pickup feature.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_shipping_methods_disable_auto_select',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_methods_disable_auto_select' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Local pickup', 'fluid-checkout' ),
						'desc'              => __( 'Removes shipping address section when a local pickup shipping method is selected.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Replaces the shipping address with the pickup point location when a local pickup shipping method is selected.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-local-pickup/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_enable_checkout_local_pickup',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_local_pickup' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Show option to clear shipping methods in the pickup location substep', 'fluid-checkout' ),
						'desc_tip'          => __( 'Show a link button on the pickup location substep to clear the chosen shipping methods. This can be used to allow showing the shipping address section again if a local pickup method was previously selected.', 'fluid-checkout' ),
						'id'                => 'fc_local_pickup_display_clear_shipping_methods_button',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_display_clear_shipping_methods_button' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'desc'              => __( 'Choose which address to save as the shipping address for local pickup orders.', 'fluid-checkout' ),
						'id'                => 'fc_local_pickup_save_shipping_address',
						'type'              => 'fc_select',
						'options'           => array(
							'same_as_pickup_location'    => __( 'Save the selected pickup location', 'fluid-checkout' ),
							'same_as_billing'            => __( 'Save same as the billing address', 'fluid-checkout' ),
							'no'                         => __( 'Do not save any shipping address', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_save_shipping_address' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Company name field for shipping address', 'fluid-checkout' ),
						'desc'              => __( 'Change visibility for the company name field for the shipping address section on the checkout form.', 'fluid-checkout' ),
						'desc_tip'          => __( 'If field is set as "optional", which is the default visibility state, no changes will be applied to let other plugins apply any changes they need.', 'fluid-checkout' ),
						'id'                => 'fc_shipping_company_field_visibility',
						'type'              => 'select',
						'options'           => array(
							'no'            => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'      => __( 'Optional', 'fluid-checkout' ),
							'required'      => __( 'Required', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_company_field_visibility' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Shipping phone', 'fluid-checkout' ),
						'desc'              => __( 'Add shipping phone field to the checkout form.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Maybe be forced as "required" if the billing address section is displayed after the shipping address section, and the billing phone field is set as "required. This is needed to ensure the shipping address can be copied to the billing address when that option is checked.' ),
						'id'                => 'fc_shipping_phone_field_visibility',
						'type'              => 'select',
						'options'           => array(
							'no'            => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'      => __( 'Optional', 'fluid-checkout' ),
							'required'      => __( 'Required', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_phone_field_visibility' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Choose in which step to display the shipping phone field.', 'fluid-checkout' ),
						'id'                => 'fc_shipping_phone_field_position',
						'type'              => 'select',
						'options'           => array(
							'shipping_address' => __( 'Shipping address', 'fluid-checkout' ),
							'contact'          => __( 'Contact step', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_phone_field_position' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Billing phone', 'fluid-checkout' ),
						'desc'              => __( 'Add billing phone field to the checkout form.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Maybe be forced as "required" if the billing address section is displayed after the billing address section, and the billing phone field is set as "required. This is needed to ensure the billing address can be copied to the billing address when that option is checked.' ),
						'id'                => 'woocommerce_checkout_phone_field',
						'type'              => 'select',
						'options'           => array(
							'hidden'        => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'      => __( 'Optional', 'fluid-checkout' ),
							'required'      => __( 'Required', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_checkout_phone_field' ),
						'autoload'          => false,
					),

					array(
						'desc'              => __( 'Choose in which step to display the billing phone field.', 'fluid-checkout' ),
						'id'                => 'fc_billing_phone_field_position',
						'type'              => 'select',
						'options'           => array(
							'billing_address' => __( 'Billing address', 'fluid-checkout' ),
							'contact'          => __( 'Contact step', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_billing_phone_field_position' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'International phone numbers', 'fluid-checkout' ),
						'desc'              => __( 'Enable international phone number format and validation for phone fields', 'fluid-checkout' ),
						'desc_tip'          => __( 'Format and validate phone numbers according to each country.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-international-phone-numbers/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_international_phone_fields',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_fields' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Enable phone number validation based on country rules', 'fluid-checkout' ),
						'desc_tip'          => __( 'When disabled, the phone field validation will not check if country or area codes are valid for the country.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_international_phone_validation',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_validation' ),
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Only show allowed countries for shipping or billing', 'fluid-checkout' ),
						'desc_tip'          => __( 'When enabled, only the countries allowed for shipping will be available in the shipping phone field, and only countries allowed for billing will be available for the billing phone field.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_international_phone_country_list_filter',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_country_list_filter' ),
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Show country code beside the flag', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_international_phone_country_code',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_international_phone_country_code' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'desc'              => __( 'Show an example of a valid phone number inside phone fields', 'fluid-checkout' ),
						'id'                => 'fc_pro_international_phone_fields_placeholder',
						'type'              => 'fc_select',
						'options'           => array(
							'off'           => __( 'Do not change placeholders', 'fluid-checkout' ),
							'polite'        => __( 'Show if not defined', 'fluid-checkout' ),
							'aggressive'    => __( 'Always show', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_international_phone_fields_placeholder' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order notes', 'fluid-checkout' ),
						'desc'              => __( 'Define the visibility of the additional order notes field.', 'fluid-checkout' ),
						'id'                => 'woocommerce_enable_order_comments',
						'type'              => 'select',
						'options'           => array(
							'no'            => __( 'Hidden', 'fluid-checkout' ),
							'yes'           => __( 'Optional', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_order_comments' ),
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Gift options', 'fluid-checkout' ),
						'desc'              => __( 'Display gift message and other gift options at the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'Allow customers to add a gift message and other gift related options to the order.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_enable_checkout_gift_options',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_gift_options' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Display the gift message fields always expanded', 'fluid-checkout' ),
						'id'                => 'fc_default_gift_options_expanded',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_default_gift_options_expanded' ),
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Display the gift message as part of the order details table instead of a separate section', 'fluid-checkout' ),
						'desc_tip'          => __( 'This option affects the order confirmation page (thank you page) and order details on account pages, emails and packing slips.', 'fluid-checkout' ),
						'id'                => 'fc_display_gift_message_in_order_details',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_display_gift_message_in_order_details' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_fields_options',
					),



					array(
						'title' => __( 'Account matching', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_pro_account_matching_options',
					),

					array(
						'title'             => __( 'Account matching', 'fluid-checkout' ),
						'desc'              => __( 'Enable the account matching feature', 'fluid-checkout' ),
						'desc_tip'          => __( 'Associate the guest customer\'s orders with their existing account when an account already exists with the customer\'s contact details.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_account_matching',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_account_matching' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),
					array(
						'desc'              => __( 'Display message when an account exists with the email address provided', 'fluid-checkout' ),
						'desc_tip'          => __( 'Replaces the account creation fields with a notification and option to log in. In some contexts, it might be recommended to leave this option disabled to protect the privacy of customers.', 'fluid-checkout' ),
						'id'                => 'fc_pro_account_matching_display_account_exists_message',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_account_matching_display_account_exists_message' ),
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_account_matching_options',
					),

				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Checkout_Settings();

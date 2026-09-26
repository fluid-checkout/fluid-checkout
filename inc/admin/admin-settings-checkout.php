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
						'title' => __( 'Layout & Design', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_layout_options',
					),

					array(
						'title'                 => __( 'Checkout layout', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_layout',
						'type'                  => 'fc_layout_selector',
						'options'               => array(
							'multi-step'       => array( 'label' => __( 'Multi step', 'fluid-checkout' ) ),
							'single-step'      => array( 'label' => __( 'Single step', 'fluid-checkout' ) ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_layout' ),
						'autoload'              => false,
						'wrapper_class'         => 'fc-checkout-layout',
						'class'                 => 'fc-checkout-layout__option',
						'checkboxgroup'         => 'start',
						'fc_layout_group'       => 'start',
					),

					array(
						'title'                 => '',
						'desc'                  => '',
						'desc_tip'          => FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_checkout_column_layout',
						'requires'              => 'pro',
						'type'                  => 'fc_layout_selector',
						'options'               => array(
							'two_columns'      => array( 'label' => __( '2 columns', 'fluid-checkout' ) ),
							'one_column'       => array( 'label' => __( '1 column', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_column_layout' ),
						'autoload'              => false,
						'wrapper_class'         => 'fc-checkout-layout',
						'class'                 => 'fc-checkout-layout__option',
						'checkboxgroup'         => 'end',
						'fc_layout_group'       => 'end',
					),

					array(
						'title'                 => __( 'Design template', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'General styles for the checkout steps, order summary and other sections. <br>Might also apply to other pages such as the Cart, Order Received and View Order pages.', 'fluid-checkout' ) . ' <br>' . __( 'When using the <em>"1 column"</em> layout with the <em>"Split"</em> design template, a right column is shown only if the page sidebar widget area is active (Checkout Sidebar, Cart Sidebar, or Order Received Sidebar). Otherwise the page stays as a single column with Split styles.', 'fluid-checkout' ) . ' <br>' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-design-templates/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_design_template',
						'requires'              => 'pro',
						'type'                  => 'fc_template_selector',
						'options'               => array(
							'classic'          => array( 'label' => __( 'Classic', 'fluid-checkout' ) ),
							'boxed'            => array( 'label' => __( 'Boxed', 'fluid-checkout' ), 'disabled' => true ),
							'minimalist'       => array( 'label' => __( 'Minimalist', 'fluid-checkout' ), 'disabled' => true ),
							'split'            => array( 'label' => __( 'Split', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_design_template' ),
						'autoload'              => false,
						'wrapper_class'         => 'fc-design-template',
						'class'                 => 'fc-design-template__option',
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Choose a background color for the Split design secondary column. Leave empty to use the order summary background color.', 'fluid-checkout' ) . '<br>' . __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_secondary_column_background_color',
						'type'                  => 'text',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_secondary_column_background_color' ),
						'autoload'              => false,
						'class'                 => 'colorpick',
						'custom_attributes'     => array(
							'data-conditional-id'    => 'fc_design_template',
							'data-conditional-value' => 'split',
						),
					),

					array(
						'title'                 => __( 'Dark mode', 'fluid-checkout' ),
						'desc'                  => __( 'Enable dark mode', 'fluid-checkout' ),
						'id'                    => 'fc_enable_dark_mode_styles',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_dark_mode_styles' ),
						'type'                  => 'checkbox',
						'autoload'              => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_layout_options',
					),



					array(
						'title' => __( 'Header and Footer', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_header_footer_options',
					),

					array(
						'title'                 => __( 'Header and footer templates', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'              => __( 'We recommend using the distraction free header and footer to avoid distractions at the checkout page. <a href="https://baymard.com/blog/cart-abandonment" target="_blank">Read the research about cart abandonment</a>.', 'fluid-checkout' ) . ' ' . __( 'Controls whether to use the distraction free page header and footer or keep the currently active theme\'s header and footer.', 'fluid-checkout' ),
						'id'                    => 'fc_hide_site_header_footer_at_checkout',
						'type'                  => 'select',
						'options'               => array(
							'yes'              => __( 'Distraction free header and footer', 'fluid-checkout' ),
							'no'               => __( 'Theme\'s header and footer', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_hide_site_header_footer_at_checkout' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Logo image', 'fluid-checkout' ),
						'desc_tip'              => __( 'Choose an image to be displayed on the checkout page header. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_logo_image',
						'type'                  => 'fc_image_uploader',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_logo_image' ),
						'autoload'              => false,
						'wrapper_class'         => 'fc-checkout-logo-image',
					),

					array(
						'title'                 => __( 'Header background color', 'fluid-checkout' ),
						'desc_tip'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ) . ' ' . __( 'Choose a background color for the checkout page header. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'desc'                  => '',
						'id'                    => 'fc_checkout_header_background_color',
						'type'                  => 'color',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_header_background_color' ),
						'autoload'              => false,
						'css'                   => 'max-width: 8rem;',
					),

					array(
						'title'                 => __( 'Page background color', 'fluid-checkout' ),
						'desc_tip'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ) . ' ' . __( 'Choose a background color for the checkout page. Color is applied to the <em>body</em> element.', 'fluid-checkout' ),
						'desc'                  => '',
						'id'                    => 'fc_checkout_page_background_color',
						'type'                  => 'color',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_page_background_color' ),
						'autoload'              => false,
						'css'                   => 'max-width: 8rem;',
					),

					array(
						'title'                 => __( 'Footer background color', 'fluid-checkout' ),
						'desc_tip'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ) . ' ' . __( 'Choose a background color for the checkout page footer. Only applies when using the distraction free header and footer.', 'fluid-checkout' ),
						'desc'                  => '',
						'id'                    => 'fc_checkout_footer_background_color',
						'type'                  => 'color',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_footer_background_color' ),
						'autoload'              => false,
						'css'                   => 'max-width: 8rem;',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_header_footer_options',
					),



					array(
						'title' => __( 'Checkout Elements', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_elements_options',
					),

					array(
						'title'                 => __( 'Progress bar', 'fluid-checkout' ),
						'desc'                  => __( 'Display the checkout progress bar when using multi-step checkout layout', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_progress_bar',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_progress_bar' ),
						'type'                  => 'checkbox',
						'checkboxgroup'         => 'start',
						'show_if_checked'       => 'option',
						'autoload'              => false,
					),
					array(
						'desc'                  => __( 'Make the checkout progress bar stay visible while scrolling', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_sticky_progress_bar',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_sticky_progress_bar' ),
						'type'                  => 'checkbox',
						'checkboxgroup'         => 'end',
						'show_if_checked'       => 'yes',
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Choose the style of the progress bar.', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_progress_bar_style',
						'requires'              => 'pro',
						'type'                  => 'fc_select',
						'options'               => array(
							'bars'             => array( 'label' => __( 'Bars', 'fluid-checkout' ) ),
							'breadcrumbs'      => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Breadcrumbs', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_edit_cart_replace_edit_cart_link' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Order summary', 'fluid-checkout' ),
						'desc'                  => __( 'Make the order summary stay visible while scrolling', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_sticky_order_summary',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_sticky_order_summary' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Choose a background color for the order summary section.', 'fluid-checkout' ) . '<br>' . __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_order_review_highlight_color',
						'type'                  => 'color',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_order_review_highlight_color' ),
						'autoload'              => false,
						'css'                   => 'max-width: 8rem;',
					),

					array(
						'desc'                  => __( 'Highlight the order totals row in the order summary table', 'fluid-checkout' ),
						'desc_tip'              => __( 'Most useful when the order summary section does not have a highlighted background color. Might also apply to the Cart, Order Received and View Order pages when using Fluid Checkout PRO.', 'fluid-checkout' ),
						'id'                    => 'fc_show_order_totals_row_highlighted',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_order_totals_row_highlighted' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Action link on the order summary at checkout.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_pro_checkout_edit_cart_replace_edit_cart_link',
						'requires'              => 'pro',
						'type'                  => 'fc_select',
						'options'               => array(
							'edit_cart_link'   => array( 'label' => __( 'Edit cart link', 'fluid-checkout' ) ),
							'cart_items_count' => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Cart items count', 'fluid-checkout' ), 'disabled' => true ),
							'shop_link'        => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Link to shop page', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_edit_cart_replace_edit_cart_link' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Position for the extra order summary section on mobile.', 'fluid-checkout' ) . 
												'<br>' . __( 'The option <em>"On the site header"</em> only applies when using the distraction-free header.', 'fluid-checkout' ) .
												'<br>' . __( 'When using the <em>"1 column"</em> checkout layout this option is always set to <em>"Before checkout steps"</em> and also applies to desktop view.', 'fluid-checkout' ) .
												FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_pro_checkout_order_summary_position_mobile',
						'requires'              => 'pro',
						'type'                  => 'fc_select',
						'options'               => array(
							'hidden'                => array( 'label' => __( 'Hidden', 'fluid-checkout' ) ),
							'site_header'           => array( 'label' => __( 'On the site header (requires distraction-free header)', 'fluid-checkout' ) ),
							'before_checkout_steps' => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Before checkout steps', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_experimental_feature_html(), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_order_summary_position_mobile' ),
						'autoload'              => false,
					),
					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Initial state for the expansible order summary section on mobile.', 'fluid-checkout' ) .
												'<br>' . __( 'We recommend setting it as <em>expanded</em> for the following countries due to regulatory requirements: <strong>Germany</strong>, <strong>Austria</strong>, <strong>Switzerland</strong>, <strong>Netherlands</strong>, <strong>Poland</strong>, <strong>Belgium</strong> and <strong>France</strong>.', 'fluid-checkout' ),
						'id'                    => 'fc_pro_checkout_order_summary_collapsible_initial_state',
						'type'                  => 'fc_select',
						'options'               => array(
							'collapsed'        => array( 'label' => __( 'Collapsed', 'fluid-checkout' ) ),
							'expanded'         => array( 'label' => __( 'Expanded', 'fluid-checkout' ) ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_order_summary_collapsible_initial_state' ),
						'autoload'              => false,
						'disabled'              => true,
						'requires'              => 'pro',
					),

					array(
						'title'                 => __( 'Cart items', 'fluid-checkout' ),
						'desc'                  => __( 'Enable options to edit cart items on the checkout page', 'fluid-checkout' ),
						'desc_tip'              => __( 'Allow customers to change product quantities or removing items directly at the checkout page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-checkout-edit-cart/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_pro_enable_checkout_edit_cart',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_checkout_edit_cart' ),
						'checkboxgroup'         => 'start',
						'show_if_checked'       => 'option',
						'autoload'              => false,
						'disabled'              => true,
						'requires'              => 'pro',
					),
					array(
						'desc'                  => __( 'Hide cart items errors at the checkout page', 'fluid-checkout' ),
						'desc_tip'              => __( 'Do not display the cart items errors message at the top of the checkout page. When submitting the checkout form to complete the purchase, these error messages will always be displayed.', 'fluid-checkout' ),
						'id'                    => 'fc_pro_cart_items_error_messages_hide_at_checkout',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_items_error_messages_hide_at_checkout' ),
						'checkboxgroup'         => 'end',
						'show_if_checked'       => 'yes',
						'autoload'              => false,
						'disabled'              => true,
						'requires'              => 'pro',
					),

					array(
						'title'                 => __( 'Place order', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'              => __( 'Define the position to display "Place order" and terms checkbox section.', 'fluid-checkout' ) . ' ' . __( 'Some options might not be compatible with some plugins and themes.', 'fluid-checkout' ),
						'id'                    => 'fc_checkout_place_order_position',
						'type'                  => 'select',
						'options'               => array(
							'below_payment_section'           => __( 'Below the payment section', 'fluid-checkout' ),
							'below_order_summary'             => __( 'Below the order summary', 'fluid-checkout' ),
							'both_payment_and_order_summary'  => __( 'Both below the payment section and the order summary', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_place_order_position' ),
						'autoload'              => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_elements_options',
					),



					array(
						'title' => __( 'Trust Symbols & Badges', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_trust_symbols_options',
						'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
					),

					array(
						'title'                 => __( 'Widget areas', 'fluid-checkout' ),
						'desc'                  => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
						'desc_tip'              => __( 'These widget areas are used to add trust symbols and trust badges on the checkout page.', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_widget_areas',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_areas' ),
						'checkboxgroup'         => 'start',
						'show_if_checked'       => 'option',
						'autoload'              => false,
					),
					array(
						'desc'                  => __( 'Display checkout sidebar widgets only when viewing the last checkout step on mobile devices when using multi-step checkout layout', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_widget_area_sidebar_last_step',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_area_sidebar_last_step' ),
						'checkboxgroup'         => 'end',
						'show_if_checked'       => 'yes',
						'autoload'              => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_trust_symbols_options',
					),



					array(
						'title' => __( 'Coupon Codes', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_coupon_code_options',
					),

					array(
						'title'                 => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'                  => __( 'Enable integrated coupon code section', 'fluid-checkout' ),
						'desc_tip'              => __( 'Only applicable if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_coupon_codes',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_coupon_codes' ),
						'checkboxgroup'         => 'start',
						'show_if_checked'       => 'option',
						'autoload'              => false,
					),
					array(
						'desc'                  => __( 'Display the coupon codes section title', 'fluid-checkout' ),
						'desc_tip'              => __( 'Only applicable when coupon code is displayed as a separate section on the checkout or cart pages.', 'fluid-checkout' ),
						'id'                    => 'fc_display_coupon_code_section_title',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_display_coupon_code_section_title' ),
						'checkboxgroup'         => 'end',
						'show_if_checked'       => 'yes',
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Select position where to display the coupon codes section on the checkout page.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_pro_checkout_coupon_codes_position',
						'requires'              => 'pro',
						'type'                  => 'fc_select',
						'options'               => array(
							'none'                       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Hidden', 'fluid-checkout' ), 'disabled' => true ),
							'before_checkout_steps'      => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Before the checkout steps', 'fluid-checkout' ), 'disabled' => true ),
							'substep_before_payment'     => array( 'label' => __( 'As a substep before payment methods', 'fluid-checkout' ) ),
							'substep_after_payment'      => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a substep after payment methods', 'fluid-checkout' ), 'disabled' => true ),
							'inside_order_summary'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Inside the order summary', 'fluid-checkout' ), 'disabled' => true ),
							'before_order_totals'        => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Before the order totals', 'fluid-checkout' ), 'disabled' => true ),
							'after_order_totals'         => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'After the order totals', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_coupon_codes_position' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Select style of the "apply coupon" button. Only applicable when the coupon code section is displayed "Before the checkout steps" on the checkout page, or "Before the cart items section" on the cart page.', 'fluid-checkout' ),
						'id'                    => 'fc_pro_checkout_coupon_code_message_button_style',
						'type'                  => 'fc_select',
						'options'               => array(
							'button'           => __( 'Default button style', 'fluid-checkout' ),
							'add_link_button'  => __( '"Add" link button', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_coupon_code_message_button_style' ),
						'autoload'              => false,
						'disabled'              => true,
						'requires'              => 'pro',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_coupon_code_options',
					),



					array(
						'title' => __( 'Checkout Fields', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_fields_options',
					),

					array(
						'title'                 => __( 'Optional fields', 'fluid-checkout' ),
						'desc'                  => __( 'Hide optional fields behind a link button', 'fluid-checkout' ),
						'desc_tip'              => __( 'It is recommended to keep this option checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                    => 'fc_enable_checkout_hide_optional_fields',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_hide_optional_fields' ),
						'checkboxgroup'         => 'start',
						'show_if_checked'       => 'option',
						'autoload'              => false,
					),
					array(
						'desc'                  => __( 'Do not hide "Address line 2" fields behind a link button', 'fluid-checkout' ),
						'desc_tip'              => __( 'Recommended only when most customers actually need the "Address line 2" field, or when getting the right shipping address is crucial (ie. if delivering food and other perishable products).', 'fluid-checkout' ),
						'id'                    => 'fc_hide_optional_fields_skip_address_2',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_hide_optional_fields_skip_address_2' ),
						'checkboxgroup'         => 'end',
						'show_if_checked'       => 'yes',
						'autoload'              => false,
					),

					array(
						'desc'                  => __( 'Display the "Add" link buttons in lowercase', 'fluid-checkout' ),
						'desc_tip'              => __( 'Make the labels of optional field "Add" link button as <code>lowercase</code> (ie. "Add phone number" instead of "Add Phone Number"). This option also affects the link buttons for coupon code fields.', 'fluid-checkout' ),
						'id'                    => 'fc_optional_fields_link_label_lowercase',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_optional_fields_link_label_lowercase' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Billing address', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'Select position where to display the billing address section on the checkout page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-billing-address-positions/' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_pro_checkout_billing_address_position',
						'requires'              => 'pro',
						'type'                  => 'fc_select',
						'options'               => array(
							'step_before_shipping'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a step before shipping', 'fluid-checkout' ), 'disabled' => true ),
							'substep_before_shipping'    => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step before the shipping address section', 'fluid-checkout' ), 'disabled' => true ),
							'substep_after_shipping'     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step after the shipping address section', 'fluid-checkout' ), 'disabled' => true ),
							'step_after_shipping'        => array( 'label' => __( 'As a step after shipping', 'fluid-checkout' ) ),
							'substep_before_payment'     => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'As a sub-step before the payment section', 'fluid-checkout' ), 'disabled' => true ),
							'force_single_address'       => array( 'label' => FluidCheckout_Admin::instance()->get_pro_feature_option_html( true ) . __( 'Force billing and shipping addresses to the same (single section)', 'fluid-checkout' ), 'disabled' => true ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_checkout_billing_address_position' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => __( 'Checkbox for "Same as shipping/billing address" checked by default', 'fluid-checkout' ),
						'desc_tip'              => __( 'The checkbox "Same as shiping address" will start as checked by default when the shipping address section is displayed first. <br>The checkbox "Same as billing address" will start as checked by default when the billing address section is displayed first. <br> It is recommended to leave this option checked as to significantly reduce the number of open input fields at checkout, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#3-default-billing--shipping-and-hide-the-fields-entirely" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                    => 'fc_default_to_billing_same_as_shipping',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_default_to_billing_same_as_shipping' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => __( 'Highlight the billing address section in the checkout form', 'fluid-checkout' ),
						'id'                    => 'fc_show_billing_section_highlighted',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_billing_section_highlighted' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Shipping address', 'fluid-checkout' ),
						'desc'                  => __( 'Highlight the shipping address section in the checkout form', 'fluid-checkout' ),
						'id'                    => 'fc_show_shipping_section_highlighted',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_show_shipping_section_highlighted' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Shipping methods', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'Choose in which position to display the shipping methods section.', 'fluid-checkout' ),
						'id'                    => 'fc_shipping_methods_substep_position',
						'type'                  => 'select',
						'options'               => array(
							'before_shipping_address'    => __( 'Before shipping address', 'fluid-checkout' ),
							'after_shipping_address'     => __( 'After shipping address', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_methods_substep_position' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => __( 'Prevent automatic selection of the first shipping method', 'fluid-checkout' ),
						'desc_tip'              => __( 'When enabled, the first shipping method available will not be automatically selected when no other shipping method was previously selected for each shipping package. <br>This option will be automatically enabled if the option for clearing the selected shipping method is enabled for the Local Pickup feature.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                    => 'fc_shipping_methods_disable_auto_select',
						'type'                  => 'checkbox',
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_methods_disable_auto_select' ),
						'autoload'              => false,
						'disabled'              => true,
						'requires'              => 'pro',
					),

					array(
						'title'                 => __( 'Company name field for shipping address', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'              => __( 'Change visibility for the company name field for the shipping address section on the checkout form.', 'fluid-checkout' ) . ' ' . __( 'If field is set as "optional", which is the default visibility state, no changes will be applied to let other plugins apply any changes they need.', 'fluid-checkout' ),
						'id'                    => 'fc_shipping_company_field_visibility',
						'type'                  => 'select',
						'options'               => array(
							'no'               => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'         => __( 'Optional', 'fluid-checkout' ),
							'required'         => __( 'Required', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_company_field_visibility' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Company name field for billing address', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'              => __( 'Change visibility for the company name field for the billing address section on the checkout form.', 'fluid-checkout' ) . ' ' . __( 'If field is set as "optional", which is the default visibility state, no changes will be applied to let other plugins apply any changes they need.', 'fluid-checkout' ),
						'id'                    => 'woocommerce_checkout_company_field',
						'type'                  => 'select',
						'options'               => array(
							'hidden'           => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'         => __( 'Optional', 'fluid-checkout' ),
							'required'         => __( 'Required', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_checkout_company_field' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Shipping phone', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'Add shipping phone field to the checkout form.', 'fluid-checkout' ) . '<br>' . __( 'The shipping phone field may be forced as "required" if the billing address section is displayed after the shipping address section, and the billing phone field is set as "required". This is needed to ensure the shipping address can be copied to the billing address when that option is checked, otherwise the customer might not be able to complete the checkout form.', 'fluid-checkout' ),
						'id'                    => 'fc_shipping_phone_field_visibility',
						'type'                  => 'select',
						'options'               => array(
							'hidden'           => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'         => __( 'Optional', 'fluid-checkout' ),
							'required'         => __( 'Required', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_phone_field_visibility' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Choose in which step to display the shipping phone field.', 'fluid-checkout' ),
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
						'title'                 => __( 'Billing phone', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'Add billing phone field to the checkout form.', 'fluid-checkout' ) . '<br>' . __( 'The billing phone field may be forced as "required" if the billing address section is displayed before the shipping address section, and the shipping phone field is set as "required". This is needed to ensure the shipping address can be copied to the billing address when that option is checked, otherwise the customer might not be able to complete the checkout form.', 'fluid-checkout' ),
						'id'                    => 'woocommerce_checkout_phone_field',
						'type'                  => 'select',
						'options'               => array(
							'hidden'           => __( 'Hidden (remove field)', 'fluid-checkout' ),
							'optional'         => __( 'Optional', 'fluid-checkout' ),
							'required'         => __( 'Required', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_checkout_phone_field' ),
						'autoload'              => false,
					),

					array(
						'desc'                  => '',
						'desc_tip'          => __( 'Choose in which step to display the billing phone field.', 'fluid-checkout' ),
						'id'                    => 'fc_billing_phone_field_position',
						'type'                  => 'select',
						'options'               => array(
							'billing_address'  => __( 'Billing address', 'fluid-checkout' ),
							'contact'          => __( 'Contact step', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_billing_phone_field_position' ),
						'autoload'              => false,
					),

					array(
						'title'                 => __( 'Order notes', 'fluid-checkout' ),
						'desc'                  => '',
						'desc_tip'          => __( 'Define the visibility of the additional order notes field.', 'fluid-checkout' ),
						'id'                    => 'woocommerce_enable_order_comments',
						'type'                  => 'select',
						'options'               => array(
							'no'               => __( 'Hidden', 'fluid-checkout' ),
							'yes'              => __( 'Optional', 'fluid-checkout' ),
						),
						'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_order_comments' ),
						'autoload'              => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_fields_options',
					),

				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Checkout_Settings();

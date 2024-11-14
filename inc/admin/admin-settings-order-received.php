<?php
/**
 * Fluid Checkout PRO Order Received Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_OrderReceived_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_OrderReceived_Settings();
}

/**
 * WC_Settings_FluidCheckout_OrderReceived_Settings.
 */
class WC_Settings_FluidCheckout_OrderReceived_Settings extends WC_Settings_Page {

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
			'order_received' => _x( 'Thank you', 'Settings section', 'fluid-checkout' ),
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
		if ( 'order_received' === $current_section ) {

			$settings = apply_filters(
				'fc_pro_order_received_settings',
				array(

					array(
						'title' => __( 'Thank you page & order details layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => FluidCheckout_Admin::instance()->get_upgrade_pro_html( false ),
						'id'    => 'fc_pro_order_received_layout_options',
					),

					array(
						'title'             => __( 'Order details optimizations', 'fluid-checkout' ),
						'desc'              => __( 'Enable thank you page and order details optimizations', 'fluid-checkout' ),
						'desc_tip'          => __( 'Changes the layout of order details on the thank you page and account pages.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_order_received',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_received' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'desc'              => __( 'Enable order details customizations on email notifications', 'fluid-checkout' ),
						'desc_tip'          => __( 'Display new sections and change order of sections on email notifications. Styles of order details on email notifications remain unchanged.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_details_email_customizations',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_details_email_customizations' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_received_layout_options',
					),



					array(
						'title' => __( 'Thank you page', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => __( 'These options affect the thank you page, also known as order received or order confirmation pages.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'    => 'fc_pro_order_received_layout_options',
					),

					array(
						'title'             => __( 'Order details layout', 'fluid-checkout' ),
						'desc'              => __( 'Display the order details with wide layout on the thank you page', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_details_wide_layout',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_details_wide_layout' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Trust symbols &amp; badges', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
						'desc'              => __( 'Add widget areas to the thank you page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the thank you page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
						'id'                => 'fc_pro_enable_order_received_widget_areas',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_received_widget_areas' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_received_layout_options',
					),



					array(
						'title' => __( 'Order details layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => __( 'These options affect the thank you page , view order details on account pages and on email notifications.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'    => 'fc_pro_order_details_layout_options',
					),

					array(
						'title'             => __( 'Order actions', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the order actions.', 'fluid-checkout' ),
						'id'                => 'fc_pro_order_details_order_actions_position',
						'type'              => 'fc_select',
						'options'           => array(
							'inside_order_overview'   => __( 'Inside the order overview', 'fluid-checkout' ),
							'after_order_overview'    => __( 'After the order overview', 'fluid-checkout' ),
							'after_order_sections'    => __( 'After order sections', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_order_details_order_actions_position' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order status', 'fluid-checkout' ),
						'desc'              => __( 'Display the order status progress bar', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_details_order_status_progress_bar',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_details_order_status_progress_bar' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the section.', 'fluid-checkout' ),
						'id'                => 'fc_pro_order_details_order_summary_position',
						'type'              => 'fc_select',
						'options'           => array(
							'after_order_overview'    => __( 'After the order overview and payment instructions', 'fluid-checkout' ),
							'before_order_items'      => __( 'Before the order items section', 'fluid-checkout' ),
							'inside_order_items'      => __( 'Inside the order items section', 'fluid-checkout' ),
							'after_order_items'       => __( 'After the order items section', 'fluid-checkout' ),
							'on_sidebar'              => __( 'On the sidebar', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_order_details_order_summary_position' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order downloads', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the section.', 'fluid-checkout' ),
						'id'                => 'fc_pro_order_details_order_downloads_position',
						'type'              => 'fc_select',
						'options'           => array(
							'before_order_items'      => __( 'Before the order items section', 'fluid-checkout' ),
							'inside_order_items'      => __( 'Inside the order items section', 'fluid-checkout' ),
							'after_order_items'       => __( 'After the order items section', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_order_details_order_downloads_position' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Gift message', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the section.', 'fluid-checkout' ),
						'id'                => 'fc_pro_order_details_gift_message_position',
						'type'              => 'fc_select',
						'options'           => array(
							'before_order_items'      => __( 'Before the order items section', 'fluid-checkout' ),
							'inside_order_items'      => __( 'Inside the order items section', 'fluid-checkout' ),
							'after_order_items'       => __( 'After the order items section', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_order_details_gift_message_position' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Order notes', 'fluid-checkout' ),
						'desc'              => __( 'Choose in which position to display the section.', 'fluid-checkout' ),
						'id'                => 'fc_pro_order_details_order_notes_position',
						'type'              => 'fc_select',
						'options'           => array(
							'before_order_items'      => __( 'Before the order items section', 'fluid-checkout' ),
							'inside_order_items'      => __( 'Inside the order items section', 'fluid-checkout' ),
							'after_order_items'       => __( 'After the order items section', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_order_details_order_notes_position' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_details_layout_options',
					),

				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_OrderReceived_Settings();

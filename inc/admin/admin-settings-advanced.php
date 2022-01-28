<?php
/**
 * Fluid Checkout Advanced Settings
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Advanced_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Advanced_Settings();
}

/**
 * WC_Settings_FluidCheckout_Advanced_Settings.
 */
class WC_Settings_FluidCheckout_Advanced_Settings extends WC_Settings_Page {

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
		// Define sections to insert
		$insert_sections = array(
			'advanced' => __( 'Advanced', 'fluid-checkout' ),
		);

		// Get token position
		$position_index = count( $sections );

		// Insert at token position
		$new_sections = array_slice( $sections, 0, $position_index );
		$new_sections = array_merge( $new_sections, $insert_sections );
		$new_sections = array_merge( $new_sections, array_slice( $sections, $position_index, count( $sections ) ) );
		
		return $new_sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'advanced' === $current_section ) {

			$settings = array(
				array(
					'title' => __( 'Layout', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_advanced_layout_options',
				),

				array(
					'title'            => __( 'Order summary', 'fluid-checkout' ),
					'desc'             => __( 'Make the order summary stay visible while scrolling', 'fluid-checkout' ),
					'id'               => 'fc_enable_checkout_sticky_order_summary',
					'default'          => 'yes',
					'type'             => 'checkbox',
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Progress bar', 'fluid-checkout' ),
					'desc'             => __( 'Make the checkout progress bar stay visible while scrolling', 'fluid-checkout' ),
					'desc_tip'         => __( 'Applies only to multi-step layouts.', 'fluid-checkout' ),
					'id'               => 'fc_enable_checkout_sticky_progress_bar',
					'default'          => 'yes',
					'type'             => 'checkbox',
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Order summary', 'fluid-checkout' ),
					'desc'             => __( '(Experimental) Display an additional "Place order" and terms checkbox below the order summary in the sidebar.', 'fluid-checkout' ),
					'desc_tip'         => __( 'Recommended if most of the orders have only a few different products in the cart, and product variations do not take too much space on the order summary.', 'fluid-checkout' ),
					'id'               => 'fc_enable_checkout_place_order_sidebar',
					'default'          => 'no',
					'type'             => 'checkbox',
					'autoload'         => false,
				),

				array(
					'title'            => __( 'Header and Footer', 'fluid-checkout' ),
					'desc'             => __( 'We recommend using the Fluid Checkout header and footer to avoid distractions at the checkout page. <a href="https://baymard.com/blog/cart-abandonment" target="_blank">Read the research about cart abandonment</a>.', 'fluid-checkout' ),
					'desc_tip'         => __( 'Controls whether to use the Fluid Checkout page header and footer or keep the currently active theme\'s.', 'fluid-checkout' ),
					'id'               => 'fc_hide_site_header_footer_at_checkout',
					'type'             => 'radio',
					'options'          => array(
						'yes'          => __( 'Use Fluid Checkout header and footer', 'fluid-checkout' ),
						'no'           => __( '(Experimental) Use theme\'s page header and footer for the checkout page', 'fluid-checkout' ),
					),
					'default'          => 'yes',
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_advanced_layout_options',
				),

				array(
					'title' => __( 'Troubleshooting', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_advanced_debug_options',
				),

				array(
					'title'            => __( 'Debug options', 'fluid-checkout' ),
					'desc'             => __( 'Debug mode', 'fluid-checkout' ),
					'desc_tip'         => __( 'Using debug mode affects the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
					'id'               => 'fc_debug_mode',
					'default'          => 'no',
					'type'             => 'checkbox',
					'checkboxgroup'    => 'start',
					'show_if_checked'  => 'option',
					'autoload'         => false,
				),
				array(
					'desc'             => __( 'Load unminified assets', 'fluid-checkout' ),
					'id'               => 'fc_load_unminified_assets',
					'default'          => 'no',
					'type'             => 'checkbox',
					'checkboxgroup'    => 'end',
					'show_if_checked'  => 'yes',
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_advanced_debug_options',
				),
			);

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings, $current_section );
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Advanced_Settings();

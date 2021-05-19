<?php
/**
 * WooCommerce Checkout Settings
 *
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Settings_FluidCheckout_Checkout', false ) ) {
	return new WC_Settings_FluidCheckout_Checkout();
}

/**
 * WC_Settings_FluidCheckout_Checkout.
 */
class WC_Settings_FluidCheckout_Checkout extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'wfc_checkout';
		$this->label = __( 'Checkout', 'woocommerce-fluid-checkout' );

		parent::__construct();

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		
	}



	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''             => __( 'Checkout Options', 'woocommerce-fluid-checkout' ),
			'advanced'     => __( 'Advanced', 'woocommerce-fluid-checkout' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}



	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}



	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}



	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		if ( 'advanced' === $current_section ) {
			$settings = apply_filters(
				'wfc_checkout_advanced_settings',
				array(
					array(
						'title' => __( 'Layout', 'woocommerce-fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wfc_checkout_advanced_layout_options',
					),

					array(
						'title'         => __( 'Progress bar', 'woocommerce-fluid-checkout' ),
						'desc'          => __( 'Make the checkout progress bar stay visible while scrolling', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_enable_checkout_sticky_progress_bar',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Order Summary', 'woocommerce-fluid-checkout' ),
						'desc'          => __( 'Make the order summary stay visible while scrolling', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_enable_checkout_sticky_order_summary',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Checkout Header', 'woocommerce-fluid-checkout' ),
						'desc_tip'      => __( 'Controls whether to use the Fluid Checkout page header or to keep the header of the current active theme.', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_hide_site_header_at_checkout',
						'type'          => 'radio',
						'options'       => array(
							'yes'       => __( 'Use Fluid Checkout page header', 'woocommerce-fluid-checkout' ),
							'no'        => __( 'Use theme\'s page header at the checkout page', 'woocommerce-fluid-checkout' ),
						),
						'default'       => 'yes',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Checkout Footer', 'woocommerce-fluid-checkout' ),
						'desc_tip'      => __( 'Controls whether to use the Fluid Checkout page footer or to keep the footer of the current active theme.', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_hide_site_footer_at_checkout',
						'type'          => 'radio',
						'options'       => array(
							'yes'       => __( 'Use Fluid Checkout page footer', 'woocommerce-fluid-checkout' ),
							'no'        => __( 'Use theme\'s page footer at the checkout page', 'woocommerce-fluid-checkout' ),
						),
						'default'       => 'yes',
						'autoload'      => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'wfc_checkout_advanced_layout_options',
					),
				)
			);
		}
		else {
			$settings = apply_filters(
				'wfc_checkout_general_settings',
				array(
					array(
						'title' => __( 'Layout', 'woocommerce-fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wfc_checkout_layout_options',
					),

					array(
						'title'         => __( 'Checkout Layout', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_checkout_layout',
						'type'          => 'radio',
						'options'       => FluidCheckout_Steps::instance()->get_allowed_checkout_layouts(),
						'default'       => 'multi-step',
						'autoload'      => false,
						'wrapper_class' => 'wfc-checkout-layout',
						'class'         => 'wfc-checkout-layout__option',
					),
					
					array(
						'title'         => __( 'Optional fields', 'woocommerce-fluid-checkout' ),
						'desc'          => __( 'Hide optional fields behind a "+ Add optional field" link button', 'woocommerce-fluid-checkout' ),
						'desc_tip'      => __( 'It is recommended to keep this options checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>. <br>When hiding the optional fields, the name of the field will appear in the link button (ie. "+ Add company name").', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_enable_checkout_hide_optional_fields',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),
					
					array(
						'title'         => __( 'Order Summary', 'woocommerce-fluid-checkout' ),
						'desc'          => __( 'Display an additional "Place order" and terms checkbox below the order summary in the sidebar.', 'woocommerce-fluid-checkout' ),
						'id'            => 'wfc_enable_checkout_place_order_sidebar',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false,
					),
					
					array(
						'type' => 'sectionend',
						'id'   => 'wfc_checkout_layout_options',
					),
				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}
}

return new WC_Settings_FluidCheckout_Checkout();

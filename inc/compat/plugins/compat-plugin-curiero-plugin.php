<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CurieRO (by CurieRO).
 */
class FluidCheckout_Curiero extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shipping methods hooks
		add_filter( 'woocommerce_load_shipping_methods', array( $this, 'shipping_methods_hooks' ), 100 );
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/plugins/curiero-plugin/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template_override = locate_template( array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				) );
	
				// Check if files exist before changing template
				if ( file_exists( $_template_override ) ) {
					$_template = $_template_override;
				}
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Add or remove late hooks.
	 */
	public function shipping_methods_hooks( $methods ) {
		// DPD
		$dpd_class_name = 'DPD_Shipping_Method';
		if ( class_exists( $dpd_class_name ) ) {
			$dpd_class_object = $this->get_object_by_class_name_from_hooks( $dpd_class_name );
			remove_action( 'woocommerce_review_order_after_shipping', array( $dpd_class_object, 'add_dpd_boxes_dropdown_section' ), 10 );
			add_action( 'fc_shipping_methods_after_packages_inside', array( $dpd_class_object, 'add_dpd_boxes_dropdown_section' ), 10 );
		}

		// FanShipping
		$fan_class_name = 'Fan_Shipping_Method';
		if ( class_exists( $fan_class_name ) ) {
			$fan_class_object = $this->get_object_by_class_name_from_hooks( $fan_class_name );
			remove_action( 'woocommerce_review_order_after_shipping', array( $fan_class_object, 'review_order_after_shipping' ), 10 );
			add_action( 'fc_shipping_methods_after_packages_inside', array( $fan_class_object, 'review_order_after_shipping' ), 10 );
		}
		
		// Sameday
		$sameday_class_name = 'Sameday_Shipping_Method';
		if ( class_exists( $sameday_class_name ) ) {
			$sameday_class_object = $this->get_object_by_class_name_from_hooks( $sameday_class_name );
			remove_action( 'woocommerce_review_order_after_shipping', array( $sameday_class_object, 'add_lockers_dropdown_section' ), 10 );
			add_action( 'fc_shipping_methods_after_packages_inside', array( $sameday_class_object, 'add_lockers_dropdown_section' ), 10 );
		}

		return $methods;
	}

}

FluidCheckout_Curiero::instance();

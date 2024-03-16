<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Umea (by Edge Themes).
 */
class FluidCheckout_ThemeCompat_Umea extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Maybe remove elements from theme
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			remove_action( 'woocommerce_before_checkout_form', 'umea_add_main_woo_page_holder', 5 );
			remove_action( 'woocommerce_after_checkout_form', 'umea_add_main_woo_page_holder_end', 20 );
		}
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'umea_core_get_post_value_through_levels' ) ) { return $css_variables; }

		// Get theme main color
		$main_color = umea_core_get_post_value_through_levels( 'qodef_main_color' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '44px',
				'--fluidcheckout--field--padding-left' => '18px',
				'--fluidcheckout--field--border-color' => '#959595',
				'--fluidcheckout--field--background-color--accent' => $main_color,

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $main_color,
				'--fluidcheckout--button--primary--background-color' => $main_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $main_color,
				'--fluidcheckout--button--secondary--background-color' => 'transparent',
				'--fluidcheckout--button--secondary--text-color' => $main_color,
				'--fluidcheckout--button--secondary--border-color--hover' => $main_color,
				'--fluidcheckout--button--secondary--background-color--hover' => $main_color,
				'--fluidcheckout--button--secondary--text-color--hover' => 'var( --fluidcheckout--color--white, #fff )',

				// Button design styles
				'--fluidcheckout--button--height' => '50px',
				'--fluidcheckout--button--font-weight' => 'bold',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Umea::instance();

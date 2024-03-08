<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Aperitif Core (by Qode Themes).
 */
class FluidCheckout_AperitifCore extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Remove container with ID `qodef-woo-page` added by the plugin
		remove_action( 'woocommerce_before_checkout_form', 'aperitif_core_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'aperitif_core_add_main_woo_page_holder_end', 20 );

		// Re-add Woocommerce stylesheet
		remove_filter( 'woocommerce_enqueue_styles', '__return_false' );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Theme default color values
		$button_text_color = '#ffffff';
		$button_background_color = '#c8693a';
		$button_background_color_hover = '#d77647';

		// Maybe get color values from the theme settings
		if ( function_exists( 'aperitif_core_get_post_value_through_levels' ) ) {
			$main_color = aperitif_core_get_post_value_through_levels( 'qodef_main_color' );
			$main_color_hover = aperitif_core_get_post_value_through_levels( 'qodef_main_color_hover' );

			// Primary color
			if ( ! empty( $main_color ) ) { $button_background_color = $main_color; }

			// Primary color in hover state
			if ( ! empty( $main_color_hover ) ) { $button_background_color_hover = $main_color_hover; }
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				'--fluidcheckout--button--primary--border-color' => $button_background_color,
				'--fluidcheckout--button--primary--background-color' => $button_background_color,
				'--fluidcheckout--button--primary--text-color' => $button_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_background_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $button_background_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $button_text_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_AperitifCore::instance();

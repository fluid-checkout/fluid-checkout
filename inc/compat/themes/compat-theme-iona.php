<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Iona (by ThemeREX).
 */
class FluidCheckout_ThemeCompat_Iona extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Site header sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );

		// Theme mobile scripts
		add_filter( 'iona_filter_localize_script', array( $this, 'maybe_change_script_mobile_args' ), 10, 2 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Color values from the theme
		$theme_hover_color = '#ecb928';
		$theme_hover_text_color = '#ffffff';
		$theme_input_border_color = '#dcdcdc';
		$theme_selection_background_color = '#222222';

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42.8px',
				'--fluidcheckout--field--padding-left' => '16.2px',
				'--fluidcheckout--field--border-color' => $theme_input_border_color,
				'--fluidcheckout--field--background-color--accent' => $theme_selection_background_color,

				// Primary button colors
				'--fluidcheckout--button--primary--border-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--primary--background-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--primary--text-color--hover' => $theme_hover_text_color,

				// Secondary button colors
				'--fluidcheckout--button--secondary--border-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--secondary--background-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--secondary--text-color--hover' => $theme_hover_text_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the relative selector for sticky elements.
	 *
	 * @param   array  $attributes  The element HTML attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = 'header .sc_layouts_row_fixed_on';

		return $attributes;
	}



	/**
	 * Change the mobile script args.
	 *
	 * @param   array  $script_args  The script args.
	 */
	public function change_script_mobile_args( $script_args ) {
		// Change the mobile args
		$script_args = array_merge( $script_args, array(
			// Window width to switch the site header to the mobile layout
			'mobile_layout_width' => 0,
			'mobile_device'       => false,
		) );

		return $script_args;
	}

	/**
	 * Maybe change the mobile script args on the checkout page.
	 * 
	 * @param   array  $script_args  The script args.
	 */
	public function maybe_change_script_mobile_args( $script_args ) {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $script_args; }

		return $this->change_script_mobile_args( $script_args );
	}

}

FluidCheckout_ThemeCompat_Iona::instance();

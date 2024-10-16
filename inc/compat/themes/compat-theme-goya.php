<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Goya (by Everthemes).
 */
class FluidCheckout_ThemeCompat_Goya extends FluidCheckout {

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
		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_enhanced_select_assets' ), 100 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-goya-floating-labels', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/goya/float-labels' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-goya-floating-labels', 'window.addEventListener("load",function(){GoyaFloatLabels.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-goya-floating-labels' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header-sticky .header';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		$primary_background_color = get_theme_mod( 'primary_buttons', '#282828' );
		$primary_text_color = get_theme_mod( 'primary_buttons_text_color', '#fff' );
		$secondary_background_color = get_theme_mod( 'second_buttons', '#282828' );
		$accent_color = get_theme_mod( 'accent_color', '#b9a16b' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '54px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => '#ddd',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $primary_background_color,
				'--fluidcheckout--button--primary--background-color' => $primary_background_color,
				'--fluidcheckout--button--primary--text-color' => $primary_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $primary_background_color,
				'--fluidcheckout--button--primary--background-color--hover' => $primary_background_color,
				'--fluidcheckout--button--primary--text-color--hover' => $primary_text_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => 'currentColor',
				'--fluidcheckout--button--secondary--background-color' => 'transparent',
				'--fluidcheckout--button--secondary--text-color' => 'currentColor',
				'--fluidcheckout--button--secondary--border-color--hover' => $primary_background_color,
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => $primary_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Dequeue enhanced select assets.
	 */
	public function maybe_dequeue_enhanced_select_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if function is not available
		if ( ! method_exists( FluidCheckout_Enqueue::instance(), 'dequeue_enhanced_select_assets' ) ) { return; }

		// Bail if theme's floating labels feature is not enabled
		if ( ! get_theme_mod( 'elements_floating_labels', true ) ) { return; }

		FluidCheckout_Enqueue::instance()->dequeue_enhanced_select_assets();
	}

}

FluidCheckout_ThemeCompat_Goya::instance();

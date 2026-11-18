<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Synex (by Gramentheme).
 */
class FluidCheckout_ThemeCompat_Synex extends FluidCheckout {

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
		add_filter( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Theme breadcrumb / page heading
		$this->maybe_remove_theme_breadcrumb();
	}



	/**
	 * Maybe remove the theme breadcrumb and page heading section.
	 */
	public function maybe_remove_theme_breadcrumb() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		remove_action( 'synex_before_main_content', 'synex_breadcrumb_func' );
		remove_action( 'creatim_before_main_content', 'synex_breadcrumb_func' );
		remove_action( 'hfe_header', 'synex_breadcrumb_func', 20 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-synex-smooth-scroll', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/synex/smooth-scroll' ), array( 'jquery', 'synex-main' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-synex-smooth-scroll', 'window.addEventListener("load",function(){SynexSmoothScroll.init();});' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-synex-smooth-scroll' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

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
	 * Change the element used to position the progress bar and order summary when sticky.
	 *
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes[ 'data-sticky-relative-to' ] = '.header-section';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 *
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				// Theme sets global `input { color: var(--white) }`, which makes field text unreadable on light backgrounds.
				'--fluidcheckout--field--text-color' => 'var( --header, #15171A )',
				// Match Select2/TomSelect height, padding and font-size to text inputs (theme body is 18px).
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '9px',
				'--fluidcheckout--field--font-size' => '18px',
				// Match WooCommerce form fields.
				'--fluidcheckout--field--border-radius' => 'var( --wc-form-border-radius, 4px )',
				'--fluidcheckout--field--border-color' => 'var( --wc-form-border-color, rgba(32, 7, 7, 0.8) )',
				'--fluidcheckout--field--background-color--accent' => 'var( --theme, #007BFF )',

				// Button design styles
				'--fluidcheckout--button--height' => '42px',
				'--fluidcheckout--button--border-radius' => '8px',
				'--fluidcheckout--button--primary--background-color' => 'var( --theme, #007BFF )',
				'--fluidcheckout--button--primary--border-color' => 'var( --theme, #007BFF )',
				'--fluidcheckout--button--primary--text-color' => '#fff',
				// Match theme `.theme-btn-main:hover .theme-btn { background-color: var(--header) }`.
				'--fluidcheckout--button--primary--background-color--hover' => 'var( --header, #15171A )',
				'--fluidcheckout--button--primary--border-color--hover' => 'var( --header, #15171A )',
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Synex::instance();

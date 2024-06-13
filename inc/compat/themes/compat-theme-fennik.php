<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Fennik (by LA Studio).
 */
class FluidCheckout_ThemeCompat_Fennik extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Checkout template hooks
		$this->checkout_template_hooks();
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Quantity fields
		remove_action( 'woocommerce_after_quantity_input_field', 'fennik_wc_add_qty_control_plus', 10 );
		remove_action( 'woocommerce_before_quantity_input_field', 'fennik_wc_add_qty_control_minus', 10 );

		// Order review heading
		remove_action( 'woocommerce_checkout_order_review', 'fennik_add_custom_heading_to_checkout_order_review', 0 );
	}



	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add opening tags for inner container from the theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div id="content-wrap" class="container">
		<?php
	}

	/**
	 * Add closing tags for inner container from the theme.
	 */
	public function add_inner_container_closing_tags() {
		?>
		</div>
		<?php
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-fennik-image-lazy-load', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/fennik/lazy-load' ), array( 'jquery', 'fennik-theme' ), NULL, true );
		wp_add_inline_script( 'fc-compat-fennik-image-lazy-load', 'window.addEventListener("load",function(){FennikLazyLoad.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Bail if lazy load is not enabled in the theme
		if ( ! fennik_get_option( 'activate_lazyload' ) ) { return; }

		// Scripts
		// Lazy load
		wp_enqueue_script( 'fc-compat-fennik-image-lazy-load' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_option' ) ) { return; }
	
		$this->enqueue_assets();
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_theme_option_by_context' ) ) { return $attributes; }

		$is_sticky = fennik_get_theme_option_by_context( 'header_sticky', 'no' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#lastudio-header-builder.is-sticky .lahbhinner';

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
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--border-color' => 'var(--theme-border-color)',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => 'var(--theme-secondary-color)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Fennik::instance();

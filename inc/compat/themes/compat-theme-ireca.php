<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Ireca (by Envato).
 */
class FluidCheckout_ThemeCompat_Ireca extends FluidCheckout {

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
		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
		
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_place_order_button_classes', array( $this, 'remove_button_classes' ), 10 );

		// Dequeue Select2 files
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_select2_files' ), 100 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// For Ireca theme, use the .ovamenu_shrink element for sticky positioning
		$attributes['data-sticky-relative-to'] = '.ovamenu_shrink';

		return $attributes;
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
	 * Remove classes from place order button for Ireca theme.
	 *
	 * @param string $classes Button classes.
	 */
	public function remove_button_classes( $classes ) {
		// Add Ireca theme specific button classes
		$classes = str_replace( 'alt', '', $classes );
		
		return $classes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get Ireca theme's main color
		$main_color = get_theme_mod( 'main_color', '#e9a31b' );
		
		// Add CSS variables for Ireca theme
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '37px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--color' => '#200707cc',
				'--fluidcheckout--field--padding-left' => '7px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => '#200707cc',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => $main_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

	/**
	 * Dequeue Select2 files.
	 */
	public function dequeue_select2_files() {
		// Dequeue Select2 files if they are enqueued by the theme
		if ( wp_style_is( 'select2_ireca', 'enqueued' ) ) {
			wp_dequeue_style( 'select2_ireca' );
		}
		if ( wp_script_is( 'select2_ireca', 'enqueued' ) ) {
			wp_dequeue_script( 'select2_ireca' );
		}
	}

}

FluidCheckout_ThemeCompat_Ireca::instance();

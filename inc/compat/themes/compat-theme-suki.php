<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Suki (by Suki Team).
 */
class FluidCheckout_ThemeCompat_Suki extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove plus and minus buttons to the quantity input from suki theme.
		remove_action( 'woocommerce_after_quantity_input_field', array( Suki_Compatibility_WooCommerce::instance(), 'add_quantity_plus_minus_buttons' ) );
		remove_action( 'wp_enqueue_scripts', array( Suki_Compatibility_WooCommerce::instance(), 'add_quantity_plus_minus_buttons_scripts' ) );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Force functions - Also changes saved values; mainly so that the UI is consistent with the saved value and users see the change in the Customizer or edit page.
		$this->force_content_layout_wide_on_page_load();
		$this->force_cart_and_checkout_layouts_default_on_page_load();

		// Filters for global settings - The saved value in the Customizer is untouched; for maintaining an always-on override.
		add_filter( 'theme_mod_content_layout', function( $value ) { return 'wide'; }, 99 );
		add_filter( 'theme_mod_woocommerce_cart_layout', function( $value ) { return 'default'; }, 99 );
		add_filter( 'theme_mod_woocommerce_checkout_layout', function( $value ) { return 'default'; }, 99 );
	}



	/**
	 * Force content_layout to 'wide' on page load for per-page and per-term settings.
	 */
	public function force_content_layout_wide_on_page_load() {
		// Get post ID
		$post_id = get_queried_object_id();

		// Per-page
		if ( $post_id ) {
			// Get page settings
			$page_settings = get_post_meta( $post_id, '_suki_page_settings', true );

			// Set content_layout to 'wide' if it's not already set or is not 'wide'
			if ( is_array( $page_settings ) && ( ! isset( $page_settings[ 'content_layout' ] ) || $page_settings[ 'content_layout' ] !== 'wide') ) {
				// Set content_layout to 'wide'
				$page_settings[ 'content_layout' ] = 'wide';

				// Update page settings
				update_post_meta( $post_id, '_suki_page_settings', $page_settings );
			}
		}
		
		// Get queried object
		$queried_object = get_queried_object();

		// Per-term
		if ( $queried_object instanceof WP_Term ) {
			// Get term settings	
			$term_settings = get_term_meta( $queried_object->term_id, 'suki_page_settings', true );

			// Set content_layout to 'wide' if it's not already set or is not 'wide'
			if ( is_array( $term_settings ) && ( ! isset( $term_settings[ 'content_layout' ] ) || $term_settings[ 'content_layout' ] !== 'wide') ) {
				// Set content_layout to 'wide'
				$term_settings[ 'content_layout' ] = 'wide';

				// Update term settings
				update_term_meta( $queried_object->term_id, 'suki_page_settings', $term_settings );
			}
		}
	}



	/**
	 * Force cart and checkout layouts to 'default' on page load.
	 */
	public function force_cart_and_checkout_layouts_default_on_page_load() {
		// Bail if theme mods already have expected values
		if ( 'default' === get_theme_mod( 'woocommerce_cart_layout' ) && 'default' === get_theme_mod( 'woocommerce_checkout_layout' ) ) { return; }

		// Otherwise, force cart and checkout layouts to 'default'
		set_theme_mod( 'woocommerce_cart_layout', 'default' );
		set_theme_mod( 'woocommerce_checkout_layout', 'default' );
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
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'rgba(0,0,0,.1)',
			),
		);
		
		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}
}

FluidCheckout_ThemeCompat_Suki::instance();

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Always force content_layout to 'wide' on save (posts and pages)
		add_action( 'save_post', array( $this, 'force_content_layout_wide_on_save_post' ), 99, 1 );

		// Always force content_layout to 'wide' on edit term (taxonomy terms like categories and tags) (posts and pages)
		add_action( 'edit_term', array( $this, 'force_content_layout_wide_on_edit_term' ), 99, 3 );

		// Force content_layout to 'wide' on page load
		add_action( 'wp', array( $this, 'force_content_layout_wide_on_page_load' ), 99, 1 );

		// Force WooCommerce cart and checkout layouts to 'default' globally
		add_action( 'after_setup_theme', array( $this, 'force_cart_and_checkout_layouts_default_on_save_or_customize_save' ) );
		add_action( 'customize_save_after', array( $this, 'force_cart_and_checkout_layouts_default_on_save_or_customize_save' ) );

		// Force on page load
		add_action( 'wp', array( $this, 'force_cart_and_checkout_layouts_default_on_page_load' ) );

		// Always force WooCommerce cart and checkout layouts to 'default' via theme_mod filters. The WooCommerce cart and checkout layouts will always be 'default' (single column), no matter what is saved in the Customizer or database.
		add_filter( 'theme_mod_woocommerce_cart_layout', function( $value ) { return 'default'; }, 99 );
		add_filter( 'theme_mod_woocommerce_checkout_layout', function( $value ) { return 'default'; }, 99 );
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
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// ! Double check if theme is using variables for these values
				// Form field styles
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'rgba(0,0,0,.1)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Force per-page content_layout meta to 'wide' when saving a post.
	 */
	public function force_content_layout_wide_on_save_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		// 1. Bail early if not a supported post type (including customize_changeset handled separately)
		$supported_post_types = function_exists( 'suki_get_post_types_for_page_settings' )
			? suki_get_post_types_for_page_settings()
			: array( 'page', 'post' );
		if ( ! in_array( $post_type, $supported_post_types, true ) && $post_type !== 'customize_changeset' ) {
			// Not a supported post type, bail out
			return;
		}

		// 2. Special handling for Customizer saves
		if ( $post_type === 'customize_changeset' ) {
			update_option( 'content_layout', 'wide' );
			return;
		}

		// 3. Only update if the post already has Suki page settings meta
		$page_settings = get_post_meta( $post_id, '_suki_page_settings', true );
		if ( is_array( $page_settings ) ) {
			$page_settings['content_layout'] = 'wide';
			update_post_meta( $post_id, '_suki_page_settings', $page_settings );
		}
	}



	/**
	 * Force per-term content_layout meta to 'wide' when saving a term.
	 */
	public function force_content_layout_wide_on_edit_term( $term_id, $tt_id, $taxonomy ) {
		// Bail early if not a supported taxonomy
		$default_taxonomies = array( 'category', 'post_tag' );
		$custom_taxonomies = function_exists( 'get_taxonomies' )
			? get_taxonomies( array(
				'public'             => true,
				'publicly_queryable' => true,
				'rewrite'            => true,
				'_builtin'           => false,
			), 'names' )
			: array();

		// Merge default and custom taxonomies
		$supported_taxonomies = array_merge( $default_taxonomies, $custom_taxonomies );

		if ( ! in_array( $taxonomy, $supported_taxonomies, true ) ) {
			// Not a supported taxonomy, bail out
			return;
		}

		// Only update if the term already has Suki page settings meta
		$page_settings = get_term_meta( $term_id, 'suki_page_settings', true );
		if ( is_array( $page_settings ) ) {
			$page_settings['content_layout'] = 'wide';
			update_term_meta( $term_id, 'suki_page_settings', $page_settings );
		}
	}



	/**
	 * Force content_layout to 'wide' on page load.
	 */
	public function force_content_layout_wide_on_page_load() {
		// Get the current content layout
		$content_layout = get_option( 'content_layout' );
		if ( $content_layout !== 'wide' ) {
			update_option( 'content_layout', 'wide' );
		}

		// Get current post/page ID
		$post_id = get_queried_object_id();
		if ( $post_id ) {
			// Get the current page settings
			$page_settings = get_post_meta( $post_id, '_suki_page_settings', true );
			if ( is_array( $page_settings ) ) {
				$page_settings['content_layout'] = 'wide';
				update_post_meta( $post_id, '_suki_page_settings', $page_settings );
			}
		}

		// Get current term ID
		$term_id = null;
		$queried_object = get_queried_object();
		if ( $queried_object instanceof WP_Term ) {
			$term_id = $queried_object->term_id;
		}

		if ( $term_id ) {
			// Get the current term settings
			$term_settings = get_term_meta( $term_id, 'suki_page_settings', true );
			if ( is_array( $term_settings ) ) {
				$term_settings['content_layout'] = 'wide';
				update_term_meta( $term_id, 'suki_page_settings', $term_settings );
			}
		}

		// Log the result
		error_log('FluidCheckout: content_layout after page load: ' . get_option( 'content_layout' ));
		error_log('FluidCheckout: _suki_page_settings after page load: ' . print_r(get_post_meta( $post_id ?? 0, '_suki_page_settings', true ), true));
		error_log('FluidCheckout: suki_page_settings after page load: ' . print_r(get_term_meta( $term_id ?? 0, 'suki_page_settings', true ), true));
	}

	public function force_cart_and_checkout_layouts_default_on_customize_save() {
		// Set cart layout to default
		error_log('FluidCheckout: woocommerce_cart_layout before set: ' . get_theme_mod('woocommerce_cart_layout'));
		set_theme_mod( 'woocommerce_cart_layout', 'default' );
		error_log('FluidCheckout: woocommerce_cart_layout after set: ' . get_theme_mod('woocommerce_cart_layout'));

		// Set checkout layout to default 
		error_log('FluidCheckout: woocommerce_checkout_layout before set: ' . get_theme_mod('woocommerce_checkout_layout'));
		set_theme_mod( 'woocommerce_checkout_layout', 'default' );
		error_log('FluidCheckout: woocommerce_checkout_layout after set: ' . get_theme_mod('woocommerce_checkout_layout'));
	}


	public function force_cart_and_checkout_layouts_default_on_page_load() {
		// Set cart layout to default
		$cart_layout = get_theme_mod( 'woocommerce_cart_layout' );
		if ( $cart_layout !== 'default' ) {
			set_theme_mod( 'woocommerce_cart_layout', 'default' );
			error_log('FluidCheckout: woocommerce_cart_layout after set: ' . get_theme_mod('woocommerce_cart_layout'));
		}

		// Set checkout layout to default
		$checkout_layout = get_theme_mod( 'woocommerce_checkout_layout' );
		if ( $checkout_layout !== 'default' ) {
			set_theme_mod( 'woocommerce_checkout_layout', 'default' );
			error_log('FluidCheckout: woocommerce_checkout_layout after set: ' . get_theme_mod('woocommerce_checkout_layout'));
		}
	}


}

FluidCheckout_ThemeCompat_Suki::instance();

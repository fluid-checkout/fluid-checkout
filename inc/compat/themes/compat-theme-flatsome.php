<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Flatsome (by UX-Themes).
 */
class FluidCheckout_ThemeCompat_Flatsome extends FluidCheckout {

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

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Make header static (not sticky at the top)
		add_filter( 'theme_mod_header_sticky', array( $this, 'change_theme_mod_header_sticky' ), 10 );

		// Use theme's logo
		add_action( 'fc_checkout_header_logo', array( $this, 'output_checkout_header_logo' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Revert Flatsome changes to the privacy policy placement at the checkout page
		remove_action( 'woocommerce_checkout_after_order_review', 'wc_checkout_privacy_policy_text', 1 );
		add_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-flatsome-floating-labels', self::$directory_url . 'js/compat/themes/flatsome/float-labels'. self::$asset_version . '.js', array( 'jquery' ), NULL, true );
		wp_add_inline_script( 'fc-compat-flatsome-floating-labels', 'window.addEventListener("load",function(){FlatsomeFloatLabels.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-flatsome-floating-labels' );
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
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Disable the sticky header option on the checkout page.
	 *
	 * @param mixed $current_mod  Theme modification value.
	 */
	public function change_theme_mod_header_sticky( $current_mod ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $current_mod; }

		return 0; // Disabled
	}



	/**
	 * Output the theme logo on the plugin's checkout header.
	 */
	public function output_checkout_header_logo() {
		get_template_part( 'template-parts/header/partials/element', 'logo' );
	}

}

FluidCheckout_ThemeCompat_Flatsome::instance();

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_enhanced_select_assets' ), 100 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
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
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '38.91px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--box-shadow' => 'inset 0 1px 2px rgba( 0, 0, 0, .1 )',
				'--fluidcheckout--field--box-shadow--focus' => 'none',
				'--fluidcheckout--field--background-color--accent' => 'var(--primary-color)',
			),
			':root body.fl-labels' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '46.69px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-flatsome-floating-labels', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/flatsome/float-labels' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-flatsome-floating-labels', 'window.addEventListener("load",function(){FlatsomeFloatLabels.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		// Floating labels
		if ( get_theme_mod( 'checkout_floating_labels', 0 ) ) {
			wp_enqueue_script( 'fc-compat-flatsome-floating-labels' );
		}
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
	 * Dequeue enhanced select assets.
	 */
	public function maybe_dequeue_enhanced_select_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if function is not available
		if ( ! method_exists( FluidCheckout_Enqueue::instance(), 'dequeue_enhanced_select_assets' ) ) { return; }

		// Bail if theme's floating labels feature is not enabled
		if ( ! get_theme_mod( 'checkout_floating_labels', 0 ) ) { return; }

		FluidCheckout_Enqueue::instance()->dequeue_enhanced_select_assets();
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

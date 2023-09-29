<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: WordPress Theme Atomion (by MarketPress).
*/
class FluidCheckout_ThemeCompat_WordPressThemeAtomion extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

		// Cart items
		add_filter( 'atomion_wc_checkout_description_show_excerpt_with_markup', array( $this, 'maybe_change_cart_item_description_html' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Theme elements
		remove_action( 'atomion_breadcrumb', 'atomion_order_progress', 15 );
		remove_action( 'woocommerce_after_checkout_form', 'atomion_wc_required_fields_note', 10 );
		remove_action( 'woocommerce_review_order_before_submit', 'atomion_checkout_go_back_button', 10 );
		remove_action( 'woocommerce_checkout_order_review', 'atomion_checkout_go_back_button', 50 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header-main.sticky';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme main color
		$main_color = get_theme_mod( 'gc_primary_color_setting', '#37B9E3' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $main_color,
				'--fluidcheckout--button--primary--background-color' => $main_color,
				'--fluidcheckout--button--primary--border-color--hover' => $main_color,
				'--fluidcheckout--button--primary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--primary--text-color--hover' => $main_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color--hover' => $main_color,
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => $main_color,

				// Button design styles
				'--fluidcheckout--button--border-width' => '3px',
				'--fluidcheckout--button--height' => '50px',
				'--fluidcheckout--button--font-weight' => 'bold',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the cart item description HTML.
	 *
	 * @param   string   $html    The cart item description HTML.
	 */
	public function change_cart_item_description_html( $html ) {
		$html = str_replace( '<br><small>', '<div class="cart-item__element cart-item__description"><small>', $html );
		$html = str_replace( '</small>', '</small></div>', $html );
		return $html;
	}

	/**
	 * Maybe change the cart item description HTML for the checkout page.
	 *
	 * @param   string   $html    The cart item description HTML.
	 */
	public function maybe_change_cart_item_description_html( $html ) {
		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $html; }

		return $this->change_cart_item_description_html( $html );
	}

}

FluidCheckout_ThemeCompat_WordPressThemeAtomion::instance();

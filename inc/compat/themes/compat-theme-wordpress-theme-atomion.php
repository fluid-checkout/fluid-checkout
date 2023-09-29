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

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
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

}

FluidCheckout_ThemeCompat_WordPressThemeAtomion::instance();

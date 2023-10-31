<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Gizmos (by Mikado Themes).
 */
class FluidCheckout_ThemeCompat_Gizmos extends FluidCheckout {

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
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove theme elements
		remove_action( 'woocommerce_before_checkout_form', 'gizmos_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'gizmos_add_main_woo_page_holder_end', 20 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function is not available
		if ( ! function_exists( 'gizmos_core_get_post_value_through_levels' ) ) { return $attributes; }

		// Get theme header scroll appearance
		$header_scroll_appearance = gizmos_core_get_post_value_through_levels( 'qodef_header_scroll_appearance' );

		// Maybe set for sticky header
		if ( 'sticky' === $header_scroll_appearance ) {
			$attributes['data-sticky-relative-to'] = '.qodef-header-sticky';
		}
		// Maybe set for fixed header
		else if ( 'fixed' === $header_scroll_appearance ) {
			$attributes['data-sticky-relative-to'] = '.qodef-header--fixed-display #qodef-page-header';
		}

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Gizmos::instance();

<?php

/**
 * Account pages feature
 */
class FluidCheckout_AccountPages extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->init();
	}
	


	/**
	 * Initialize class.
	 */
	public function init() {
		// Bail if account pages not enabled
		if ( get_option( 'wfc_enable_account_pages', 'true' ) !== 'true' ) { return; }

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		
		// Endpoint title
		if ( get_option( 'wfc_enable_account_pages_endpoint_title', 'true' ) === 'true' ) {
			remove_filter( 'the_title', 'wc_page_endpoint_title' );
			add_filter( 'the_title', array( $this, 'maybe_add_endpoint_title' ), 50, 2 );
		}
			
	}





	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on account pages.
		if( ! function_exists( 'is_account_page' ) || ! is_account_page() ){ return $classes; }

		$classes[] = 'has-wfc-account-page';

		return $classes;
	}
	




	/**
	 * Add endpoint title to account page title
	 */
	public function maybe_add_endpoint_title( $title, $post_id = null ) {
		global $wp_query;
		
		// Bail if not on account page
		if( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_null( $wp_query ) || is_admin() || ! is_main_query() || ! in_the_loop() || ! is_page() ) { return $title; }

		// Get WooCommerce endpoint title
		$endpoint = WC()->query->get_current_endpoint();
		$endpoint_title = WC()->query->get_endpoint_title( $endpoint );

		// Add title for dashboard
		if ( ! is_wc_endpoint_url() ) {
			$endpoint = 'dashboard';
			$endpoint_title = __( 'Dashboard', 'woocommerce-fluid-checkout' );
		}

		// Filter endpoint title
		$endpoint_title = apply_filters( 'wfc_account_pages_endpoint_title', $endpoint_title, $endpoint );

		// Maybe change title
		if ( ! empty( $endpoint_title ) ) {
			$title .= sprintf( ' <span class="endpoint-title">%s</span>', $endpoint_title );
		}

		return $title;
	}

}

FluidCheckout_AccountPages::instance();

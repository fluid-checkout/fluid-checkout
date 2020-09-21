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
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Default dashboard page
		add_action( 'woocommerce_account_dashboard', array( $this, 'output_default_account_dashboard_content' ), 10 );
		add_action( 'wfc_edit_account_address_form', array( $this, 'output_default_account_edit_address_content' ), 10, 2 );
		
		// Endpoint title
		if ( get_option( 'wfc_enable_account_pages_endpoint_title', 'true' ) === 'true' ) {
			remove_filter( 'the_title', 'wc_page_endpoint_title' );
			add_filter( 'the_title', array( $this, 'maybe_add_endpoint_title' ), 50, 2 );
		}
		
		// Dashboard
		if ( get_option( 'wfc_enable_account_pages', 'true' ) === 'true' ) {
			remove_action( 'woocommerce_account_dashboard', array( $this, 'output_default_account_dashboard_content' ) );
			add_action( 'woocommerce_account_dashboard', array( $this, 'output_account_dashboard_items' ) );
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
	 * Output the default dashboard page content
	 */
	public function output_default_account_dashboard_content() {
		wc_get_template(
			'myaccount/dashboard-default.php'
		);
	}



	/**
	 * Output the default edit address page content
	 */
	public function output_default_account_edit_address_content( $load_address, $address ) {
		wc_get_template(
			'myaccount/form-edit-address-default.php',
			array (
				'load_address' => $load_address,
				'address' => $address,
			)
		);
	}



	/**
	 * Get data for account dashboard endpoint items
	 */
	public function get_account_dashboard_endpoint_items( $remove_missing = true ) {
		// Get WooCommerce account endpoints
		$wc_nav_endpoints = wc_get_account_menu_items();

		// Get endpoint details from settings or default
		$dashboard_endpoints = get_option( 'wfc_account_dashboard_endpoints', array(
			'orders'		=> array(
				'label' 		=> $wc_nav_endpoints[ 'orders' ],
				'description' 	=> __( 'View orders&apos; details and statuses', 'woocommerce-fluid-checkout' ),
				'image_type'	=> 'image_icon_class',
				'image_icon_class' 	=> 'fa fa-shopping-basket',
			),
			'downloads'		=> array(
				'label' 		=> $wc_nav_endpoints[ 'downloads' ],
				'description' 	=> __( 'View your downloadable files', 'woocommerce-fluid-checkout' ),
				'image_type'	=> 'image_icon_class',
				'image_icon_class' 	=> 'fa fa-file-archive',
			),
			'edit-address'		=> array(
				'label' 		=> $wc_nav_endpoints[ 'edit-address' ],
				'description' 	=> _n( 'Manage saved shipping and billing address', 'Edit your address', (int) wc_shipping_enabled(), 'woocommerce-fluid-checkout' ),
				'image_type'	=> 'image_icon_class',
				'image_icon_class' 	=> 'fa fa-address-book',
			),
			'payment-methods'		=> array(
				'label' 		=> $wc_nav_endpoints[ 'payment-methods' ],
				'description' 	=> __( 'Manage saved payment information', 'woocommerce-fluid-checkout' ),
				'image_type'	=> 'image_icon_class',
				'image_icon_class' 	=> 'fa fa-credit-card',
			),
			'edit-account'		=> array(
				'label' 		=> $wc_nav_endpoints[ 'edit-account' ],
				'description' 	=> __( 'Update your personal information and password', 'woocommerce-fluid-checkout' ),
				'image_type'	=> 'image_icon_class',
				'image_icon_class' 	=> 'fa fa-user',
			),
		) );

		// Remove missing WooCommerce endpoints
		if ( $remove_missing ) {
			foreach ( $dashboard_endpoints as $endpoint_id => $endpoint ) {
				if ( ! array_key_exists( $endpoint_id, $wc_nav_endpoints ) ) {
					unset( $dashboard_endpoints[ $endpoint_id ] );
				}
			}
		}

		return apply_filters( 'wfc_account_dashboard_endpoints', $dashboard_endpoints );
	}



	/**
	 * Get image html for an account dashboard endpoint item
	 */
	public function get_account_dashboard_endpoint_image_html( $endpoint, $endpoint_values ) {
		$html = '';

		$image_type = $endpoint_values[ 'image_type' ];

		if ( $image_type === 'image_url' ) {
			$html = sprintf( '<img src="%s" alt="%s" aria-hidden="true" role="presentation" />', esc_url( $endpoint_values[ 'image_url' ] ), esc_html( $endpoint_values[ 'label' ] ) );
		}
		else if ( $image_type === 'image_icon_class' ) {
			$html = sprintf( '<i class="%s" aria-hidden="true" role="presentation"></i>', esc_attr( $endpoint_values[ 'image_icon_class' ] ) );
		}

		return apply_filters( 'wfc_account_dashboard_endpoint_image_html', $html, $endpoint, $endpoint_values );
	}



	/**
	 * Output dashboard page content with endpoint blocks
	 */
	public function output_account_dashboard_items() {
		wc_get_template(
			'myaccount/dashboard-endpoints.php',
			array(
				'dashboard_endpoints' => $this->get_account_dashboard_endpoint_items(),
			)
		);
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
			$title .= sprintf( ' <span class="endpoint-title-separator"> &gt; </span><span class="endpoint-title">%s</span>', $endpoint_title );
		}

		return $title;
	}

}

FluidCheckout_AccountPages::instance();

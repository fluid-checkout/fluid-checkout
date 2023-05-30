<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout page template feature.
 */
class FluidCheckout_CheckoutPageTemplate extends FluidCheckout {

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
		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Checkout header and footer
		if ( $this->get_hide_site_header_footer_at_checkout() ) {
			add_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
			add_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
		}
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Enqueue
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Checkout page template
		remove_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );

		// Template file loader
		remove_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Checkout header and footer
		if ( $this->get_hide_site_header_footer_at_checkout() ) {
			remove_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
			remove_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
		}
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/fc/checkout-page-template/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;
		}

		// Look for template file in the theme
		if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
			$_template_override = locate_template( array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			) );

			// Check if files exist before changing template
			if ( file_exists( $_template_override ) ) {
				$_template = $_template_override;
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Replace the checkout page template with our own file.
	 *
	 * @param   String  $template  Template file path.
	 */
	public function checkout_page_template( $template ) {
		// Bail if checkout page template is not enabled
		if ( true !== apply_filters( 'fc_enable_checkout_page_template', true ) ) { return $template; }

		// Bail if not on checkout page.
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $template; }

		// Locate new checkout page template
		$_template  = $this->locate_template( $template, 'checkout/page-checkout.php', null );

		// Check if the file exists
		if ( file_exists( $_template ) ) {
			$template = $_template;
		}

		return $template;
	}



	/**
	 * Get option for hiding the site's original header and footer at the checkout page.
	 *
	 * @return  boolean  True if should hide the site's original header and footer at the checkout page, false otherwise.
	 */
	public function get_hide_site_header_footer_at_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Check if checkout page is showing the checkout form, then check the settings to show theme header or plugin header
		return ( ! ( ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() && ! is_user_logged_in() ) ) && 'yes' === get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' );
	}



	/**
	 * Output the checkout header.
	 */
	public function output_checkout_header() {
		// Only display our checkout header if the site header is hidden
		if ( ! $this->get_hide_site_header_footer_at_checkout() ) { return; }

		wc_get_template(
			'checkout/checkout-header.php',
			array( 'checkout' => WC()->checkout() )
		);
	}

	/**
	 * Output the checkout footer.
	 */
	public function output_checkout_footer() {
		// Only display our checkout footer if the site footer is hidden
		if ( ! $this->get_hide_site_header_footer_at_checkout() ) { return; }

		// Bail if nothing was added to the footer
		if ( ! has_action( 'fc_checkout_footer_widgets' ) || ! ( is_active_sidebar( 'fc_checkout_footer' ) || has_action( 'fc_checkout_footer_widgets_inside_before' ) || has_action( 'fc_checkout_footer_widgets_inside_after' ) ) ) { return; }

		wc_get_template( 'checkout/checkout-footer.php' );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Maybe load RTL file
		$rtl_suffix = is_rtl() ? '-rtl' : '';

		// Styles
		wp_register_style( 'fc-checkout-layout', self::$directory_url . 'css/checkout-layout'. $rtl_suffix . self::$asset_version . '.css', NULL, NULL );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Styles
		wp_enqueue_style( 'fc-checkout-layout' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}

}

FluidCheckout_CheckoutPageTemplate::instance();

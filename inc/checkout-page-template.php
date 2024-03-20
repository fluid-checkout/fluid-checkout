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
		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );
		add_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shortcode wrapper
		add_action( 'wp', array( $this, 'maybe_setup_checkout_shortcode_wrapper' ), 10 );

		// Checkout header and footer
		if ( $this->is_distraction_free_header_footer_checkout() ) {
			add_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
			add_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
		}
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Checkout page template
		remove_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );
		remove_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 100 );

		// Template file loader
		remove_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shortcode wrapper
		remove_action( 'wp', array( $this, 'maybe_setup_checkout_shortcode_wrapper' ), 10 );

		// Checkout header and footer
		if ( $this->is_distraction_free_header_footer_checkout() ) {
			remove_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
			remove_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
		}
	}

	/**
	 * Disable custom template for the checkout page content part when using the Full Site Editor (FSE).
	 */
	public function maybe_disable_checkout_page_template( $enabled ) {
		// Bail if using distraction free header and footer
		if ( $this->is_distraction_free_header_footer_checkout() ) { return $enabled; }

		// Bail if theme not using FSE
		if ( ! current_theme_supports( 'block-templates' ) ) { return $enabled; }

		// Disable custom checkout templates.
		return false;
	}



	/**
	 * Setup shortcode wrapper for the checkout shortcode.
	 */
	public function maybe_setup_checkout_shortcode_wrapper() {
		// Bail if checkout page template is not enabled
		if ( true === apply_filters( 'fc_enable_checkout_page_template', true ) ) { return; }

		// Define shortcode tag
		$checkout_shortcode_tag = apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' );

		// Replace checkout shortcode
		remove_shortcode( $checkout_shortcode_tag );
		add_shortcode( $checkout_shortcode_tag, array( $this, 'output_checkout_shortcode_wrapper' ) );
	}

	/**
	 * Get the shortcode wrapper attributes.
	 */
	public function get_shortcode_wrapper_attributes() {
		return array(
			'before' => '<div class="fc-content ' . esc_attr( apply_filters( 'fc_content_section_class', '' ) ) . '"><div class="woocommerce">',
			'after'  => '</div></div>',
		);
	}

	/**
	 * Output the checkout shortcode contents with a wrapper `fc-content` element around it, for when the custom checkout page template is disabled.
	 */
	public function output_checkout_shortcode_wrapper( $attributes ) {
		// Bail if not on checkout page
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) {
			// Output the checkout shortcode contents without a wrapper
			return WC_Shortcodes::shortcode_wrapper( array( 'WC_Shortcode_Checkout', 'output' ), $attributes );
		}

		// Output the checkout shortcode contents with a wrapper
		return WC_Shortcodes::shortcode_wrapper( array( 'WC_Shortcode_Checkout', 'output' ), $attributes, $this->get_shortcode_wrapper_attributes() );
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
	 * Define wheter using distraction free header and footer templates.
	 *
	 * @return  boolean  `true` when using distraction free header and footer templates on the checkout page, `false` otherwise.
	 */
	public function is_distraction_free_header_footer_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Bail if not showing the checkout form
		// - registration at checkout not enabled
		// - registration is required to checkout
		// - user is not logged in
		if ( ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() && ! is_user_logged_in() ) { return false; }

		// Return `true` when distraction free header and footer is enabled
		return 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_hide_site_header_footer_at_checkout' );
	}

	/**
	 * Define wheter using distraction free header and footer templates.
	 *
	 * @return  boolean  `true` when using distraction free header and footer templates on the checkout page, `false` otherwise.
	 * 
	 * @deprecated       Use `FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout()` instead.
	 */
	public function get_hide_site_header_footer_at_checkout() {
		// Add deprecation notice
		wc_doing_it_wrong( __FUNCTION__, 'Use FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() instead.', '3.0.4' );

		return $this->is_distraction_free_header_footer_checkout();
	}



	/**
	 * Output the checkout header.
	 */
	public function output_checkout_header() {
		// Bail if not using distraction free header and footer
		if ( ! $this->is_distraction_free_header_footer_checkout() ) { return; }

		wc_get_template(
			'checkout/checkout-header.php',
			array( 'checkout' => WC()->checkout() )
		);
	}

	/**
	 * Output the checkout footer.
	 */
	public function output_checkout_footer() {
		// Bail if not using distraction free header and footer
		if ( ! $this->is_distraction_free_header_footer_checkout() ) { return; }

		// Bail if nothing was added to the footer
		if ( ! has_action( 'fc_checkout_footer_widgets' ) || ! ( is_active_sidebar( 'fc_checkout_footer' ) || has_action( 'fc_checkout_footer_widgets_inside_before' ) || has_action( 'fc_checkout_footer_widgets_inside_after' ) ) ) { return; }

		wc_get_template( 'checkout/checkout-footer.php' );
	}

}

FluidCheckout_CheckoutPageTemplate::instance();

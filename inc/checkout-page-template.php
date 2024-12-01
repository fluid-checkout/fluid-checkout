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
	 * Check whether the feature is enabled or not.
	 */
	public function is_feature_enabled() {
		// Return whether the feature is enabled or not.
		// Use comparison to `true` to ensure a boolean value is returned.
		return true === apply_filters( 'fc_enable_checkout_page_template', true );
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );
		add_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shortcode wrapper
		add_action( 'wp', array( $this, 'maybe_setup_checkout_shortcode_wrapper' ), 10 );
		add_filter( 'fc_enable_checkout_shortcode_wrapper', array( $this, 'maybe_enable_checkout_shortcode_wrapper' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Template parts
		$this->template_parts_hooks();
	}

	/**
	 * Add or remove template parts hooks.
	 */
	public function template_parts_hooks() {
          // Checkout header and footer
          add_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
          add_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Checkout page template
		remove_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );
		remove_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 100 );

		// Template file loader
		remove_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100 );

		// Shortcode wrapper
		remove_action( 'wp', array( $this, 'maybe_setup_checkout_shortcode_wrapper' ), 10 );

		// Checkout header and footer
		remove_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
		remove_action( 'fc_checkout_footer', array( $this, 'output_checkout_footer' ), 100 );
	}

	/**
	 * Disable custom template for the checkout page content in some cases.
	 */
	public function maybe_disable_checkout_page_template( $is_enabled ) {
		// Disable if not using distraction free header and footer, but using the full site editor (FSE).
		if ( ! $this->is_distraction_free_header_footer_checkout() && current_theme_supports( 'block-templates' ) ) { return false; }

		// Disable if on order pay page
		if ( is_checkout_pay_page() || is_wc_endpoint_url( 'order-pay' ) ) { return false; }

		// Disable if on order received page
		if ( is_order_received_page() || is_wc_endpoint_url( 'order-received' ) ) { return false; }

		// Otherwise, make no changes.
		return $is_enabled;
	}



	/**
	 * Setup shortcode wrapper for the checkout shortcode, for when the custom checkout page template is disabled.
	 */
	public function maybe_setup_checkout_shortcode_wrapper() {
		// Bail if feature is enabled
		if ( $this->is_feature_enabled() ) { return; }

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
		// Maybe output the checkout shortcode contents with a `fc-content` wrapper
		if ( true === apply_filters( 'fc_enable_checkout_shortcode_wrapper', false ) ) {
			return WC_Shortcodes::shortcode_wrapper( array( 'WC_Shortcode_Checkout', 'output' ), $attributes, $this->get_shortcode_wrapper_attributes() );
		}

		// Output the checkout shortcode contents without any wrapper
		return WC_Shortcodes::shortcode_wrapper( array( 'WC_Shortcode_Checkout', 'output' ), $attributes );
	}

	/**
	 * Maybe enable the checkout shortcode wrapper on the checkout page.
	 *
	 * @param   bool  $enable_wrapper  Whether to enable the checkout shortcode wrapper.
	 */
	public function maybe_enable_checkout_shortcode_wrapper( $enable_wrapper ) {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $enable_wrapper; }
		
		// Otherwise, enable the wrapper
		return true;
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		// Bail if feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return $template; }

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
		// Bail if feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return $template; }

		// Bail if not on checkout page.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $template; }

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
		if ( ! is_user_logged_in() && ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() ) { return false; }

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

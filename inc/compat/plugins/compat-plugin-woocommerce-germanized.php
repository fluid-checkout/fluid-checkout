<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Germanized for WooCommerce (by vendidero).
 */
class FluidCheckout_WooCommerceGermanized extends FluidCheckout {

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

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 1600, 3 ); // Priority needs to be higher than that used by Germanized (1500)

		// Place order position
		add_filter( 'pre_option_fc_checkout_place_order_position', array( $this, 'change_place_order_position_option' ), 10, 3 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if Germanized functions are not available
		if ( ! function_exists( 'wc_gzd_get_hook_priority' ) ) { return; }

		// Payment
		remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title', 10 );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 );

		// Order summary products
		remove_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement', 10 );
		remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal', 10 );
		add_action( 'woocommerce_review_order_before_cart_contents', array( $this,'do_action_woocommerce_gzd_review_order_before_cart_contents' ), 10 );

		// Legal checkboxes
		remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
		remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );
		add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
		add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_checkout_set_terms_manually', 20 );

		// Display back to cart button
		if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) === 'yes' ) {
			remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart', 10 );
			add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'output_gzd_template_checkout_back_to_cart' ), 10 );
		}

		// Add products table highlight background color custom styles
		add_action( 'wp_enqueue_scripts', array( $this, 'add_product_table_background_inline_styles' ), 30 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_shipping' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_billing' ), 10 );
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/plugins/woocommerce-germanized/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

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
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Change the option for the place order position to always `below_order_summary` when using Germanized.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function change_place_order_position_option( $pre_option, $option, $default ) {
		return 'below_order_summary';
	}



	/**
	 * Execute actions from the Germanized template `review-order-product-table.php` for compatibility.
	 */
	public function do_action_woocommerce_gzd_review_order_before_cart_contents() {
		do_action( 'woocommerce_gzd_review_order_before_cart_contents' );
	}



	/**
	 * Fix columns count for the Germanized template function `woocommerce_gzd_template_checkout_back_to_cart` for compatibility.
	 */
	public function output_gzd_template_checkout_back_to_cart() {
		// Get html generated from the target function
		ob_start();
		woocommerce_gzd_template_checkout_back_to_cart();
		$html = ob_get_clean();

		// Fix column count
		$html = str_replace( 'colspan="5"', 'colspan="2"', $html );

		echo $html; // XSS ok
	}



	/**
	 * Adds checkout products table background highlight color as inline css.
	 */
	public function add_product_table_background_inline_styles() {
		$color = get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '#f3f3f3';
		$custom_css = "body.woocommerce-checkout .fc-wrapper .shop_table { background-color: $color; }";
		wp_add_inline_style( 'woocommerce-gzd-layout', $custom_css );
	}



	/**
	 * Add extra fields to skip for the substep review text by address type.
	 *
	 * @param   array   $skip_list     List of fields to skip adding to the substep review text.
	 * @param   string  $address_type  The address type.
	 */
	public function change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, $address_type ) {
		$skip_list[] = $address_type . '_title';
		return $skip_list;
	}

	/**
	 * Add shipping extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	public function change_substep_text_extra_fields_skip_list_shipping( $skip_list ) {
		return $this->change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, 'shipping' );
	}

	/**
	 * Add billing extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	public function change_substep_text_extra_fields_skip_list_billing( $skip_list ) {
		return $this->change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, 'billing' );
	}

}

FluidCheckout_WooCommerceGermanized::instance();

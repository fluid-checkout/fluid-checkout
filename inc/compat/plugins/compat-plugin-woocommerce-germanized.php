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

		// Germanized thumbnails
		add_filter( 'woocommerce_gzd_checkout_use_legacy_table_replacement_template', '__return_false', 10 );
		add_filter( 'pre_option_woocommerce_gzd_display_checkout_thumbnails', array( $this, 'change_germanized_display_checkout_thumbnails_option' ), 10, 3 );

		// Coupon as vouchers
		add_filter( 'woocommerce_coupon_discount_amount_html', array( $this, 'maybe_change_voucher_coupon_discount_amount_html' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'maybe_change_voucher_coupon_label' ), 10, 3 );

		// Pickup location
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_pickup_location' ), 10 );
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

		// Place order
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_gzd_template_order_submit_fallback', 50 );
		add_action( 'fc_place_order', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );

		// Display back to cart button
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) ) {
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
	 * Change the option for displaying the Germanized enhanced checkout thumbnails to `no`.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function change_germanized_display_checkout_thumbnails_option( $pre_option, $option, $default ) {
		return 'no';
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
		// Get color from settings
		$color = FluidCheckout_Settings::instance()->get_option( 'woocommerce_gzd_display_checkout_table_color' );
		$color_esc = ! empty( $color ) ? esc_attr( $color ) : '#f3f3f3';

		// TODO: Use CSS variables to set background color
		$custom_css = "body.woocommerce-checkout .fc-wrapper .shop_table { background-color: $color_esc; }";
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



	/**
	 * Register voucher coupon validation for this plugin. 
	 */
	public function register_coupon_validation_filters() {
		// Bail if class is not available
		if ( ! class_exists( 'WC_GZD_Coupon_Helper' ) ) { return; }

		add_filter( 'woocommerce_coupon_is_valid_for_product', array( WC_GZD_Coupon_Helper::instance(), 'is_valid_for_product_filter' ), 1000, 3 );
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( WC_GZD_Coupon_Helper::instance(), 'is_valid' ), 1000, 2 );
		add_filter( 'woocommerce_coupon_get_free_shipping', array( WC_GZD_Coupon_Helper::instance(), 'is_valid_free_shipping_filter' ), 1000, 2 );
	}

	/**
	 * Unregister voucher coupon validation for this plugin.
	 */
	public function unregister_coupon_validation_filters() {
		// Bail if class is not available
		if ( ! class_exists( 'WC_GZD_Coupon_Helper' ) ) { return; }

		remove_filter( 'woocommerce_coupon_is_valid_for_product', array( WC_GZD_Coupon_Helper::instance(), 'is_valid_for_product_filter' ), 1000 );
		remove_filter( 'woocommerce_coupon_is_valid_for_cart', array( WC_GZD_Coupon_Helper::instance(), 'is_valid' ), 1000 );
		remove_filter( 'woocommerce_coupon_get_free_shipping', array( WC_GZD_Coupon_Helper::instance(), 'is_valid_free_shipping_filter' ), 1000 );
	}

	/**
	 * Check whether a coupon is a voucher from this plugin.
	 * 
	 * @param  WC_Coupon|string   $coupon   Coupon object or coupon code.
	 *
	 * @see    WC_GZD_Coupon_Helper::coupon_is_voucher()
	 */
	public function coupon_is_voucher( $coupon ) {
		if ( is_string( $coupon ) ) {
			$coupon = new WC_Coupon( $coupon );
		}

		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_gzd_coupon_is_voucher', ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ), $coupon );
	}



	/**
	 * Maybe change the amount HTML for the coupon for voucher coupons from this plugin.
	 *
	 * @param   string     $discount_amount_html   The HTML for the discount amount.
	 * @param   WC_Coupon  $coupon                 The coupon object.
	 */
	public function maybe_change_voucher_coupon_discount_amount_html( $discount_amount_html, $coupon ) {
		// Bail if not a voucher coupon
		if ( ! $this->coupon_is_voucher( $coupon ) ) { return $discount_amount_html; }

		// Get coupon code
		$coupon_code = is_string( $coupon ) ? $coupon : $coupon->get_code();

		// Get the voucher discount amount
		$this->unregister_coupon_validation_filters();
		$discounts = new WC_GZD_Voucher_Discounts( WC()->cart, $coupon );
		$discounts->apply_coupon( $coupon, false );
		$total_discounts = $discounts->get_discounts_by_coupon();
		$this->register_coupon_validation_filters();

		// Bail if coupon is not available in the discounts totals
		if ( ! array_key_exists( $coupon_code, $total_discounts ) ) { return $discount_amount_html; }

		// Get coupon discount and convert to negative value for presentation
		$coupon_discount_amount = $total_discounts[ $coupon_code ] * -1;

		// Get the voucher amount HTML
		$discount_amount_html = wc_price( $coupon_discount_amount );

		return $discount_amount_html;
	}

	/**
	 * Maybe change the label for the coupon for voucher coupons from this plugin.
	 *
	 * @param   string     $label    The coupon label.
	 * @param   string     $coupon   The coupon object.
	 */
	public function maybe_change_voucher_coupon_label( $label, $coupon ) {
		// Bail if not a voucher coupon
		if ( ! $this->coupon_is_voucher( $coupon ) ) { return $label; }

		// Get the voucher label
		$label = apply_filters( 'woocommerce_gzd_voucher_name', sprintf( __( 'Voucher: %1$s', 'woocommerce-germanized' ), $coupon->get_code() ), $coupon->get_code() );

		return $label;
	}



	/**
	 * Prevent hiding pickup location optional fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_pickup_location( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'billing_pickup_location_notice',
			'shipping_pickup_location_notice',
			'pickup_location_customer_number',
			'current_pickup_location',
		) );
		return $skip_list;
	}

}

FluidCheckout_WooCommerceGermanized::instance();

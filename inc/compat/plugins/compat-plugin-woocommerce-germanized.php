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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

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
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'add_persisted_data_skip_fields' ), 10, 2 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_move_pickup_selection_fields_to_shipping_address' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'reset_current_location_field_value' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_set_address_fields_as_readonly' ), 100 );
		add_filter( 'fc_shipping_same_as_billing_field_keys', array( $this, 'remove_pickup_location_from_copy_billing_field_keys' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_pickup_location_data_session_value' ), 10 );

		// Shipping address review text
		add_filter( 'fc_shipping_substep_text_address_data', array( $this, 'remove_customer_number_from_text_address_data' ), 10 );
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'add_pickup_location_field_step_review_text_skip_list' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'add_substep_text_lines_shipping_address' ), 10 );

		// Add hidden fields
		add_action( 'woocommerce_checkout_shipping', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_address', array( $this, 'maybe_set_substep_incomplete_shipping_address' ), 10 );

		// Add substep fragments
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_hidden_fields_fragment' ), 10 );
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
		remove_action( 'fc_place_order', array( FluidCheckout_Steps::instance(), 'output_checkout_place_order' ), 10, 2 );
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

		// Pickup location
		add_filter( 'woocommerce_shiptastic_shipment_customer_pickup_location_code', array( $this, 'maybe_replace_current_pickup_location_code' ), 100 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-woocommerce-germanized', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-germanized/checkout-woocommerce-germanized' ), array( 'jquery', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-woocommerce-germanized', 'window.addEventListener("load",function(){CheckoutWooCommerceGermanized.init(fcSettings.checkoutWooCommerceGermanized);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-woocommerce-germanized', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-germanized/checkout-validation-woocommerce-germanized' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-woocommerce-germanized', 'window.addEventListener("load",function(){CheckoutValidationWooCommerceGermanized.init(fcSettings.checkoutValidationWooCommerceGermanized);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-woocommerce-germanized' );
		wp_enqueue_script( 'fc-checkout-validation-woocommerce-germanized' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationWooCommerceGermanized' ] = array(
			'validationMessages'  => array(
				'invalid_customer_number' => __( 'Sorry, your pickup location customer number is invalid.', 'shipments', 'woocommerce-germanized' ),
			),
		);

		return $settings;
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
			'pickup_location_address',
			'pickup_location'
		) );
		return $skip_list;
	}



	/**
	 * Add pickup location fields from the popup to the persisted data skip list.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function add_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		$rede_fields_keys = array(
			'pickup_location',
			'pickup_location_address',
			'pickup_location_postcode',
		);

		return array_merge( $skip_field_keys, $rede_fields_keys );
	}



	/**
	 * Get the current shipping address on checkout page.
	 *
	 * @return  array  The current shipping address.
	 */
	public function get_current_checkout_shipping_address() {
		$address = array(
			'country'   => WC()->checkout->get_value( 'shipping_country' ),
			'state'     => WC()->checkout->get_value( 'shipping_state' ),
			'city'      => WC()->checkout->get_value( 'shipping_city' ),
			'postcode'  => WC()->checkout->get_value( 'shipping_postcode' ),
			'address_1' => WC()->checkout->get_value( 'shipping_address_1' ),
		);
		return $address;
	}

	/**
	 * Get the current shipping method provider.
	 *
	 * @return  object  The shipping provider instance.
	 */
	public function get_current_shipping_method_provider() {
		// Get current shipping address
		$shipping_address = $this->get_current_checkout_shipping_address();

		// Bail if required functions are not available
		if ( ! function_exists( 'wc_stc_get_current_shipping_provider_method' ) || ! method_exists( 'Vendidero\Shiptastic\PickupDelivery', 'get_pickup_delivery_cart_args' ) ) { return; }

		// Get shipping method
		$shipping_method = wc_stc_get_current_shipping_provider_method();

		// Bail if shipping method is not set or its method is not available
		if ( ! $shipping_method || ! method_exists( $shipping_method, 'get_shipping_provider_instance' ) ) { return; }
		
		// Get shipping provider instance
		$provider = $shipping_method->get_shipping_provider_instance();

		// Bail if provider is not set or doesn't support pickup location delivery
		$query_args = Vendidero\Shiptastic\PickupDelivery::get_pickup_delivery_cart_args();
		if ( ! is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) || ! $provider->supports_pickup_location_delivery( $shipping_address, $query_args ) ) { return; }

		return $provider;
	}



	/**
	 * Maybe move the pickup location selection fields to the shipping address section.
	 *
	 * @param   array  $fields  The billing fields.
	 */
	public function maybe_move_pickup_selection_fields_to_shipping_address( $fields ) {
		// Initialize variables
		$notice_field_key = 'billing_pickup_location_notice';
		$pickup_location_field_key = 'current_pickup_location';
		$pickup_location_field_sections = array( 'order', 'billing' ); // The field can be located in both billing and order comments sections depending on the plugin settings

		// Maybe remove the notice field
		if ( isset( $fields[ 'billing' ][ $notice_field_key ] ) ) {
			unset( $fields[ 'billing' ][ $notice_field_key ] );
		}

		// Move the pickup location field to the shipping address if exists in the order comments or billing address sections
		foreach ( $pickup_location_field_sections as $field_group ) {
			if ( isset( $fields[ $field_group ][ $pickup_location_field_key ] ) ) {
				$fields[ 'shipping' ][ $pickup_location_field_key ] = $fields[ $field_group ][ $pickup_location_field_key ];
				unset( $fields[ $field_group ][ $pickup_location_field_key ] );
				break;
			}
		}

		return $fields;
	}



	/**
	 * Reset the current location field value to empty.
	 * By default, the plugin gets the field value from the customer meta data, which overrides the session value restored by Fluid Checkout.
	 *
	 * @param   array  $fields  The checkout fields.
	 */
	public function reset_current_location_field_value( $fields ) {
		// Initialize variables
		$field_group_key = 'shipping';
		$field_key = 'current_pickup_location';

		// Bail if field is not set
		if ( ! isset( $fields[ $field_group_key ][ $field_key ] ) ) { return $fields; }

		// Get current pickup location code from session
		$pickup_location_code = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( $field_key );

		// Reset field value
		$fields[ $field_group_key ][ $field_key ][ 'value' ] = $pickup_location_code;

		// Maybe reset the the custom attribute to prevent JS errors
		if ( ! $pickup_location_code ) {
			$fields[ $field_group_key ][ $field_key ][ 'current_location' ] = '';
		}

		return $fields;
	}



	/**
	 * Maybe set address fields as readonly.
	 * By default, the plugin sets the field as readonly through JS.
	 *
	 * @param   array  $fields  The checkout fields.
	 */
	public function maybe_set_address_fields_as_readonly( $fields ) {
		// Initialize variables
		$field_group_key = 'shipping';
		$current_location_field_key = 'current_pickup_location';
		
		// Get shipping method provider
		$provider = $this->get_current_shipping_method_provider();

		// Bail if provider is not set or doesn't support pickup location delivery
		if ( ! is_object( $provider ) || ! method_exists( $provider, 'get_pickup_location_by_code' ) ) { return $fields; }

		// Get current pickup location
		$pickup_location_code = WC()->checkout->get_value( $current_location_field_key );
		$current_location = $provider->get_pickup_location_by_code( $pickup_location_code );

		// Bail if current location is not available
		if ( ! is_object( $current_location ) || ! method_exists( $current_location, 'get_address_replacement_map' ) ) { return $fields; }

		// Set fields mapped to the current pickup location as readonly
		$fields_to_disable = $current_location->get_address_replacement_map();
		foreach ( $fields_to_disable as $field_key => $field ) {
			// Add missing prefix to the field key
			$field_key = $field_group_key . '_' . $field_key;

			// Skip if field is not available
			if ( ! isset( $fields[ $field_group_key ][ $field_key ] ) ) { continue; }

			// Maybe create array of custom attributes for the field
			if ( ! array_key_exists( 'custom_attributes', $fields[ $field_group_key ][ $field_key ] ) ) {
				$fields[ $field_group_key ][ $field_key ][ 'custom_attributes' ] = array();
			}

			// Set the field as readonly
			$fields[ $field_group_key ][ $field_key ][ 'custom_attributes' ][ 'readonly' ] = 'readonly';
		}

		return $fields;
	}



	/**
	 * Remove pickup location fields from "shipping same as billing" field list.
	 * This ensures that the fields appear in the same position as they do in the original plugin.
	 *
	 * @param   array  $field_keys  The shipping address field keys.
	 */
	public function remove_pickup_location_from_copy_billing_field_keys( $field_keys ) {
		$field_keys = array_merge( $field_keys, array(
			'shipping_pickup_location_notice',
			'pickup_location_customer_number',
		) );

		return $field_keys;
	}



	/**
	 * Set the pickup location data session value.
	 *
	 * @param   array  $posted_data  The posted data.
	 */
	public function maybe_set_pickup_location_data_session_value( $posted_data ) {
		$field_key = 'current_pickup_location';

		// Bail if the field value isn't available
		if ( ! isset( $posted_data[ $field_key ] ) ) { return $posted_data; }

		// Set the field value to session
		FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $field_key, $posted_data[ $field_key ] );

		return $posted_data;
	}



	/**
	 * Maybe replace the current pickup location code with the one from session.
	 * This is needed to avoid the field value being set to the customer meta value saved by the plugin.
	 *
	 * @param   string  $pickup_location_code  The pickup location code.
	 */
	public function maybe_replace_current_pickup_location_code( $pickup_location_code ) {
		// Initialize variables
		$field_key = 'current_pickup_location';

		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $pickup_location_code; }

		// Get the current location code from the session
		$pickup_location_code = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( $field_key );

		return $pickup_location_code;
	}



	/**
	 * Remove the customer number field from the address data in substep review text.
	 *
	 * @param   array  $address_data  The address data.
	 */
	public function remove_customer_number_from_text_address_data( $address_data ) {
		// Bail if not an array
		if ( ! is_array( $address_data ) ) { return $address_data; }

		// Bail if field is not set
		if ( ! isset( $address_data[ 'pickup_location_customer_number' ] ) ) { return $address_data; }

		// Remove customer number field from the address data
		unset( $address_data[ 'pickup_location_customer_number' ] );

		return $address_data;
	}

	/**
	 * Add pickup location fields to the substep review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  Array with all fields to skip.
	 */
	public function add_pickup_location_field_step_review_text_skip_list( $field_keys_skip_list ) {
		// Bail if not an array
		if ( ! is_array( $field_keys_skip_list ) ) { return $field_keys_skip_list; }

		$field_keys_skip_list = array_merge( $field_keys_skip_list, array(
			'current_pickup_location',
			'pickup_location_customer_number',
		) );
		
		return $field_keys_skip_list;
	}

	/**
	 * Add the shipping adddress substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_address( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get customer number field value
		$customer_number = WC()->checkout->get_value( 'pickup_location_customer_number' );

		// Maybe add review text lines
		if ( ! empty( $customer_number ) ) {
			$review_text_lines[] = '<strong>' . _x( 'Customer Number', 'shipments', 'woocommerce-germanized' ) . '</strong>';
			$review_text_lines[] = esc_html( $customer_number );
		}

		return $review_text_lines;
	}



	/**
	 * Check if the customer number is valid.
	 *
	 * @param   string  $customer_number  Customer number to validate.
	 */
	public function is_customer_number_valid( $customer_number ) {
		// Initialize variables
		$is_valid = true;
		$current_location_field_key = 'current_pickup_location';

		// Get shipping method provider
		$provider = $this->get_current_shipping_method_provider();

		// Bail if provider is not set or doesn't support pickup location delivery
		if ( ! is_object( $provider ) || ! method_exists( $provider, 'get_pickup_location_by_code' ) ) { return $is_valid; }

		// Get current pickup location
		$pickup_location_code = WC()->checkout->get_value( $current_location_field_key );
		$current_location = $provider->get_pickup_location_by_code( $pickup_location_code );

		// Bail if current location object or its methods are not available
		if ( ! is_object( $current_location ) || ! method_exists( $current_location, 'customer_number_is_valid' ) || ! method_exists( $current_location, 'customer_number_is_mandatory' ) ) { return $is_valid; }

		// Bail if customer number is not mandatory and empty
		if ( ! $current_location->customer_number_is_mandatory() && empty( $customer_number ) ) { return $is_valid; }

		$is_valid = $current_location->customer_number_is_valid( $customer_number );
		
		return $is_valid;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get entered customer number
		$customer_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'pickup_location_customer_number' );

		// Check if customer number is valid
		$is_valid = $this->is_customer_number_valid( $customer_number );

		// Output custom hidden fields
		echo '<div id="germanized-custom_checkout_fields" class="form-row fc-no-validation-icon germanized-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="germanized-customer-number-is_valid" name="germanized-customer-number-is_valid" value="'. esc_attr( $is_valid ) .'" class="validate-germanized-customer-number">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Add hidden fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_hidden_fields_fragment( $fragments ) {
		// Get custom hidden fields HTML
		ob_start();
		$this->output_custom_hidden_fields();
		$html = ob_get_clean();

		// Add fragment
		$fragments[ '.germanized-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Maybe set the shipping address substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get entered customer number
		$customer_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'pickup_location_customer_number' );

		// Check if customer number is valid
		$is_valid = $this->is_customer_number_valid( $customer_number );

		// Maybe set step as incomplete
		if ( ! $is_valid ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}

}

FluidCheckout_WooCommerceGermanized::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage access to plugin settings.
 */
class FluidCheckout_Settings extends FluidCheckout {

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
		// Settings values
		add_filter( 'pre_option_fc_design_template', array( $this, 'set_option_lite_design_template' ), 10, 3 );
		add_filter( 'pre_option_fc_pro_checkout_edit_cart_replace_edit_cart_link', array( $this, 'set_option_replace_edit_cart_link' ), 10, 3 );
		add_filter( 'pre_option_fc_pro_checkout_coupon_codes_position', array( $this, 'set_option_coupon_code_position_checkout' ), 10, 3 );
	}



	/**
	 * Get the default values for all options.
	 */
	public function get_default_option_values() {
		$defaults = array(
			// Settings without options in the admin panel.
			'fc_apply_checkout_field_args'                                  => 'yes',
			'fc_enable_checkout_validation'                                 => 'yes',

			// Settings with options in the admin panel.
			'fc_checkout_layout'                                            => 'multi-step',
			'fc_design_template'                                            => 'classic',
			'fc_enable_dark_mode_styles'                                    => 'no',
			'fc_hide_site_header_footer_at_checkout'                        => 'yes',
			'fc_checkout_logo_image'                                        => '',
			'fc_checkout_header_background_color'                           => null,
			'fc_checkout_page_background_color'                             => null,
			'fc_checkout_footer_background_color'                           => null,
			'fc_enable_checkout_progress_bar'                               => 'yes',
			'fc_enable_checkout_sticky_progress_bar'                        => 'yes',
			'fc_enable_checkout_express_checkout'                           => 'no',
			'fc_enable_checkout_express_checkout_inline_buttons'            => 'no',
			'fc_checkout_order_review_highlight_color'                      => null,
			'fc_enable_checkout_sticky_order_summary'                       => 'yes',
			'fc_pro_checkout_edit_cart_replace_edit_cart_link'              => 'edit_cart_link',
			'fc_pro_enable_checkout_edit_cart'                              => 'no',
			'fc_pro_cart_items_error_messages_hide_at_checkout'             => 'yes',
			'fc_checkout_place_order_position'                              => 'below_payment_section',
			'fc_enable_checkout_widget_areas'                               => 'yes',
			'fc_enable_checkout_widget_area_sidebar_last_step'              => 'no',
			'fc_enable_checkout_hide_optional_fields'                       => 'yes',
			'fc_optional_fields_link_label_lowercase'                       => 'yes',
			'fc_hide_optional_fields_skip_address_2'                        => 'no',
			'fc_shipping_methods_substep_position'                          => 'after_shipping_address',
			'fc_enable_checkout_local_pickup'                               => 'no',
			'fc_show_shipping_section_highlighted'                          => 'yes',
			'fc_show_billing_section_highlighted'                           => 'yes',
			'fc_default_to_billing_same_as_shipping'                        => 'yes',
			'fc_shipping_phone_field_visibility'                            => 'no',
			'fc_shipping_phone_field_position'                              => 'shipping_address',
			'woocommerce_checkout_phone_field'                              => 'required',
			'fc_billing_phone_field_position'                               => 'billing_address',
			'fc_pro_enable_international_phone_fields'                      => 'no',
			'fc_pro_enable_international_phone_validation'                  => 'no',
			'fc_pro_enable_international_phone_country_code'                => 'yes',
			'fc_pro_international_phone_fields_placeholder'                 => 'off',
			'woocommerce_enable_order_comments'                             => 'yes',
			'fc_enable_checkout_gift_options'                               => 'no',
			'fc_default_gift_options_expanded'                              => 'no',
			'fc_display_gift_message_in_order_details'                      => 'no',
			'fc_enable_checkout_coupon_codes'                               => 'yes',
			'fc_display_coupon_code_section_title'                          => 'no',
			'fc_pro_checkout_coupon_codes_position'                         => 'substep_before_payment',
			'fc_pro_checkout_coupon_code_message_button_style'              => 'add_link_button',
			'fc_pro_enable_account_matching'                                => 'no',
			'fc_pro_account_matching_display_account_exists_message'        => 'yes',

			// Deprecated settings.
			'fc_enable_checkout_place_order_sidebar'                        => 'no',
		);

		return apply_filters( 'fc_default_option_values', $defaults );
	}



	/**
	 * Get the default value for a specific option.
	 * 
	 * @param  string  $option  Option name.
	 */
	public function get_option_default( $option ) {
		$defaults = $this->get_default_option_values();
		return array_key_exists( $option, $defaults ) ? $defaults[ $option ] : null;
	}

	/**
	 * Get the value for a specific option. Returns default if option value is not set.
	 * 
	 * @param  string  $option   Option name.
	 * @param  mixed   $default  The fallback value to return if the option does not exist.
	 */
	public function get_option( $option, $default = null ) {
		// Maybe get default from the default values array.
		if ( null === $default ) {
			$default = $this->get_option_default( $option );
		}

		return get_option( $option, $default );
	}



	/**
	 * Force the option value for design template when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_lite_design_template( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_design_template' );
	}

	/**
	 * Force the option value for replacing edit cart link when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_replace_edit_cart_link( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_pro_checkout_edit_cart_replace_edit_cart_link' );
	}

	/**
	 * Force the option value for coupon code sectin position on checkout when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_coupon_code_position_checkout( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_pro_checkout_coupon_codes_position' );
	}

}

FluidCheckout_Settings::instance();

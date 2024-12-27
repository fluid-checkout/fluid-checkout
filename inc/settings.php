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
		add_filter( 'pre_option_fc_pro_checkout_billing_address_position', array( $this, 'set_option_billing_address_position_checkout' ), 10, 3 );

		// Settings save
		add_action( 'woocommerce_admin_settings_sanitize_option', array( $this, 'maybe_prevent_change_disabled_settings_on_save' ), 10, 3 );
	}



	/**
	 * Get the default values for all options.
	 */
	public function get_default_option_values() {
		$defaults = array(
			// Settings checkout.			
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
			'fc_enable_checkout_express_checkout_ignore_required_fields'    => 'yes',
			'fc_checkout_order_review_highlight_color'                      => null,
			'fc_show_order_totals_row_highlighted'                          => 'no',
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
			'fc_shipping_methods_disable_auto_select'                       => 'no',
			'fc_enable_checkout_local_pickup'                               => 'no',
			'fc_local_pickup_display_clear_shipping_methods_button'         => 'no',
			'fc_local_pickup_save_shipping_address'                         => 'no',
			'fc_show_shipping_section_highlighted'                          => 'yes',
			'fc_pro_checkout_billing_address_position'                      => 'step_after_shipping',
			'fc_show_billing_section_highlighted'                           => 'yes',
			'fc_default_to_billing_same_as_shipping'                        => 'yes',
			'fc_shipping_company_field_visibility'                          => 'optional',
			'fc_shipping_phone_field_visibility'                            => 'no',
			'fc_shipping_phone_field_position'                              => 'shipping_address',
			'woocommerce_checkout_phone_field'                              => 'required',
			'fc_billing_phone_field_position'                               => 'billing_address',
			'fc_pro_enable_international_phone_fields'                      => 'no',
			'fc_pro_enable_international_phone_validation'                  => 'no',
			'fc_pro_enable_international_phone_country_code'                => 'yes',
			'fc_pro_enable_international_phone_country_list_filter'         => 'yes',
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

			// Settings cart.
			'fc_pro_enable_cart_page'                                       => 'no',
			'fc_pro_hide_site_header_footer_at_cart'                        => 'no',
			'fc_pro_enable_cart_sticky_order_summary'                       => 'yes',
			'fc_pro_cart_section_position_shipping'                         => 'inside_order_summary',
			'fc_pro_cart_section_position_coupon_code'                      => 'inside_cart_items',
			'fc_pro_cart_section_position_cross_sells'                      => 'after_cart_items',
			'fc_pro_enable_cart_cross_sells'                                => 'yes',
			'fc_pro_cart_cross_sells_display_items_limit'                   => 2,
			'fc_pro_enable_cart_widget_areas'                               => 'yes',
			
			// Settings order received.
			'fc_pro_enable_order_received'                                  => 'no',
			'fc_pro_enable_order_details_email_customizations'              => 'yes',
			'fc_pro_enable_order_details_wide_layout'                       => 'no',
			'fc_pro_order_details_order_actions_position'                   => 'inside_order_overview',
			'fc_pro_enable_order_details_order_status_progress_bar'         => 'no',
			'fc_pro_order_details_order_summary_position'                   => 'inside_order_items',
			'fc_pro_order_details_order_downloads_position'                 => 'inside_order_items',
			'fc_pro_order_details_gift_message_position'                    => 'before_order_items',
			'fc_pro_order_details_order_notes_position'                     => 'inside_order_items',
			'fc_pro_enable_order_received_widget_areas'                     => 'no',
			
			// Settings order pay.
			'fc_pro_enable_pay_received'                                    => 'no',
			'fc_pro_enable_order_pay_widget_areas'                          => 'yes',

			// Settings tools.
			'fc_debug_mode'                                                 => 'no',
			'fc_load_unminified_assets'                                     => 'no',
			'fc_use_enhanced_select_components'                             => 'no',
			'fc_fix_zoom_in_form_fields_mobile_devices'                     => 'yes',

			// Settings without options in the admin panel.
			'fc_plugin_activation_time'                                     => null,
			'fc_apply_checkout_field_args'                                  => 'yes',
			'fc_enable_checkout_validation'                                 => 'yes',
			'fc_show_account_creation_notice_checkout_contact_step_text'    => 'true',

			// Compatibility settings for plugins.
			'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step' => 'yes',
			'fc_integration_bluehost_plugin_custom_fields'                  => 'no',
			'fc_integration_captcha_pro_captcha_position'                   => 'before_place_order_section',
			'fc_integration_mailchimp_force_subscribe_checkbox_position'    => 'yes',
			'gm_order_review_checkboxes_before_order_review'                => 'off',
			'hezarfen_checkout_fields_auto_sort'                            => 'no',
			'hezarfen_hide_checkout_postcode_fields'                        => 'no',
			'sg_enable_picker'                                              => 'enable',
			'woocommerce_gzd_display_checkout_back_to_cart_button'          => 'no',
			'woocommerce_gzd_display_checkout_table_color'                  => '#eeeeee',
			'woocommerce_enable_guest_checkout'                             => 'yes',

			// Compatibility settings for themes.
			'fc_compat_theme_atomion_display_order_progress'                => 'no',
			'fc_compat_theme_atomion_display_field_labels'                  => 'yes',
			'fc_compat_theme_impreza_header_spacing'                        => null,
			'fc_compat_theme_zk_nito_display_field_labels'                  => 'no',
			'fc_compat_theme_zk_nito_add_extra_fields'                      => 'no',
			'fc_compat_theme_woodmart_output_checkout_steps_section'        => 'no',
			'fc_compat_theme_woodmart_disable_theme_checkout_options'       => 'yes',
			'fc_compat_theme_pressmart_output_checkout_steps_section'       => 'no',
			'fc_compat_theme_betheme_output_checkout_steps_section'         => 'no',
			'fc_compat_theme_thegem_output_checkout_steps_section'          => 'no',
			'fc_compat_theme_porto_output_checkout_steps_section'           => 'no',
			'fc_compat_theme_kapee_output_checkout_steps_section'           => 'no',
			'fc_compat_theme_go_enable_account_wide_layout'                 => 'yes',
			'fc_compat_theme_fennik_output_breadcrumbs_section'             => 'no',

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
	 * Force the option value for coupon code section position on checkout when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_coupon_code_position_checkout( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_pro_checkout_coupon_codes_position' );
	}

	/**
	 * Force the option value for billing address section position on checkout when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_billing_address_position_checkout( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_pro_checkout_billing_address_position' );
	}



	/**
	 * Maybe prevent changes to the option value when the option is disabled.
	 * 
	 * @param  mixed   $value      The option value.
	 * @param  string  $option     The option arguments.
	 * @param  mixed   $raw_value  The raw value of the option.
	 */
	public function maybe_prevent_change_disabled_settings_on_save( $value, $option, $raw_value ) {
		global $current_tab, $current_section;

		// Bail if not the plugin settings.
		if ( ! 'fc_checkout' === $current_tab ) { return $value; }

		// Maybe set the value to the saved value if the option is disabled.
		if( array_key_exists( 'disabled', $option ) && true === $option[ 'disabled' ] ) {
			$saved_value = get_option( $option[ 'id' ] );
			$value = $saved_value;
		}

		return $value;
	}

}

FluidCheckout_Settings::instance();

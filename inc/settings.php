<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage access to plugin settings.
 *
 * CPT-backed settings profiles with lazy load, wp_cache, and get_option BC via pre_option.
 */
class FluidCheckout_Settings extends FluidCheckout {

	/**
	 * Settings profile post type.
	 */
	const PROFILE_POST_TYPE = 'fc_settings_profile';

	/**
	 * Option key for the active profile slug.
	 */
	const ACTIVE_PROFILE_OPTION = 'fc_settings_active_profile';

	/**
	 * Option key set after legacy individual options are migrated into a CPT profile.
	 */
	const MIGRATION_FLAG_OPTION = 'fc_settings_profiles_migrated';

	/**
	 * Option key for the legacy cleanup admin notice.
	 */
	const CLEANUP_NOTICE_OPTION = 'fc_settings_legacy_cleanup_pending';

	/**
	 * Option key for the Unix timestamp when settings profiles migration completed.
	 */
	const MIGRATION_TIME_OPTION = 'fc_settings_profiles_migrated_time';

	/**
	 * Object cache group.
	 */
	const CACHE_GROUP = 'fluid-checkout';

	/**
	 * Bypass flag for reading the active profile pointer without recursion.
	 *
	 * @var bool
	 */
	private $bypass_pre_option = false;

	/**
	 * Whether pre_option handlers have been registered for the current request.
	 *
	 * @var bool
	 */
	private $pre_option_registered = false;



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
		// CPT
		add_action( 'init', array( $this, 'register_settings_profile_post_type' ), 5 );

		// Migration and pre_option bootstrap
		add_action( 'init', array( $this, 'maybe_migrate_legacy_options' ), 6 );
		add_action( 'init', array( $this, 'maybe_register_pre_option_filters' ), 7 );

		// Settings values forced when only Lite is active
		add_filter( 'pre_option_fc_design_template', array( $this, 'set_option_lite_design_template' ), 10, 3 );
		add_filter( 'pre_option_fc_checkout_column_layout', array( $this, 'set_option_checkout_column_layout' ), 10, 3 );
		add_filter( 'pre_option_fc_checkout_progress_bar_style', array( $this, 'set_option_progress_bar_style' ), 10, 3 );
		add_filter( 'pre_option_fc_pro_checkout_edit_cart_replace_edit_cart_link', array( $this, 'set_option_replace_edit_cart_link' ), 10, 3 );
		add_filter( 'pre_option_fc_pro_checkout_coupon_codes_position', array( $this, 'set_option_coupon_code_position_checkout' ), 10, 3 );
		add_filter( 'pre_option_fc_pro_checkout_billing_address_position', array( $this, 'set_option_billing_address_position_checkout' ), 10, 3 );

		// Settings values (at later stage)
		// Intentionally use `option_` instead of `pre_option_` as we need to check the value of the option that was saved the to database before making any changes to it.
		add_filter( 'option_fc_pro_checkout_order_summary_position_mobile', array( $this, 'set_option_order_summary_position_mobile' ), 10, 2 );

		// Legacy cleanup notice actions
		add_action( 'admin_init', array( $this, 'maybe_handle_legacy_cleanup_action' ), 10 );
	}



	/**
	 * Get the default values for all options.
	 */
	public function get_default_option_values() {
		$cached = wp_cache_get( 'default_option_values', self::CACHE_GROUP );

		// Return cached defaults when available
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$defaults = array(
			// Settings checkout.
			'fc_checkout_layout'                                            => 'multi-step',
			'fc_checkout_column_layout'                                     => 'two_columns',
			'fc_design_template'                                            => 'classic',
			'fc_enable_dark_mode_styles'                                    => 'no',
			'fc_hide_site_header_footer_at_checkout'                        => 'yes',
			'fc_checkout_logo_image'                                        => '',
			'fc_checkout_header_background_color'                           => null,
			'fc_checkout_page_background_color'                             => null,
			'fc_checkout_footer_background_color'                           => null,
			'fc_enable_checkout_progress_bar'                               => 'yes',
			'fc_enable_checkout_sticky_progress_bar'                        => 'yes',
			'fc_checkout_progress_bar_style'                                => 'bars',
			'fc_enable_checkout_express_checkout'                           => 'no',
			'fc_enable_checkout_express_checkout_inline_buttons'            => 'no',
			'fc_enable_checkout_express_checkout_ignore_required_fields'    => 'yes',
			'fc_checkout_order_review_highlight_color'                      => null,
			'fc_checkout_secondary_column_background_color'                 => null,
			'fc_show_order_totals_row_highlighted'                          => 'no',
			'fc_enable_checkout_sticky_order_summary'                       => 'yes',
			'fc_pro_checkout_edit_cart_replace_edit_cart_link'              => 'edit_cart_link',
			'fc_pro_checkout_order_summary_position_mobile'                 => 'site_header',
			'fc_pro_checkout_order_summary_collapsible_initial_state'       => 'collapsed',
			'fc_pro_enable_checkout_edit_cart'                              => 'no',
			'fc_pro_cart_items_error_messages_hide_at_checkout'             => 'yes',
			'fc_checkout_place_order_position'                              => 'below_payment_section',
			'fc_enable_checkout_widget_areas'                               => 'yes',
			'fc_enable_checkout_widget_area_sidebar_last_step'              => 'no',
			'fc_enable_checkout_hide_optional_fields'                       => 'yes',
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
			'woocommerce_checkout_company_field'                            => 'optional',
			'fc_shipping_phone_field_visibility'                            => 'hidden',
			'fc_shipping_phone_field_position'                              => 'shipping_address',
			'woocommerce_checkout_phone_field'                              => 'required',
			'fc_billing_phone_field_position'                               => 'billing_address',
			'fc_pro_enable_international_phone_fields'                      => 'no',
			'fc_pro_enable_international_phone_validation'                  => 'no',
			'fc_pro_enable_international_phone_validation_precise'          => 'no',
			'fc_pro_enable_international_phone_validation_precise_types'    => array( 'MOBILE', 'FIXED_LINE' ),
			'fc_pro_enable_international_phone_country_code'                => 'yes',
			'fc_pro_enable_international_phone_country_list_filter'         => 'yes',
			'fc_pro_international_phone_fields_placeholder'                 => 'OFF',
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
			'fc_pro_enable_order_page_block_based_template'                 => 'no',
			'fc_pro_enable_order_details_wide_layout'                       => 'no',
			'fc_pro_order_details_order_actions_position'                   => 'inside_order_overview',
			'fc_pro_enable_order_details_order_status_progress_bar'         => 'no',
			'fc_pro_order_details_order_summary_position'                   => 'inside_order_items',
			'fc_pro_order_details_order_downloads_position'                 => 'inside_order_items',
			'fc_pro_order_details_gift_message_position'                    => 'before_order_items',
			'fc_pro_order_details_order_notes_position'                     => 'inside_order_items',
			'fc_pro_enable_order_received_widget_areas'                     => 'no',

			// Settings order pay.
			'fc_pro_enable_order_pay'                                      => 'no',
			'fc_pro_enable_order_pay_widget_areas'                          => 'yes',

			// Address book.
			'fc_pro_enable_address_book'                                    => 'no',
			'fc_pro_enable_address_book_address_label'                      => 'yes',

			// VAT Assistant.
			'fc_vat_number_field_visibility'                                => 'no',
			'fc_vat_number_field_label'                                     => '',
			'fc_vat_number_shop'                                            => '',
			'fc_vat_number_eu_vat_validation'                               => 'no',
			'fc_vat_number_eu_vat_reverse_charge'                           => 'yes',
			'fc_vat_number_eu_vat_reverse_charge_same_country'              => 'no',
			'fc_vat_number_autocomplete_billing_company_name'               => 'no',
			'fc_vat_number_autocomplete_billing_company_name_editing'       => 'no',
			'fc_vat_number_eu_vat_reverse_charge_countries_skip_list'       => array(),
			'fc_vat_number_eu_vat_reverse_charge_label'                     => '',
			'fc_vat_number_eu_vat_reverse_charge_label_invoice'             => '',
			'fc_vat_number_eu_vat_digital_goods_tax_classes'                => array(),
			'fc_vat_number_eu_vat_country_confirmation'                     => 'yes',
			'fc_vat_number_per_account_limit'                               => 'one_per_account',

			// Google Address Autocomplete (feature keys; FCGAA overrides when both active).
			'fc_gaa_enabled'                                                => 'no',
			'fc_gaa_google_places_api_key'                                  => '',
			'fc_gaa_google_places_api_key_validated_hash'                   => '',
			'fc_gaa_google_places_api_version'                              => 'current',
			'fc_gaa_google_places_api_language'                             => '',
			'fc_gaa_search_results_types'                                   => 'address',
			'fc_gaa_search_results_types_address'                           => array(),
			'fc_gaa_company_autocomplete_value_enabled'                     => 'no',
			'fc_gaa_company_autocomplete_input_enabled'                     => 'no',
			'fc_gaa_search_results_types_company'                           => array( 'establishment' ),
			'fc_gaa_show_powered_by_google'                                 => 'yes',
			'fc_gaa_enabled_brasil_api'                                     => 'no',
			'fc_gaa_brasil_api_version'                                     => 'v1',
			'fc_gaa_google_maps_dont_remove_duplicate_scripts'              => 'no',

			// Compatibility settings for plugins.
			'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step' => 'yes',
			'fc_integration_bluehost_plugin_custom_fields'                  => 'no',
			'fc_integration_captcha_pro_captcha_position'                   => 'before_place_order_section',
			'fc_integration_mailchimp_force_subscribe_checkbox_position'    => 'yes',
			'fc_integration_woocommerce_gateway_stripe_apply_styles'        => 'yes',
			'fc_integration_woocommerce_smart_coupons_position_checkout'    => 'before_progress_bar',
			'gm_order_review_checkboxes_before_order_review'                => 'off',
			'hezarfen_checkout_fields_auto_sort'                            => 'no',
			'hezarfen_hide_checkout_postcode_fields'                        => 'no',
			'sg_enable_picker'                                              => 'enable',
			'woocommerce_gzd_display_checkout_back_to_cart_button'          => 'no',
			'woocommerce_gzd_display_checkout_table_color'                  => '#eeeeee',
			'woocommerce_enable_guest_checkout'                             => 'yes',
			'woocommerce_enable_signup_and_login_from_checkout'             => 'no',
			'woocommerce_registration_generate_username'                   => 'yes',
			'woocommerce_registration_generate_password'                   => 'yes',
			'woocommerce_enable_shipping_calc'                              => 'yes',
			'woocommerce_shipping_cost_requires_address'                    => 'no',
			'woocommerce_enable_checkout_login_reminder'                    => 'no',
			'fc_enable_packing_slips_options'                               => 'no',
			'fc_packing_slips_message_box_body_text'                        => '',
			'wc_points_rewards_points_label'                                => sprintf( '%s:%s', __( 'Point', 'woocommerce-points-and-rewards' ), __( 'Points', 'woocommerce-points-and-rewards' ) ), // Intentionally use text domain from the plugin Points and Rewards as this is the default value set in that plugin.
			'wc_points_rewards_cart_min_discount'                           => '',
			'wc_points_rewards_partial_redemption_enabled'                  => 'no',
			'fc_pro_integration_iconic_delivery_slots_ignore_priority'      => 'yes',
			'fc_pro_integration_woocommerce_order_delivery_show_calendar_inline' => 'no',
			'fc_pro_integration_woocommerce_order_delivery_hide_optional_fields' => 'yes',
			'fc_pro_integration_woocommerce_points_and_rewards_redeem_message_position' => 'coupon_code_section',
			'fc_pro_integration_woocommerce_points_and_rewards_display_what_points_page' => null,
			'fc_pro_integration_woocommerce_smart_coupons_position_cart'    => 'before_cart_items',
			'fc_pro_woocommerce_subscriptions_order_details_order_subscriptions_position' => 'inside_order_items',
			'fc_pro_integration_woocommerce_order_delivery_show_estimated_time_notice' => 'yes',
			'fc_pro_integration_woocommerce_order_delivery_estimated_time_notice_text' => '',
			'fc_pro_integration_dintero_checkout_express_checkout_button_enabled' => 'yes',
			'fc_pro_integration_klarna_checkout_express_checkout_button_enabled' => 'yes',
			'fc_pro_integration_payson_checkout_express_checkout_button_enabled' => 'yes',
			'fc_pro_integration_dibs_easy_express_checkout_button_enabled'  => 'yes',
			'fc_pro_integration_svea_checkout_express_checkout_button_enabled' => 'yes',
			'fc_pro_integration_woo_all_products_for_subscriptions_show_options_on_checkout' => 'yes',
			'fc_pro_integration_woocommerce_germanized_dhl_preferred_delivery_position' => 'after_shipping',
			'fc_pro_license_manager_for_woocommerce_order_details_order_licenses_position' => 'inside_order_items',
			'fc_integration_woocommerce_gateway_amazon_payments_advanced_express_checkout_style' => 'only_button',

			// Compatibility settings for themes.
			'fc_compat_theme_atomion_display_order_progress'                => 'no',
			'fc_compat_theme_atomion_display_field_labels'                  => 'yes',
			'fc_compat_theme_impreza_header_spacing'                        => null,
			'fc_compat_theme_zk_nito_display_field_labels'                  => 'no',
			'fc_compat_theme_zk_nito_add_extra_fields'                      => 'no',
			'fc_compat_theme_nyture_output_checkout_steps_section'          => 'no',
			'fc_compat_theme_woodmart_output_checkout_steps_section'        => 'no',
			'fc_compat_theme_woodmart_disable_theme_checkout_options'       => 'yes',
			'fc_compat_theme_pressmart_output_checkout_steps_section'       => 'no',
			'fc_compat_theme_betheme_output_checkout_steps_section'         => 'no',
			'fc_compat_theme_thegem_output_checkout_steps_section'          => 'no',
			'fc_compat_theme_porto_output_checkout_steps_section'           => 'no',
			'fc_compat_theme_kapee_output_checkout_steps_section'           => 'no',
			'fc_compat_theme_go_enable_account_wide_layout'                 => 'yes',
			'fc_compat_theme_fennik_output_breadcrumbs_section'             => 'no',
			'fc_compat_theme_dt_the7_output_additional_header_sections'     => 'no',

			// Settings tools.
			'fc_debug_mode'                                                 => 'no',
			'fc_load_unminified_assets'                                     => 'no',
			'fc_use_enhanced_select_components'                             => 'no',
			'fc_fix_zoom_in_form_fields_mobile_devices'                     => 'yes',
			'fc_telemetry_enabled'                                          => 'no',
			'fc_telemetry_data_groups'                                      => array( 'basic_environment', 'plugin_settings' ),

			// Settings without options in the admin panel.
			'fc_apply_checkout_field_args'                                  => 'yes',
			'fc_enable_checkout_validation'                                 => 'yes',
			'fc_show_account_creation_notice_checkout_contact_step_text'    => 'yes',
			'fc_pro_cart_restore_item_message_dismiss_button_enabled'       => 'yes',
			'fc_pro_cart_coupon_codes_enable'                               => 'yes',
			'fc_pro_enable_checkout_quantity_spinner_buttons'               => 'yes',

			// Deprecated settings.
			'fc_enable_checkout_place_order_sidebar'                        => 'no',
		);

		// Bypass pre_option while building defaults — filter callbacks may call get_option()
		$previous_bypass = $this->bypass_pre_option;
		$this->bypass_pre_option = true;
		$defaults = apply_filters( 'fc_default_option_values', $defaults );
		$this->bypass_pre_option = $previous_bypass;

		// Fail soft to an empty map if a filter returns a non-array
		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		wp_cache_set( 'default_option_values', $defaults, self::CACHE_GROUP );

		return $defaults;
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
	 * Get the list of managed option keys from the defaults map.
	 */
	public function get_managed_option_keys() {
		return array_keys( $this->get_default_option_values() );
	}

	/**
	 * Whether an option key is managed by the settings repository.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_managed_option( $option ) {
		$defaults = $this->get_default_option_values();
		return array_key_exists( $option, $defaults );
	}

	/**
	 * Get the value for a specific option. Returns default if option value is not set.
	 *
	 * Effective value: context profile overlay → active profile → code default (managed)
	 * or WP option (unmanaged, unless present in a loaded profile payload).
	 *
	 * @param  string  $option   Option name.
	 * @param  mixed   $default  The fallback value to return if the option does not exist.
	 */
	public function get_option( $option, $default = null ) {
		// Maybe get default from the default values array.
		if ( null === $default ) {
			$default = $this->get_option_default( $option );
		}

		$effective = $this->get_effective_values();

		// Get overlay / active profile value when present
		if ( is_array( $effective ) && array_key_exists( $option, $effective ) ) {
			$value = $effective[ $option ];
		}
		// Managed keys without a stored value use the code default
		elseif ( $this->is_managed_option( $option ) ) {
			$value = $default;
		}
		// Unmanaged keys fall through to WordPress
		else {
			$value = $this->get_raw_option( $option, $default );
		}

		// Maybe force Lite-compatible values when PRO is not active
		return $this->maybe_force_lite_option_value( $option, $value );
	}



	/**
	 * Register the settings profile post type.
	 */
	public function register_settings_profile_post_type() {
		register_post_type(
			self::PROFILE_POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Settings profiles', 'fluid-checkout' ),
					'singular_name' => __( 'Settings profile', 'fluid-checkout' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type'     => 'shop_order',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}



	/**
	 * Get the active profile slug from the options pointer, bypassing pre_option filters.
	 */
	public function get_active_profile_slug() {
		$slug = $this->get_raw_option( self::ACTIVE_PROFILE_OPTION, '' );
		$slug = is_string( $slug ) ? sanitize_title( $slug ) : '';

		return $slug;
	}

	/**
	 * Set the active profile slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function set_active_profile( $slug ) {
		$slug = sanitize_title( (string) $slug );
		$this->update_raw_option( self::ACTIVE_PROFILE_OPTION, $slug, false );
		$this->invalidate_cache();
	}

	/**
	 * Get the request context profile slug from the filter, or empty when none.
	 */
	public function get_context_profile_slug() {
		/**
		 * Filter the settings profile slug to overlay for the current request.
		 *
		 * @param  string|null  $slug  Profile slug, or empty/null for no overlay.
		 */
		$slug = apply_filters( 'fc_settings_context_profile', null );
		$slug = is_string( $slug ) ? sanitize_title( $slug ) : '';

		// Ignore invalid or missing context profiles
		if ( '' === $slug || null === $this->get_profile_post_id_by_slug( $slug ) ) {
			return '';
		}

		return $slug;
	}

	/**
	 * Set the context profile slug for the current request via a one-off filter callback.
	 * Prefer hooking `fc_settings_context_profile` from feature code instead.
	 *
	 * @param  string|null  $slug  Profile slug or null to clear.
	 */
	public function set_context_profile( $slug ) {
		$slug = null === $slug ? '' : sanitize_title( (string) $slug );

		// Remove previous runtime setter if any
		remove_all_filters( 'fc_settings_context_profile' );

		if ( '' !== $slug ) {
			add_filter( 'fc_settings_context_profile', function() use ( $slug ) {
				return $slug;
			}, 10 );
		}

		$this->invalidate_effective_cache();
	}



	/**
	 * Get the profile values map for a slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function get_profile_values( $slug ) {
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return array(); }

		$cache_key = 'payload:' . $slug;
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// Return cached payload when available
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$post_id = $this->get_profile_post_id_by_slug( $slug );

		// Bail if profile post is missing
		if ( null === $post_id ) {
			wp_cache_set( $cache_key, array(), self::CACHE_GROUP );
			return array();
		}

		$post = get_post( $post_id );

		// Bail if post is unreadable
		if ( ! $post || self::PROFILE_POST_TYPE !== $post->post_type ) {
			wp_cache_set( $cache_key, array(), self::CACHE_GROUP );
			return array();
		}

		// Unserialize profile payload without allowing object instantiation
		$raw_values = $post->post_content;
		if ( is_string( $raw_values ) ) {
			$values = unserialize( $raw_values, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		} else {
			$values = maybe_unserialize( $raw_values );
		}

		// Fail soft to empty map on invalid payload
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		wp_cache_set( $cache_key, $values, self::CACHE_GROUP );

		return $values;
	}

	/**
	 * Save a profile values map by slug.
	 *
	 * @param  string  $slug    Profile slug.
	 * @param  array   $values  Option key => value map.
	 */
	public function save_profile_values( $slug, $values ) {
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return false; }

		$values = is_array( $values ) ? $values : array();
		$post_id = $this->get_profile_post_id_by_slug( $slug );
		$post_content = serialize( $values ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Intentional for CPT post_content payload.

		if ( null === $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => self::PROFILE_POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $slug,
					'post_name'    => $slug,
					'post_content' => $post_content,
				),
				true
			);
		} else {
			$post_id = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $post_content,
				),
				true
			);
		}

		// Bail if save failed
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		$this->invalidate_cache_for_slug( $slug );

		return true;
	}



	/**
	 * Get the effective values map for the current request (context overlay on active).
	 */
	public function get_effective_values() {
		$active_slug = $this->get_active_profile_slug();
		$context_slug = $this->get_context_profile_slug();
		$context_key = '' !== $context_slug ? $context_slug : 'none';
		$cache_key = 'effective:' . ( '' !== $active_slug ? $active_slug : 'none' ) . ':' . $context_key;

		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// Return cached effective map when available
		if ( false !== $cached && is_array( $cached ) ) {
			$this->maybe_register_pre_option_for_keys( array_keys( $cached ) );
			return $cached;
		}

		$defaults = $this->get_default_option_values();
		$effective = is_array( $defaults ) ? $defaults : array();

		// Overlay active profile when available
		if ( '' !== $active_slug ) {
			$active_values = $this->get_profile_values( $active_slug );
			if ( ! empty( $active_values ) ) {
				$effective = array_merge( $effective, $active_values );
			}
		}

		// Overlay sparse context profile when available
		if ( '' !== $context_slug ) {
			$context_values = $this->get_profile_values( $context_slug );
			if ( ! empty( $context_values ) ) {
				$effective = array_merge( $effective, $context_values );
			}
		}

		wp_cache_set( $cache_key, $effective, self::CACHE_GROUP );
		$this->maybe_register_pre_option_for_keys( array_keys( $effective ) );

		return $effective;
	}



	/**
	 * Update values on the active profile (merge into existing payload).
	 *
	 * @param  array  $values  Option key => value map to merge.
	 */
	public function update_active_profile_values( $values ) {
		$values = is_array( $values ) ? $values : array();

		// Bail if nothing to save
		if ( empty( $values ) ) { return false; }

		$active_slug = $this->get_active_profile_slug();

		// Maybe create the default profile when none is active
		if ( '' === $active_slug ) {
			$active_slug = 'default';
			$this->set_active_profile( $active_slug );
		}

		$current = $this->get_profile_values( $active_slug );
		$merged = array_merge( is_array( $current ) ? $current : array(), $values );

		return $this->save_profile_values( $active_slug, $merged );
	}



	/**
	 * Maybe migrate legacy individual options into the first active CPT profile.
	 */
	public function maybe_migrate_legacy_options() {
		// Bail if already migrated
		if ( 'yes' === $this->get_raw_option( self::MIGRATION_FLAG_OPTION, 'no' ) ) {
			return;
		}

		$active_slug = $this->get_active_profile_slug();

		// Bail if an active profile already exists and is readable
		if ( '' !== $active_slug && null !== $this->get_profile_post_id_by_slug( $active_slug ) ) {
			$this->update_raw_option( self::MIGRATION_FLAG_OPTION, 'yes', false );
			$this->maybe_set_migration_time();
			return;
		}

		$defaults = $this->get_default_option_values();
		$payload = array();

		// Iterate managed keys and copy existing individual option values
		foreach ( $defaults as $key => $default ) {
			$raw = $this->get_raw_option( $key, null );

			// Skip keys that were never stored
			if ( null === $raw ) { continue; }

			$payload[ $key ] = $raw;
		}

		// Merge code defaults under stored values so the active profile is complete
		$payload = array_merge( $defaults, $payload );

		$slug = 'default';
		$saved = $this->save_profile_values( $slug, $payload );

		// Bail if profile could not be saved
		if ( ! $saved ) { return; }

		$this->set_active_profile( $slug );
		$this->update_raw_option( self::MIGRATION_FLAG_OPTION, 'yes', false );
		$this->maybe_set_migration_time();
		$this->update_raw_option( self::CLEANUP_NOTICE_OPTION, 'yes', false );
	}

	/**
	 * Persist the migration timestamp once when missing.
	 */
	public function maybe_set_migration_time() {
		// Bail if migration time is already stored
		if ( absint( $this->get_raw_option( self::MIGRATION_TIME_OPTION, 0 ) ) > 0 ) {
			return;
		}

		$this->update_raw_option( self::MIGRATION_TIME_OPTION, time(), false );
	}



	/**
	 * Register the catch-all pre_option filter early so the first managed read bootstraps the repository.
	 */
	public function maybe_register_pre_option_filters() {
		// Bail if already registered
		if ( $this->pre_option_registered ) { return; }

		add_filter( 'pre_option', array( $this, 'filter_pre_option' ), 10, 3 );
		$this->pre_option_registered = true;
	}

	/**
	 * Ensure per-key awareness after effective values are loaded.
	 *
	 * @param  array  $keys  Option keys present in the effective map.
	 */
	public function maybe_register_pre_option_for_keys( $keys ) {
		$this->maybe_register_pre_option_filters();
	}

	/**
	 * Provide effective profile values for managed keys and profile extras via pre_option.
	 *
	 * @param  mixed   $pre_option  Value to short-circuit with, or false to continue.
	 * @param  string  $option      Option name.
	 * @param  mixed   $default     Default passed to get_option.
	 */
	public function filter_pre_option( $pre_option, $option, $default ) {
		// Bail while reading pointer / infra options
		if ( $this->bypass_pre_option ) { return $pre_option; }

		// Bail if a more specific pre_option_* filter already short-circuited (e.g. Lite-only forced values)
		if ( false !== $pre_option ) { return $pre_option; }

		// Bail for the active pointer and migration flags themselves
		if ( in_array( $option, array( self::ACTIVE_PROFILE_OPTION, self::MIGRATION_FLAG_OPTION, self::MIGRATION_TIME_OPTION, self::CLEANUP_NOTICE_OPTION, 'fc_hide_wc_settings_tab' ), true ) ) {
			return $pre_option;
		}

		$is_managed = $this->is_managed_option( $option );

		// Unmanaged keys: only serve from a warm effective cache (do not bootstrap on unrelated get_option calls)
		if ( ! $is_managed ) {
			$active_slug = $this->get_active_profile_slug();
			$context_slug = $this->get_context_profile_slug();
			$cache_key = 'effective:' . ( '' !== $active_slug ? $active_slug : 'none' ) . ':' . ( '' !== $context_slug ? $context_slug : 'none' );
			$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

			if ( false !== $cached && is_array( $cached ) && array_key_exists( $option, $cached ) ) {
				return $cached[ $option ];
			}

			return $pre_option;
		}

		$effective = $this->get_effective_values();

		if ( is_array( $effective ) && array_key_exists( $option, $effective ) ) {
			return $this->maybe_force_lite_option_value( $option, $effective[ $option ] );
		}

		// Managed keys missing from the map use code defaults
		$code_default = $this->get_option_default( $option );
		$value = null !== $code_default ? $code_default : $default;

		return $this->maybe_force_lite_option_value( $option, $value );
	}



	/**
	 * Get a WordPress option while bypassing this class's pre_option filter.
	 *
	 * @param  string  $option   Option name.
	 * @param  mixed   $default  Default value.
	 */
	public function get_raw_option( $option, $default = false ) {
		$previous_bypass = $this->bypass_pre_option;
		$this->bypass_pre_option = true;
		$value = get_option( $option, $default );
		$this->bypass_pre_option = $previous_bypass;

		return $value;
	}

	/**
	 * Update a WordPress option while bypassing this class's pre_option filter.
	 *
	 * @param  string  $option    Option name.
	 * @param  mixed   $value     Option value.
	 * @param  bool    $autoload  Whether to autoload.
	 */
	public function update_raw_option( $option, $value, $autoload = false ) {
		$previous_bypass = $this->bypass_pre_option;
		$this->bypass_pre_option = true;
		$result = update_option( $option, $value, $autoload );
		$this->bypass_pre_option = $previous_bypass;

		return $result;
	}



	/**
	 * Get the profile post ID for a slug, or null when missing.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function get_profile_post_id_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return null; }

		$posts = get_posts(
			array(
				'name'             => $slug,
				'post_type'        => self::PROFILE_POST_TYPE,
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return (int) $posts[0];
	}



	/**
	 * Invalidate all settings profile cache entries.
	 */
	public function invalidate_cache() {
		wp_cache_delete( 'default_option_values', self::CACHE_GROUP );

		// Object cache group flush when supported
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
			return;
		}

		// Fall back to deleting known keys for the active and context profiles
		$active_slug = $this->get_active_profile_slug();
		$context_slug = $this->get_context_profile_slug();
		$this->invalidate_cache_for_slug( $active_slug );
		if ( '' !== $context_slug ) {
			$this->invalidate_cache_for_slug( $context_slug );
		}
		$this->invalidate_effective_cache();
	}

	/**
	 * Invalidate cache entries for a profile slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function invalidate_cache_for_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return; }

		wp_cache_delete( 'payload:' . $slug, self::CACHE_GROUP );
		$this->invalidate_effective_cache();
	}

	/**
	 * Invalidate effective merged maps.
	 */
	public function invalidate_effective_cache() {
		$active_slug = $this->get_active_profile_slug();
		$active_key = '' !== $active_slug ? $active_slug : 'none';
		$context_slug = $this->get_context_profile_slug();
		$context_key = '' !== $context_slug ? $context_slug : 'none';

		wp_cache_delete( 'effective:' . $active_key . ':none', self::CACHE_GROUP );
		wp_cache_delete( 'effective:' . $active_key . ':' . $context_key, self::CACHE_GROUP );
		wp_cache_delete( 'effective:none:none', self::CACHE_GROUP );
	}



	/**
	 * Handle legacy cleanup confirm / dismiss actions.
	 */
	public function maybe_handle_legacy_cleanup_action() {
		// Bail if not a cleanup request
		if ( ! isset( $_GET[ 'fc_settings_legacy_cleanup' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Bail if user cannot manage WooCommerce
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

		check_admin_referer( 'fc_settings_legacy_cleanup' );

		$action = sanitize_key( wp_unslash( $_GET[ 'fc_settings_legacy_cleanup' ] ) );

		if ( 'dismiss' === $action ) {
			$this->update_raw_option( self::CLEANUP_NOTICE_OPTION, 'no', false );
		} elseif ( '1' === $action ) {
			$this->delete_legacy_individual_options();
			$this->update_raw_option( self::CLEANUP_NOTICE_OPTION, 'no', false );
		}

		wp_safe_redirect( remove_query_arg( array( 'fc_settings_legacy_cleanup', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Delete leftover individual `fc_*` option rows that are in the managed defaults map.
	 */
	public function delete_legacy_individual_options() {
		$defaults = $this->get_default_option_values();

		// Iterate managed keys and delete only fc_* leftovers
		foreach ( array_keys( $defaults ) as $key ) {
			// Skip non-fc keys (WooCommerce / third-party options stay as individual rows when also used outside FC)
			if ( 0 !== strpos( $key, 'fc_' ) ) { continue; }

			// Skip infra keys that must remain individual options
			if ( in_array( $key, array( self::ACTIVE_PROFILE_OPTION, self::MIGRATION_FLAG_OPTION, self::MIGRATION_TIME_OPTION, self::CLEANUP_NOTICE_OPTION, 'fc_hide_wc_settings_tab' ), true ) ) {
				continue;
			}

			delete_option( $key );
		}
	}



	/**
	 * Maybe force Lite-compatible option values when PRO is not active.
	 * Uses the same hooks PRO removes on activation, so behavior stays in sync with `pre_option_*` / `option_*` filters.
	 *
	 * @param  string  $option  Option name.
	 * @param  mixed   $value   Resolved option value.
	 */
	public function maybe_force_lite_option_value( $option, $value ) {
		switch ( $option ) {
			case 'fc_design_template':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_design_template', array( $this, 'set_option_lite_design_template' ) ) ) { return $value; }
				return $this->set_option_lite_design_template( $value, $option, null );

			case 'fc_checkout_column_layout':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_checkout_column_layout', array( $this, 'set_option_checkout_column_layout' ) ) ) { return $value; }
				return $this->set_option_checkout_column_layout( $value, $option, null );

			case 'fc_checkout_progress_bar_style':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_checkout_progress_bar_style', array( $this, 'set_option_progress_bar_style' ) ) ) { return $value; }
				return $this->set_option_progress_bar_style( $value, $option, null );

			case 'fc_pro_checkout_edit_cart_replace_edit_cart_link':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_pro_checkout_edit_cart_replace_edit_cart_link', array( $this, 'set_option_replace_edit_cart_link' ) ) ) { return $value; }
				return $this->set_option_replace_edit_cart_link( $value, $option, null );

			case 'fc_pro_checkout_coupon_codes_position':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_pro_checkout_coupon_codes_position', array( $this, 'set_option_coupon_code_position_checkout' ) ) ) { return $value; }
				return $this->set_option_coupon_code_position_checkout( $value, $option, null );

			case 'fc_pro_checkout_billing_address_position':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'pre_option_fc_pro_checkout_billing_address_position', array( $this, 'set_option_billing_address_position_checkout' ) ) ) { return $value; }
				return $this->set_option_billing_address_position_checkout( $value, $option, null );

			case 'fc_pro_checkout_order_summary_position_mobile':
				// Bail if PRO removed the Lite force filter
				if ( ! has_filter( 'option_fc_pro_checkout_order_summary_position_mobile', array( $this, 'set_option_order_summary_position_mobile' ) ) ) { return $value; }
				return $this->set_option_order_summary_position_mobile( $value, $option );

			default:
				return $value;
		}
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
	 * Force the option value for checkout column layout when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_checkout_column_layout( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_checkout_column_layout' );
	}

	/**
	 * Force the option value for progress bar style when only Lite plugin is activated.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function set_option_progress_bar_style( $pre_option, $option, $default ) {
		return $this->get_option_default( 'fc_checkout_progress_bar_style' );
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
	 * Force the option value for order summary section position on mobile when only Lite plugin is activated.
	 *
	 * @param  string  $value        The value of the option.
	 * @param  string  $option       Option name.
	 */
	public function set_option_order_summary_position_mobile( $value, $option ) {
		// Bail if using accepted Lite options
		$accepted_values = array( 'hidden', 'site_header' );
		if ( in_array( $value, $accepted_values ) ) {
			return $value;
		}

		return $this->get_option_default( 'fc_pro_checkout_order_summary_position_mobile' );
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
	 * Sanitize and save settings field values into the active settings profile.
	 *
	 * @param  array       $options  Settings field definitions.
	 * @param  array|null  $data     Posted data. Defaults to `$_POST`.
	 */
	public function save_settings( $options, $data = null ) {
		if ( null === $data ) {
			$data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller verifies nonce.
		}

		// Bail if no data to save
		if ( empty( $data ) || ! is_array( $options ) ) {
			return false;
		}

		$update_values = array();

		// Iterate settings fields and collect sanitized values
		foreach ( $options as $option ) {
			// Skip fields that are not options
			if ( ! isset( $option[ 'id' ] ) || ! isset( $option[ 'type' ] ) || ( isset( $option[ 'is_option' ] ) && false === $option[ 'is_option' ] ) ) {
				continue;
			}

			// Keep the stored value for disabled fields
			if ( array_key_exists( 'disabled', $option ) && true === $option[ 'disabled' ] ) {
				$update_values[ $option[ 'id' ] ] = $this->get_option( $option[ 'id' ] );
				continue;
			}

			$option_name = $option[ 'field_name' ] ?? $option[ 'id' ];

			// Get posted value (supports nested option names)
			if ( strstr( $option_name, '[' ) ) {
				parse_str( $option_name, $option_name_array );
				$option_name = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );
				$raw_value = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
			} else {
				$raw_value = isset( $data[ $option_name ] ) ? wp_unslash( $data[ $option_name ] ) : null;
			}

			$value = $this->sanitize_setting_value( $option, $raw_value, $update_values );

			// Skip null values (e.g. passwords left blank)
			if ( null === $value ) {
				continue;
			}

			$update_values[ $option[ 'id' ] ] = $value;
		}

		// Bail if nothing to update
		if ( empty( $update_values ) ) {
			return false;
		}

		return $this->update_active_profile_values( $update_values );
	}

	/**
	 * Sanitize a single settings field value by type.
	 *
	 * @param  array  $option         Settings field definition.
	 * @param  mixed  $raw_value      Raw posted value.
	 * @param  array  $update_values  Values sanitized earlier in the same save request.
	 */
	public function sanitize_setting_value( $option, $raw_value, $update_values = array() ) {
		$type = isset( $option[ 'type' ] ) ? $option[ 'type' ] : '';

		switch ( $type ) {
			case 'checkbox':
			case 'fc_telemetry_enable':
				$value = ( '1' === $raw_value || 'yes' === $raw_value ) ? 'yes' : 'no';
				break;

			case 'textarea':
			case 'fc_textarea':
				$value = wp_kses_post( trim( (string) $raw_value ) );
				break;

			case 'multiselect':
			case 'multi_select_countries':
			case 'fc_multiselect':
			case 'fc_checkboxgroup':
				$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
				break;

			case 'select':
			case 'fc_select':
			case 'fc_layout_selector':
			case 'fc_template_selector':
				// Only allow enabled options so disabled PRO values fall back to the Lite-compatible default
				$allowed_values = array();
				if ( ! empty( $option[ 'options' ] ) && is_array( $option[ 'options' ] ) ) {
					foreach ( $option[ 'options' ] as $key => $args ) {
						if ( is_array( $args ) && array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ) {
							continue;
						}
						$allowed_values[] = (string) $key;
					}
				}
				if ( empty( $option[ 'default' ] ) && empty( $allowed_values ) ) {
					$value = null;
					break;
				}
				$default = ( empty( $option[ 'default' ] ) ? ( $allowed_values[0] ?? '' ) : $option[ 'default' ] );
				$value = in_array( (string) $raw_value, $allowed_values, true ) ? $raw_value : $default;
				break;

			case 'password':
				$value = is_string( $raw_value ) ? trim( $raw_value ) : null;
				break;

			default:
				$value = wc_clean( $raw_value );
				break;
		}

		// Special case: telemetry data groups
		if ( ! empty( $option[ 'id' ] ) && 'fc_telemetry_data_groups' === $option[ 'id' ] ) {
			$enabled = array_key_exists( 'fc_telemetry_enabled', $update_values )
				? $update_values[ 'fc_telemetry_enabled' ]
				: $this->get_option( 'fc_telemetry_enabled', 'no' );

			if ( 'no' === $enabled ) {
				$stored = array_key_exists( 'fc_telemetry_data_groups', $update_values )
					? $update_values[ 'fc_telemetry_data_groups' ]
					: $this->get_option( 'fc_telemetry_data_groups', array( 'basic_environment' ) );
				$value = class_exists( 'FluidCheckout_Settings_Tools' )
					? FluidCheckout_Settings_Tools::normalize_telemetry_data_groups( $stored )
					: array( 'basic_environment' );
			} elseif ( class_exists( 'FluidCheckout_Settings_Tools' ) ) {
				$value = FluidCheckout_Settings_Tools::normalize_telemetry_data_groups( is_array( $raw_value ) ? $raw_value : array() );
			}
		}

		return $value;
	}

}

FluidCheckout_Settings::instance();

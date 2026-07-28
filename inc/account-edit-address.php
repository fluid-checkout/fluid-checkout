<?php
defined( 'ABSPATH' ) || exit;

/**
 * Features for account edit address endpoints.
 */
class FluidCheckout_AccountEditAddress extends FluidCheckout {

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
		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'maybe_add_js_settings_address_i18n_edit_address' ), 20 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Body Class
		remove_filter( 'body_class', array( $this, 'add_body_class' ) );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'maybe_add_js_settings_address_i18n_edit_address' ), 20 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on account address edit page
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $classes; }

		// Initialize variables
		$add_classes = array();

		// Add extra class to enable form fields font-size styles
		if ( true === apply_filters( 'fc_fix_zoom_in_form_fields_mobile_devices', ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_fix_zoom_in_form_fields_mobile_devices' ) ) ) ) {
			$add_classes[] = 'has-form-field-font-size-fix';
		}

		return array_merge( $classes, $add_classes );
	}



	/**
	 * Add settings to the plugin settings JS object for edit address pages.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function maybe_add_js_settings_address_i18n_edit_address( $settings ) {
		// Bail if not on account address edit page
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $settings; }

		// Initialize variables
		$load_address      = $this->get_edit_address_load_address();
		$address_i18n      = array_key_exists( 'addressI18n', $settings ) ? $settings[ 'addressI18n' ] : array();

		// Maybe add native WooCommerce edit address fields
		if ( in_array( $load_address, array( 'billing', 'shipping' ), true ) ) {
			$address_i18n[ 'editAddressFields' ]  = $this->get_edit_address_fields( $load_address );
			$address_i18n[ 'editAddressContext' ] = array(
				'source'           => 'woocommerce',
				'addressType'      => $load_address,
				'fieldKeyFormat'   => 'prefixed',
				'fieldKeyPrefix'   => $load_address . '_',
			);
		}

		// Add address i18n settings for edit address pages
		$settings[ 'addressI18n' ] = $address_i18n;

		return $settings;
	}



	/**
	 * Get the address type for the current edit address page.
	 *
	 * @return  string|null  Address type (`billing` or `shipping`), or endpoint slug for other edit address pages.
	 */
	public function get_edit_address_load_address() {
		global $wp;

		// Bail if edit address endpoint is not set
		if ( ! isset( $wp->query_vars[ 'edit-address' ] ) ) { return null; }

		return wc_edit_address_i18n( sanitize_title( $wp->query_vars[ 'edit-address' ] ), true );
	}



	/**
	 * Get edit address fields for the current user and address type.
	 *
	 * Mirrors `WC_Shortcode_My_Account::edit_address()` field building, without user values.
	 *
	 * @param   string  $load_address  Address type to load. Accepted values: `billing` or `shipping`.
	 */
	public function get_edit_address_fields( $load_address ) {
		// Sanitize address type
		$load_address = sanitize_key( $load_address );

		// Get user country
		$country = get_user_meta( get_current_user_id(), $load_address . '_country', true );

		// Maybe fallback to base country
		if ( ! $country ) {
			$country = WC()->countries->get_base_country();
		}

		// Maybe validate country for billing address
		if ( 'billing' === $load_address ) {
			$allowed_countries = WC()->countries->get_allowed_countries();

			if ( ! array_key_exists( $country, $allowed_countries ) ) {
				$country = current( array_keys( $allowed_countries ) );
			}
		}

		// Maybe validate country for shipping address
		if ( 'shipping' === $load_address ) {
			$allowed_countries = WC()->countries->get_shipping_countries();

			if ( ! array_key_exists( $country, $allowed_countries ) ) {
				$country = current( array_keys( $allowed_countries ) );
			}
		}

		// Get address fields
		$fields = WC()->countries->get_address_fields( $country, $load_address . '_' );

		// Apply same filters as the native WooCommerce edit address form
		$fields = apply_filters( 'woocommerce_address_to_edit', $fields, $load_address );

		// Remove field values, only field arguments are needed for JS
		foreach ( $fields as $field_key => $field_args ) {
			if ( is_array( $field_args ) && array_key_exists( 'value', $field_args ) ) {
				unset( $fields[ $field_key ][ 'value' ] );
			}
		}

		return $fields;
	}

}

FluidCheckout_AccountEditAddress::instance();

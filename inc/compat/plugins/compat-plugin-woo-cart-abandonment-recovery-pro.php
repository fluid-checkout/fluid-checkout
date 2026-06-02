<?php
defined( 'ABSPATH' ) || exit;

use libphonenumber\PhoneNumberUtil;

/**
 * Compatibility with plugin: Cart Abandonment Recovery Pro for WooCommerce (by CartFlows Inc).
 */
class FluidCheckout_WooCartAbandonmentRecoveryPro extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Dequeue WCAR country code assets when using FC international phone fields
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_wcar_country_code_assets' ), 100 );

		// Phone GDPR
		$this->gdpr_phone_hooks();

		// International phone + WCAR country code compatibility
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_remove_wcar_country_code_checkout_fields' ), 50 );
		add_filter( 'wcar_cart_default_other_fields', array( $this, 'maybe_normalize_wcar_abandoned_phone_fields' ), 20 );
	}

	/**
	 * Initialize phone GDPR hooks.
	 */
	public function gdpr_phone_hooks() {
		// Bail if WCAR plugin main class is unavailable
		if ( ! function_exists( 'wcf_ca' ) ) { return; }

		add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'output_gdpr_phone_consent_hidden_field' ), 5 );
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_wcar_gdpr_phone_message_placeholder' ), 200 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'maybe_add_gdpr_phone_consent_substep_text_line' ), 30 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'maybe_add_gdpr_phone_consent_substep_text_line' ), 30 );
		add_filter( 'fc_substep_text_display_value_wcf_gdpr_phone_consent', array( $this, 'format_checkbox_display_value_as_yes' ), 10, 4 );
		add_filter( 'fc_substep_text_display_value_gdpr_phone_consent', array( $this, 'format_checkbox_display_value_as_yes' ), 10, 4 );
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_persist_gdpr_phone_consent_to_session' ), 20 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_gdpr_phone_consent_hidden_fragment' ), 10 );
	}



	/**
	 * Check whether WCAR Pro phone GDPR is enabled.
	 *
	 * @return bool
	 */
	public function is_wcar_phone_gdpr_enabled() {
		// Bail if WCAR PRO license is not active
		if ( ! function_exists( 'wcar_pro_is_active_license' ) || ! wcar_pro_is_active_license() ) { return false; }

		// Bail if WCAR plugin main class is unavailable
		if ( ! function_exists( 'wcf_ca' ) ) { return false; }

		// Bail if phone GDPR is disabled in plugin settings
		if ( 'on' !== wcf_ca()->utils->wcar_get_option( 'wcf_ca_phone_gdpr_status' ) ) { return false; }

		return true;
	}

	/**
	 * Check whether WCAR Pro SMS or WhatsApp tracking is enabled.
	 *
	 * @return bool
	 */
	public function is_wcar_sms_or_whatsapp_enabled() {
		// Bail if WCAR PRO license is not active
		if ( ! function_exists( 'wcar_pro_is_active_license' ) || ! wcar_pro_is_active_license() ) { return false; }

		// Bail if WCAR plugin main class is unavailable
		if ( ! function_exists( 'wcf_ca' ) ) { return false; }

		$sms_enabled      = 'on' === wcf_ca()->utils->wcar_get_option( 'wcf_ca_sms_tracking_status' );
		$whatsapp_enabled = 'on' === wcf_ca()->utils->wcar_get_option( 'wcf_ca_whatsapp_tracking_status' );

		return $sms_enabled || $whatsapp_enabled;
	}

	/**
	 * Check whether FC PRO international phone fields feature is enabled.
	 *
	 * @return bool
	 */
	public function is_fc_intl_phone_enabled() {
		// Bail if FC PRO international phone fields class is unavailable
		if ( ! class_exists( 'FluidCheckout_PRO_CheckoutInternationalPhoneField' ) ) { return false; }

		return FluidCheckout_PRO_CheckoutInternationalPhoneField::instance()->is_feature_enabled();
	}

	/**
	 * Check whether WCAR country code field should be replaced by FC international phone fields.
	 *
	 * @return bool
	 */
	public function is_fc_intl_phone_wcar_compat_enabled() {
		// Bail if FC international phone fields are not enabled
		return $this->is_fc_intl_phone_enabled() && $this->is_wcar_sms_or_whatsapp_enabled();
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Register script assets; enqueue is guarded in maybe_enqueue_assets()
		$dependencies = array( 'jquery', 'cartflows-cart-abandonment-tracking' );
		if ( $this->is_fc_intl_phone_wcar_compat_enabled() ) {
			$dependencies[] = 'fc-pro-intl-tel-input-handler';
		}

		// Register script.
		wp_register_script( 'fc-compat-checkout-wcar', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-cart-abandonment-recovery-pro/checkout-wcar' ), $dependencies, NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-checkout-wcar', 'window.addEventListener("load",function(){FCWCARCheckout.init();});' );

		// Localize script. With flag to enable FC international phone fields compatibility with WCAR PRO.
		wp_localize_script( 'fc-compat-checkout-wcar', 'fcWcarCheckoutSettings', array( 'enableIntlPhoneWcarCompat' => $this->is_fc_intl_phone_wcar_compat_enabled() ) );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-compat-checkout-wcar' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page or fragment.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if no compatibility features are needed
		if ( ! $this->is_wcar_phone_gdpr_enabled() && ! $this->is_fc_intl_phone_wcar_compat_enabled() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Maybe dequeue WCAR country code field assets on FC checkout.
	 */
	public function maybe_dequeue_wcar_country_code_assets() {
		// Bail if compatibility is not needed
		if ( ! $this->is_fc_intl_phone_wcar_compat_enabled() ) { return; }

		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		wp_dequeue_script( 'wcar-pro-country-code-field' );
		wp_dequeue_style( 'wcar-pro-country-code-field' );
		wp_dequeue_script( 'wcar-pro-cart-abandonment-tracking' );
	}



	/**
	 * Remove WCAR country code checkout fields when FC international phone fields are enabled.
	 *
	 * @param   array  $fields  Checkout fields.
	 *
	 * @return  array           Modified checkout fields.
	 */
	public function maybe_remove_wcar_country_code_checkout_fields( $fields ) {
		// Bail if compatibility is not needed
		if ( ! $this->is_fc_intl_phone_wcar_compat_enabled() ) { return $fields; }

		// Maybe remove billing country code field
		if ( isset( $fields[ 'billing' ][ 'billing_country_code' ] ) ) {
			unset( $fields[ 'billing' ][ 'billing_country_code' ] );
		}

		// Maybe remove shipping country code field
		if ( isset( $fields[ 'shipping' ][ 'shipping_country_code' ] ) ) {
			unset( $fields[ 'shipping' ][ 'shipping_country_code' ] );
		}

		return $fields;
	}

	/**
	 * Normalize abandoned cart phone fields for WCAR SMS/WhatsApp when FC international phone is enabled.
	 *
	 * Only mutates WCAR `other_fields` data. Does not change WooCommerce order phone fields.
	 *
	 * @param   array  $other_fields  Abandoned cart other fields.
	 *
	 * @return  array                   Modified other fields.
	 */
	public function maybe_normalize_wcar_abandoned_phone_fields( $other_fields ) {
		// Bail if compatibility is not needed
		if ( ! $this->is_fc_intl_phone_wcar_compat_enabled() ) { return $other_fields; }

		// Get full phone number from POST data.
		$full_phone = $this->get_full_phone_from_post();

		// If full phone number is not found in POST data, use WCAR phone number.
		if ( empty( $full_phone ) && ! empty( $other_fields[ 'wcf_phone_number' ] ) ) {
			$full_phone = $other_fields[ 'wcf_phone_number' ];
		}

		// Bail if full phone number is empty.
		if ( empty( $full_phone ) ) { return $other_fields; }

		// Normalize WCAR phone fields using libphonenumber.
		if ( class_exists( 'libphonenumber\PhoneNumberUtil' ) ) {
			$other_fields = $this->normalize_wcar_phone_with_libphonenumber( $other_fields, $full_phone );
		}
		// Otherwise, normalize WCAR phone fields without libphonenumber.
		else {
			$other_fields = $this->normalize_wcar_phone_without_libphonenumber( $other_fields, $full_phone );
		}

		return $other_fields;
	}

	/**
	 * Get full phone number from POST data (read-only).
	 *
	 * @return  string  Full phone number or empty string.
	 */
	public function get_full_phone_from_post() {
		$field_keys = array(
			'shipping_phone_full',
			'billing_phone_full',
			'phone_full',
		);

		foreach ( $field_keys as $field_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WCAR before this filter runs.
			if ( ! empty( $_POST[ $field_key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				return sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) );
			}
		}

		return '';
	}

	/**
	 * Normalize WCAR phone fields using libphonenumber.
	 *
	 * @param   array   $other_fields  Abandoned cart other fields.
	 * @param   string  $full_phone    Full phone number to parse.
	 *
	 * @return  array                   Modified other fields.
	 */
	public function normalize_wcar_phone_with_libphonenumber( $other_fields, $full_phone ) {
		// Try to parse full phone number using libphonenumber.
		try {
			$phone_util = PhoneNumberUtil::getInstance();
			$parsed_phone_number = $phone_util->parse( $full_phone, null );

			// Bail if full phone number is not valid.
			if ( ! $phone_util->isValidNumber( $parsed_phone_number ) ) {
				return $this->normalize_wcar_phone_without_libphonenumber( $other_fields, $full_phone );
			}

			// Set WCAR phone country code and number.
			$other_fields[ 'wcf_phone_country_code' ] = '+' . $parsed_phone_number->getCountryCode();
			$other_fields[ 'wcf_phone_number' ]       = (string) $parsed_phone_number->getNationalNumber();
		}
		catch ( Exception $e ) {
			// Fallback to normalize WCAR phone fields without libphonenumber.
			$other_fields = $this->normalize_wcar_phone_without_libphonenumber( $other_fields, $full_phone );
		}

		return $other_fields;
	}

	/**
	 * Normalize WCAR phone fields without libphonenumber.
	 *
	 * When the phone already includes a leading `+`, keep it as E.164 in `wcf_phone_number` and clear
	 * `wcf_phone_country_code` to avoid double-prefixing in WCAR's `get_checkout_phone_number()`.
	 *
	 * @param   array   $other_fields  Abandoned cart other fields.
	 * @param   string  $full_phone    Full phone number to parse.
	 *
	 * @return  array                   Modified other fields.
	 */
	public function normalize_wcar_phone_without_libphonenumber( $other_fields, $full_phone ) {
		// Keep only digits and the '+' sign from full phone number.
		$phone_number = preg_replace( '/[^\d+]/', '', $full_phone );

		// If full phone number starts with `+`, set WCAR phone number and clear WCAR phone country code.
		if ( 0 === strpos( $phone_number, '+' ) ) {
			$other_fields[ 'wcf_phone_number' ]       = $phone_number;
			$other_fields[ 'wcf_phone_country_code' ] = '';
		}

		return $other_fields;
	}



	/**
	 * Get the GDPR phone consent hidden field HTML.
	 *
	 * @param   string  $consent_value  Consent field value.
	 *
	 * @return  string
	 */
	public function get_gdpr_phone_consent_hidden_field_html( $consent_value = '' ) {
		return '<input type="hidden" name="wcf_gdpr_phone_consent" id="fc-wcar-gdpr-phone-consent-hidden" value="' . esc_attr( $consent_value ) . '" />';
	}

	/**
	 * Add the GDPR phone consent hidden field as a checkout fragment.
	 *
	 * @param   array  $fragments  Checkout fragments.
	 *
	 * @return  array
	 */
	public function add_gdpr_phone_consent_hidden_fragment( $fragments ) {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return $fragments; }

		$fragments[ '#fc-wcar-gdpr-phone-consent-hidden' ] = $this->get_gdpr_phone_consent_hidden_field_html( $this->get_gdpr_phone_consent_value() );

		return $fragments;
	}

	/**
	 * Output the hidden field used to persist GDPR phone consent across checkout updates.
	 */
	public function output_gdpr_phone_consent_hidden_field() {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return; }

		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in get_gdpr_phone_consent_hidden_field_html().
		echo $this->get_gdpr_phone_consent_hidden_field_html( $this->get_gdpr_phone_consent_value() );
	}

	/**
	 * Output empty placeholder for WCAR PRO phone GDPR message placement.
	 */
	public function output_wcar_gdpr_phone_message_placeholder() {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return; }

		// Output placeholder
		echo '<div id="fc-wcar-gdpr-phone-message-placeholder" class="fc-wcar-gdpr-phone-message-placeholder"></div>';
	}

	/**
	 * Get the GDPR phone consent value stored in the FC checkout session.
	 *
	 * @return  string
	 */
	public function get_gdpr_phone_consent_session_value() {
		$field_key        = 'wcf_gdpr_phone_consent';
		$legacy_field_key = 'gdpr_phone_consent';

		$value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $field_key );
		if ( null !== $value ) {
			return $value;
		}

		$value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $legacy_field_key );
		if ( null !== $value ) {
			return $value;
		}

		return '';
	}

	/**
	 * Check whether parsed posted data represents a checkout form submission.
	 *
	 * @param   array  $posted_data  Parsed checkout posted data.
	 *
	 * @return  bool
	 */
	public function has_checkout_post_data( $posted_data ) {
		if ( ! is_array( $posted_data ) || empty( $posted_data ) ) {
			return false;
		}

		$indicator_keys = array(
			'billing_email',
			'billing_first_name',
			'shipping_first_name',
			'shipping_country',
			'billing_country',
		);

		foreach ( $indicator_keys as $field_key ) {
			if ( array_key_exists( $field_key, $posted_data ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the GDPR phone consent value from parsed posted data only.
	 *
	 * @param   array  $posted_data  Parsed checkout posted data.
	 *
	 * @return  string|null  Field value, empty string when unchecked, or null when not in posted data.
	 */
	public function get_gdpr_phone_consent_value_from_posted_data( $posted_data ) {
		$field_key        = 'wcf_gdpr_phone_consent';
		$legacy_field_key = 'gdpr_phone_consent';

		if ( array_key_exists( $field_key, $posted_data ) ) {
			return $posted_data[ $field_key ];
		}

		if ( array_key_exists( $legacy_field_key, $posted_data ) ) {
			return $posted_data[ $legacy_field_key ];
		}

		if ( $this->has_checkout_post_data( $posted_data ) ) {
			return '';
		}

		return null;
	}

	/**
	 * Persist the GDPR phone consent value to the FC checkout session.
	 *
	 * @param   array  $posted_data  Parsed checkout posted data.
	 *
	 * @return  array
	 */
	public function maybe_persist_gdpr_phone_consent_to_session( $posted_data ) {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return $posted_data; }

		// Bail if not an array
		if ( ! is_array( $posted_data ) ) { return $posted_data; }

		// Get consent checkbox value
		$consent_value = sanitize_text_field( wp_unslash( $this->get_gdpr_phone_consent_value_from_posted_data( $posted_data ) ) );

		// Bail if consent value is not available in posted data
		if ( empty( $consent_value ) ) { return $posted_data; }

		// Persist consent value to session
		FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( 'wcf_gdpr_phone_consent', $consent_value );
		FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( 'gdpr_phone_consent', $consent_value );

		// Update posted data
		$posted_data[ 'wcf_gdpr_phone_consent' ] = $consent_value;

		// Return updated posted data
		return $posted_data;
	}

	/**
	 * Get the GDPR phone consent field value from posted data or session.
	 *
	 * @return  string  Field value.
	 */
	public function get_gdpr_phone_consent_value() {
		// Get posted data
		$posted_data  = FluidCheckout_Steps::instance()->get_parsed_posted_data();
		$posted_value = $this->get_gdpr_phone_consent_value_from_posted_data( $posted_data );

		// Use posted value when checkout form data is available
		if ( null !== $posted_value ) {
			return $posted_value;
		}

		// Fallback to session value
		return $this->get_gdpr_phone_consent_session_value();
	}

	/**
	 * Check whether the GDPR phone consent checkbox is checked.
	 *
	 * @return  bool
	 */
	public function is_gdpr_phone_consent_checked() {
		return $this->is_checkbox_consent_value( $this->get_gdpr_phone_consent_value() );
	}

	/**
	 * Check whether a field value represents a checked checkbox consent.
	 *
	 * @param   mixed  $field_value  Field value.
	 *
	 * @return  bool
	 */
	public function is_checkbox_consent_value( $field_value ) {
		return in_array( $field_value, array( 'on', '1', 1 ), true );
	}

	/**
	 * Check whether the GDPR consent review line should be added for the current substep filter.
	 *
	 * @return  bool
	 */
	public function should_add_gdpr_consent_to_current_substep() {
		// Bail if cart is not available does not need shipping address
		if ( ! WC()->cart ) { return false; }

		// Get if shipping address is needed
		$needs_shipping_address = WC()->cart->needs_shipping_address();

		// Bail if not on shipping address substep
		if ( doing_filter( 'fc_substep_shipping_address_text_lines' ) ) { return $needs_shipping_address; }

		// Bail if not on billing address substep
		if ( doing_filter( 'fc_substep_billing_address_text_lines' ) ) { return ! $needs_shipping_address; }

		return false;
	}

	/**
	 * Get the GDPR phone consent field arguments for substep review text.
	 *
	 * @return  array
	 */
	public function get_gdpr_phone_consent_field_args() {
		$gdpr_message = function_exists( 'wcf_ca' ) ? wcf_ca()->utils->wcar_get_option( 'wcf_ca_phone_gdpr_message', '' ) : '';

		return array(
			'type'  => 'checkbox',
			'label' => wp_strip_all_tags( $gdpr_message ),
		);
	}

	/**
	 * Format checkbox display value as "Yes" for substep review text.
	 *
	 * @param   string  $field_display_value  Field display value.
	 * @param   mixed   $field_value          Field value.
	 * @param   string  $field_key            Field key.
	 * @param   array   $field_args           Field arguments.
	 *
	 * @return  string|null
	 */
	public function format_checkbox_display_value_as_yes( $field_display_value, $field_value, $field_key, $field_args ) {
		// Bail if field value does not represent checked consent
		if ( ! $this->is_checkbox_consent_value( $field_value ) ) {
			return $field_display_value;
		}

		$field_label = ! empty( $field_args[ 'label' ] ) ? $field_args[ 'label' ] : $field_key;
		$yes_value   = _x( 'Yes', 'Substep review checkbox value', 'fluid-checkout' );

		return FluidCheckout_Steps::instance()->get_field_display_value_with_pattern( $yes_value, $field_key, $field_args, $field_label, true );
	}

	/**
	 * Maybe add the GDPR phone consent value to address substep review text lines.
	 *
	 * @param   array  $review_text_lines  The list of lines to show in the substep review text.
	 *
	 * @return  array
	 */
	public function maybe_add_gdpr_phone_consent_substep_text_line( $review_text_lines = array() ) {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return $review_text_lines; }

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if consent should not be added to the current substep
		if ( ! $this->should_add_gdpr_consent_to_current_substep() ) { return $review_text_lines; }

		// Bail if consent is not checked
		if ( ! $this->is_gdpr_phone_consent_checked() ) { return $review_text_lines; }

		$field_key  = 'wcf_gdpr_phone_consent';
		$field_args = $this->get_gdpr_phone_consent_field_args();

		$review_text_lines[] = FluidCheckout_Steps::instance()->get_field_display_value( '1', $field_key, $field_args );

		return $review_text_lines;
	}
}

FluidCheckout_WooCartAbandonmentRecoveryPro::instance();

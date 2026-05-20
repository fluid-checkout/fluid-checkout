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
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_wcar_gdpr_phone_message_placeholder' ), 200 );

		// International phone + WCAR country code compatibility
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_remove_wcar_country_code_checkout_fields' ), 50 );
		add_filter( 'wcar_cart_default_other_fields', array( $this, 'maybe_normalize_wcar_abandoned_phone_fields' ), 20 );
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
		// Bail if WCAR PRO license is not active
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

		if ( isset( $fields['billing']['billing_country_code'] ) ) {
			unset( $fields['billing']['billing_country_code'] );
		}

		if ( isset( $fields['shipping']['shipping_country_code'] ) ) {
			unset( $fields['shipping']['shipping_country_code'] );
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
		if ( empty( $full_phone ) && ! empty( $other_fields['wcf_phone_number'] ) ) {
			$full_phone = $other_fields['wcf_phone_number'];
		}

		// Bail if full phone number is empty.
		if ( empty( $full_phone ) ) { return $other_fields; }

		// Normalize WCAR phone fields using libphonenumber.
		if ( class_exists( 'libphonenumber\PhoneNumberUtil' ) ) {
			$other_fields = $this->normalize_wcar_phone_with_libphonenumber( $other_fields, $full_phone );
		}
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
			$other_fields['wcf_phone_country_code'] = '+' . $parsed_phone_number->getCountryCode();
			$other_fields['wcf_phone_number']       = (string) $parsed_phone_number->getNationalNumber();
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
		// Remove non-digits and leading `+` from full phone number.
		$phone_number = preg_replace( '/[^\d+]/', '', $full_phone );

		// If full phone number starts with `+`, set WCAR phone number and clear WCAR phone country code.
		if ( 0 === strpos( $phone_number, '+' ) ) {
			$other_fields['wcf_phone_number']       = $phone_number;
			$other_fields['wcf_phone_country_code'] = '';
		}

		return $other_fields;
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
}

FluidCheckout_WooCartAbandonmentRecoveryPro::instance();

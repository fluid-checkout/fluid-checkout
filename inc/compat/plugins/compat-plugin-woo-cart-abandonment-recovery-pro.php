<?php
defined( 'ABSPATH' ) || exit;

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Phone GDPR
		$this->gdpr_phone_hooks();

		// Country code fields
		$this->country_code_hooks();
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
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_persist_gdpr_phone_consent_to_session' ), 20 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_gdpr_phone_consent_hidden_fragment' ), 10 );
	}

	/**
	 * Initialize WCAR country code field hooks.
	 */
	public function country_code_hooks() {
		// Bail if WCAR plugin main class is unavailable
		if ( ! function_exists( 'wcf_ca' ) ) { return; }

		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'maybe_add_wcar_country_code_substep_review_text_skip_fields' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'maybe_add_wcar_country_code_substep_review_text_skip_fields' ), 10 );
	}



	/**
	 * Check whether WCAR Pro SMS or WhatsApp tracking is enabled.
	 *
	 * @return  bool
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
	 * Check whether FC PRO international phone fields replace the WCAR country code fields.
	 *
	 * @return  bool
	 */
	public function is_fc_pro_intl_phone_wcar_compat_enabled() {
		// Bail if FC PRO WCAR compatibility class is unavailable
		if ( ! class_exists( 'FluidCheckout_PRO_WooCartAbandonmentRecoveryPro' ) ) { return false; }

		return FluidCheckout_PRO_WooCartAbandonmentRecoveryPro::instance()->is_intl_phone_wcar_compat_enabled();
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
	 * Register assets.
	 */
	public function register_assets() {
		// Register script.
		wp_register_script( 'fc-compat-checkout-wcar', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-cart-abandonment-recovery-pro/checkout-wcar' ), array( 'jquery', 'cartflows-cart-abandonment-tracking' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-checkout-wcar', 'window.addEventListener("load",function(){FCWCARCheckout.init();});' );
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

		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return; }

		$this->enqueue_assets();
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
		$value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wcf_gdpr_phone_consent' );
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
		if ( array_key_exists( 'wcf_gdpr_phone_consent', $posted_data ) ) {
			return $posted_data[ 'wcf_gdpr_phone_consent' ];
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
		$consent_value = $this->get_gdpr_phone_consent_value_from_posted_data( $posted_data );

		// Bail if consent value is not available in posted data
		if ( null === $consent_value ) { return $posted_data; }

		// Sanitize consent value
		$consent_value_esc = sanitize_text_field( $consent_value );

		// Persist consent value to session
		FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( 'wcf_gdpr_phone_consent', $consent_value_esc );

		// Update posted data
		$posted_data[ 'wcf_gdpr_phone_consent' ] = $consent_value_esc;

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
		// Bail if cart is not available
		if ( ! WC()->cart ) { return false; }

		// Get if shipping address is needed
		$needs_shipping_address = WC()->cart->needs_shipping_address();

		// Show on shipping address substep when a shipping address is required
		if ( doing_filter( 'fc_substep_shipping_address_text_lines' ) ) { return $needs_shipping_address; }

		// Show on billing address substep when a shipping address is not required
		if ( doing_filter( 'fc_substep_billing_address_text_lines' ) ) { return ! $needs_shipping_address; }

		return false;
	}

	/**
	 * Get the GDPR phone consent message label from the WCAR Pro plugin settings.
	 *
	 * @return  string
	 */
	public function get_gdpr_phone_consent_label() {
		$gdpr_message = function_exists( 'wcf_ca' ) ? wcf_ca()->utils->wcar_get_option( 'wcf_ca_phone_gdpr_message', '' ) : '';

		return wp_strip_all_tags( $gdpr_message );
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

		// Build the review line manually as "<consent message>: Yes"
		$field_label = $this->get_gdpr_phone_consent_label();
		$yes_value   = _x( 'Yes', 'Substep review checkbox value', 'fluid-checkout' );

		// translators: %1$s the consent message label, %2$s the "Yes" value.
		$review_text_lines[] = ! empty( $field_label ) ? sprintf( _x( '%1$s: %2$s', 'Substep review checkbox line', 'fluid-checkout' ), $field_label, $yes_value ) : $yes_value;

		return $review_text_lines;
	}

	/**
	 * Maybe add WCAR country code fields to the address substep review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  The list of field keys to skip in the substep review text.
	 *
	 * @return  array
	 */
	public function maybe_add_wcar_country_code_substep_review_text_skip_fields( $field_keys_skip_list ) {
		// Bail if WCAR Pro SMS or WhatsApp tracking is not enabled
		if ( ! $this->is_wcar_sms_or_whatsapp_enabled() ) { return $field_keys_skip_list; }

		// Bail if FC PRO international phone fields replace the WCAR country code fields
		if ( $this->is_fc_pro_intl_phone_wcar_compat_enabled() ) { return $field_keys_skip_list; }

		// Bail if not an array
		if ( ! is_array( $field_keys_skip_list ) ) { return $field_keys_skip_list; }

		// Maybe skip shipping country code field
		if ( doing_filter( 'fc_substep_text_shipping_address_field_keys_skip_list' ) ) {
			$field_keys_skip_list[] = 'shipping_country_code';
		}

		// Maybe skip billing country code field
		if ( doing_filter( 'fc_substep_text_billing_address_field_keys_skip_list' ) ) {
			$field_keys_skip_list[] = 'billing_country_code';
		}

		return $field_keys_skip_list;
	}
}

FluidCheckout_WooCartAbandonmentRecoveryPro::instance();

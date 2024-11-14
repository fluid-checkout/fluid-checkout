<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Checkout Field Editor for WooCommerce (free version) (by ThemeHigh).
 */
class FluidCheckout_WooCheckoutFieldEditorPro extends FluidCheckout {

	private static $thwcfd_public = null;

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

		// Checkout field args
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_mailcheck_attributes' ), 100, 3 );
		add_filter( 'fc_checkout_address_i18n_override_locale_required_attribute', '__return_true', 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Account edit address
		$this->account_edit_address_hooks();
	}

	/**
	 * Add or remove hooks for account edit address.
	 */
	public function account_edit_address_hooks() {
		// Bail if account edit address is disabled for this plugin
		if ( 'yes' !== apply_filters( 'fc_integration_woo_checkout_field_editor_pro_enable_edit_address_changes', 'yes' ) ) { return; }

		// Get the plugin public class object
		self::$thwcfd_public = FluidCheckout::instance()->get_object_by_class_name_from_hooks( 'THWCFD_Public_Checkout' );

		// Bail if class object not found
		if ( ! self::$thwcfd_public ) { return; }

		/**
		 * @see THWCFD_Public_Checkout::define_public_hooks()
		 */
		$hp_billing_fields  = apply_filters( 'thwcfd_billing_fields_priority', 1000 );
		$hp_shipping_fields = apply_filters( 'thwcfd_shipping_fields_priority', 1000 );

		// Add filters to apply changes to the billing and shipping fields on the edit address screen
		add_filter( 'woocommerce_billing_fields', array( $this, 'apply_billing_fields_changes'), $hp_billing_fields, 2 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'apply_shipping_fields_changes'), $hp_shipping_fields, 2 );
	}



	/**
	 * Add custom attributes for email fields.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_mailcheck_attributes( $args, $key, $value ) {
		// Bail if field is not an email field
		if ( ! array_key_exists( 'type', $args ) || 'email' !== $args[ 'type' ] ) { return $args; }

		// Initialize custom attributes argument if not existing yet
		if ( ! array_key_exists( 'custom_attributes', $args ) ) { $args[ 'custom_attributes' ] = array(); }

		// Add mailcheck attributes
		$args[ 'custom_attributes' ] = array_merge( $args[ 'custom_attributes' ], array( 'data-mailcheck' => 1 ) );

		return $args;
	}



	/**
	 * Apply changes to the billing fields on edit address screen.
	 *
	 * @param   array  $fields    Checkout fields arguments.
	 * @param   string  $country  Country code.
	 */
	public function apply_billing_fields_changes( $fields, $country ) {
		// Bail if not on account edit address endpoint
		if ( ! is_wc_endpoint_url('edit-address') ) { return $fields; }

		return self::$thwcfd_public->prepare_address_fields( FluidCheckout_Settings::instance()->get_option( 'wc_fields_billing' ), $country, $fields, 'billing' );
	}



	/**
	 * Apply changes to the shipping fields on edit address screen.
	 *
	 * @param   array  $fields    Checkout fields arguments.
	 * @param   string  $country  Country code.
	 */
	public function apply_shipping_fields_changes( $fields, $country ) {
		// Bail if not on account edit address endpoint
		if ( ! is_wc_endpoint_url('edit-address') ) { return $fields; }

		return self::$thwcfd_public->prepare_address_fields( FluidCheckout_Settings::instance()->get_option( 'wc_fields_shipping' ), $country, $fields, 'shipping' );
	}

}

FluidCheckout_WooCheckoutFieldEditorPro::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: YITH WooCommerce Checkout Manager (by YITH).
 */
class FluidCheckout_YithWooCommerceCheckoutManager extends FluidCheckout {

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

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout field args
		add_filter( 'fc_checkout_address_i18n_override_locale_required_attribute', '__return_true', 10 );

		// Skip optional fields
		add_filter( 'fc_hide_optional_fields_skip_types', array( $this, 'add_optional_fields_skip_types' ), 10 );
		add_filter( 'fc_hide_optional_fields_skip_field', array( $this, 'maybe_skip_hiding_condition_required_fields' ), 10, 4 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'maybe_change_substep_text_extra_fields_skip_list_shipping' ), 100 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'maybe_change_substep_text_extra_fields_skip_list_billing' ), 100 );
		add_filter( 'fc_substep_order_notes_text_lines', array( $this, 'add_substep_text_lines_order_notes' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Plugin's script
		wp_register_script( 'ywccp-front-script', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/yith-woocommerce-checkout-manager/frontend' ), array( 'jquery', 'ywccp-external-script' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-yith-woocommerce-checkout-manager', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/yith-woocommerce-checkout-manager/checkout-validation-yith-woocommerce-checkout-manager' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation', 'ywccp-external-script' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-yith-woocommerce-checkout-manager', 'window.addEventListener("load",function(){CheckoutValidationYithWooCommerceCheckoutManager.init(fcSettings.checkoutValidationYithWooCommerceCheckoutManager);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-yith-woocommerce-checkout-manager' );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationYithWooCommerceCheckoutManager' ] = array(
			'is_vat_validation_enabled' => 'yes' === FluidCheckout_Settings::instance()->get_option( 'ywccp-enable-js-vat-check', 'no' ),
			'validationMessages'  => array(
				'invalid_vat' => __( 'The VAT number you have entered seems to be wrong.', 'yith-woocommerce-checkout-manager' ),
			),
		);

		return $settings;
	}



	/**
	 * Add fields to the optional fields add link skip list.
	 */
	public function add_optional_fields_skip_types( $skip_types ) {
		$skip_types[] = 'heading';
		return $skip_types;
	}

	/**
	 * Maybe skip hiding fields that are "conditionally required".
	 * "Conditionally required" fields set as required only if a certain condition is fulfilled, otherwise they are optional.
	 * 
	 * @param  bool   $skip  Whether to skip hiding the field or not.
	 * @param  string $key   The field key.
	 * @param  array  $args  The field arguments.
	 * @param  mixed  $value The field value.
	 */
	public function maybe_skip_hiding_condition_required_fields( $skip, $key, $args, $value ) {
		// Check if the field is conditionally required
		if ( isset( $args[ 'condition_required' ] ) && $args[ 'condition_required' ] && '0|' !== $args[ 'condition_required' ] ) {
			$skip = true;
		}

		return $skip;
	}



	/**
	* Change billing extra fields to skip for the substep review text.
	*
	* @param   array  $skip_list  List of fields to skip adding to the substep review text.
	*/
	function maybe_change_substep_text_extra_fields_skip_list_billing( $skip_list ) {
		// Bail if function is not available
		if ( ! function_exists( 'ywccp_get_custom_fields' ) ) { return $skip_list; }

		// Get custom fields from the plugin
		$custom_fields = ywccp_get_custom_fields( 'billing' );
		
		// Maybe add fields to skip list
		if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
			$custom_fields_keys = array_keys( $custom_fields );
			$skip_list = array_merge( $skip_list, $custom_fields_keys );
		}

		return $skip_list;
	}

	/**
	* Change shipping extra fields to skip for the substep review text.
	*
	* @param   array  $skip_list  List of fields to skip adding to the substep review text.
	*/
	function maybe_change_substep_text_extra_fields_skip_list_shipping( $skip_list ) {
		// Bail if function is not available
		if ( ! function_exists( 'ywccp_get_custom_fields' ) ) { return $skip_list; }

		// Get custom fields from the plugin
		$custom_fields = ywccp_get_custom_fields( 'shipping' );
		
		// Maybe add fields to skip list
		if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
			$custom_fields_keys = array_keys( $custom_fields );
			$skip_list = array_merge( $skip_list, $custom_fields_keys );
		}

		return $skip_list;
	}



	/**
	 * Add the order notes substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_order_notes( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get order notes
		$order_notes = WC()->checkout()->get_value( 'order_comments' );

		// Add order notes field value
		if ( ! empty( $order_notes ) ) {
			$review_text_lines[] = $order_notes;
		}

		// Get custom fields from the plugin
		$custom_fields = array();
		if ( function_exists( 'ywccp_get_custom_fields' ) ) {
			$custom_fields = ywccp_get_custom_fields( 'additional' );
		}

		// Iterate custom fields
		if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $field_key => $field_args ) {
				// Get the field value
				$field_value = WC()->checkout()->get_value( $field_key );

				// Skip if the field value is empty
				if ( empty( $field_value ) ) { continue; }

				// Add custom field value to the review text lines
				$review_text_lines[] = FluidCheckout_Steps::instance()->get_field_display_value( $field_value, $field_key, $field_args );
			}
		}

		// Maybe show "No order notes" notice
		if ( empty( $review_text_lines ) ) {
			$review_text_lines[] = apply_filters( 'fc_no_order_notes_order_review_notice', FluidCheckout_Steps::instance()->get_no_substep_review_text_notice( 'order_notes' ) );
		}

		return $review_text_lines;
	}

}

FluidCheckout_YithWooCommerceCheckoutManager::instance();

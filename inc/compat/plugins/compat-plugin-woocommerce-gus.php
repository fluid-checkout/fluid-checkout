<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Woocommerce GUS/Regon (by gotoweb.pl).
 */
class FluidCheckout_WoocommerceGUS extends FluidCheckout {

	/**
	 * VAT field key from the plugin.
	 */
	public const VAT_FIELD_KEY = 'gus_nip_value';



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

		// VAT field args
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_vat_nip_field_properties' ), 10 );

		// Checkout field args
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_gus_attribute' ), 100 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete_billing_address' ), 10 );

		// Add hidden fields
		add_action( 'woocommerce_checkout_billing', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Add hidden fields fragment
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_hidden_fields_fragment' ), 10 );

		// Review text lines
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'remove_phone_number_from_text_lines' ), 10 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'add_substep_text_lines' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Plugin's script
		wp_register_script( 'frontend-ajax-gus', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-gus/gus' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-woocommerce-gus', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-gus/checkout-validation-woocommerce-gus' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-woocommerce-gus', 'window.addEventListener("load",function(){CheckoutValidationWooCommerceGUS.init(fcSettings.checkoutValidationWooCommerceGUS);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-woocommerce-gus' );
	}

	/**
	 * Maybe enqueue assets.
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
		// Bail if plugin function is not available
		if ( ! method_exists( 'wooGus\Gus\Connect', 'getOptions' ) ) { return $settings; }

		// Add validation settings
		$settings[ 'checkoutValidationWooCommerceGUS' ] = array(
			'validationMessages'  => array(
				'error_1' => __( 'Wtyczka nie została aktywowana', 'woogus'),
				'error_2' => wooGus\Gus\Connect::getOptions( 'wprowadz_poprawny_nip' ),
				'error_3' => __( 'Serwer GUS nie odpowiada, prosimy spróbować ponownie później', 'woogus'),
				'to_short_nip' => __( 'Podany numer nip jest zbyt krótki', 'woogus'),
			),
		);

		return $settings;
	}



	/**
	 * Maybe change the VAT/NIP field arguments.
	 *
	 * @param  array  $field_groups   The checkout fields.
	 */
	public function maybe_change_vat_nip_field_properties( $field_groups ) {
		// Bail if VAT field from the plugin is not available
		if ( ! array_key_exists( 'billing', $field_groups ) || ! array_key_exists( self::VAT_FIELD_KEY, $field_groups[ 'billing' ] ) ) { return $field_groups; }

		// Initalize variables
		$field_args = $field_groups[ 'billing' ][ self::VAT_FIELD_KEY ];

		// Set new priority
		$field_args[ 'priority' ] = 230; // TODO: Remove this after fixing the fields display order caveat

		// Set loading indicator on change
		$class_args = array_merge( $field_args[ 'class' ], array( 'update_totals_on_change', 'loading_indicator_on_change' ) );
		if ( class_exists( 'FluidCheckout_CheckoutFields' ) ) {
			$class_args = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $field_args[ 'class' ], $class_args );
		}
		$field_args[ 'class' ] = $class_args;

		// Update field args
		$field_groups[ 'billing' ][ self::VAT_FIELD_KEY ] = $field_args;

		return $field_groups;
	}



	/**
	 * Maybe add custom attribute from the plugin to the checkout fields.
	 * This is done through inline JS in the plugin on `DOMContentLoaded` event.
	 * PARTLY ADAPTED FROM: add_custom_js_after_checkout_form().
	 *
	 * @param  array  $field_groups   The checkout fields.
	 */
	public function maybe_add_gus_attribute( $field_groups ) {
		// Get checkout settings from plugin
		$settings = FluidCheckout_Settings::instance()->get_option( 'inspire_checkout_fields_settings', array() );

		// Use different option if required data is missing
		if ( empty( $settings ) || ! isset( $settings[ 'billing' ][ 'billing_address_1' ][ 'gus' ] ) ) {
			$settings = FluidCheckout_Settings::instance()->get_option( 'gus_inputs', array() );
		}

		// Iterate through settings to get field IDs to add attribute
		foreach ( $settings as $section_name => $section ) {
			foreach ( $section as $field_name => $field_data ) {
				if ( isset( $field_data[ 'gus' ] ) ) {
					// Skip if section or field is not available
					if ( ! array_key_exists( $section_name, $field_groups ) || ! array_key_exists( $field_name, $field_groups[ $section_name ] ) ) { continue; }

					// Ensure 'custom_attributes' is initialized as array
					if ( ! isset( $field_groups[ $section_name ][ $field_name ][ 'custom_attributes' ] ) || ! is_array( $field_groups[ $section_name ][ $field_name ][ 'custom_attributes' ] ) ) {
						$field_groups[ $section_name ][ $field_name ][ 'custom_attributes' ] = array();
					}

					// Add 'data-gus' attribute
					$field_groups[ $section_name ][ $field_name ][ 'custom_attributes' ] = array_merge( $field_groups[ $section_name ][ $field_name ][ 'custom_attributes' ], array( 'data-gus' => $field_data[ 'gus' ] ) );
				}
			}
		}

		return $field_groups;
	}



	/**
	 * Validate the VAT number.
	 * Methods from wooGus\Gus\Connect class are used as a reference.
	 *
	 * @param  string  $vat_number  The VAT number to validate.
	 */
	public function validate_vat_number( $vat_number ) {
		// Initialize variable
		$validation = array( 
			'is_valid' => true, 
			'error_code' => '',
		);

		// Bail if plugin functions are not available
		if ( ! method_exists( 'wooGus\Gus\Connect', 'getOptions' ) || ! method_exists( 'wooGus\Gus\Connect', 'Connect' ) ) { return $validation; }

		// Check if validation is required
		$is_length_check_required = wooGus\Gus\Connect::getOptions( 'nip_validate' );
		$is_validation_required = wooGus\Gus\Connect::getOptions( 'nip_validate_gus' );

		// Bail if validation is not required
		if ( ! $is_length_check_required && ! $is_validation_required ) { return $validation; }

		// Remove all non-numeric characters from the vat number
		$vat_number = preg_replace( '/[^\d]+/', '', $vat_number );

		// Bail if length check is required and VAT number is too short
		// The plugin only checks if the VAT number is too short, so perform the same check here
		if ( $is_length_check_required && strlen( $vat_number ) < 10 ) {
			$validation = array( 
				'is_valid' => false, 
				'error_code' => 'to_short_nip',
			);

			return $validation;
		}

		// Bail if the VAT number is too long and set validation message to "error_2" (invalid VAT number) as it's done in the plugin's JS
		if ( strlen( $vat_number ) > 10 ) {
			$validation = array( 
				'is_valid' => false, 
				'error_code' => 'error_2',
			);

			return $validation;
		}

		// Validate VAT number through GUS API
		$response = wooGus\Gus\Connect::Connect( $vat_number );
		$response = wp_remote_retrieve_body( $response );

		// Check if responds contains error codes
		if ( preg_match( '/error_(1|2|3)/', $response, $matches ) ) {
			$validation = array( 
				'is_valid' => false, 
				'error_code' => $matches[0],
			);
		}

		return $validation;
	}



	/**
	 * Maybe set the billing address substep step as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_billing_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get field values
		$billing_country = WC()->checkout->get_value( 'billing_country' );
		$vat_number = WC()->checkout->get_value( self::VAT_FIELD_KEY );

		// Bail if Poland is not selected as billing country
		if ( empty( $billing_country ) || 'PL' !== $billing_country ) { return $is_substep_complete; }

		// Bail if VAT number is empty
		if ( empty( $vat_number ) ) { return false; }

		// Validate VAT number
		$validation = $this->validate_vat_number( $vat_number );

		// Maybe set step as incomplete
		if ( ! $validation[ 'is_valid' ] ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get field values
		$billing_country = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_country' );
		$vat_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( self::VAT_FIELD_KEY );

		// Set default values
		$is_valid = true;
		$error_code = '';
		
		// Maybe validate VAT number
		if ( ! empty( $vat_number ) && 'PL' === $billing_country ) {
			$validation = $this->validate_vat_number( $vat_number );
			$is_valid = $validation[ 'is_valid' ];
			$error_code = $validation[ 'error_code' ];
		}

		// Output custom hidden fields
		echo '<div id="woocommerce_gus-custom_checkout_fields" class="form-row fc-no-validation-icon woocommerce_gus-custom_checkout_fields">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="validate-woocommerce-gus" name="validate-woocommerce-gus" value="'. $is_valid .'" data-error="'. $error_code .'" class="validate-woocommerce-gus">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Add hidden fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_hidden_fields_fragment( $fragments ) {
		// Get custom hidden fields HTML
		ob_start();
		$this->output_custom_hidden_fields();
		$html = ob_get_clean();

		// Add fragment
		$fragments[ '#woocommerce_gus-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Remove VAT field from the substep review text lines.
	 * 
	 * @param  array  $field_keys_skip_list  The list of field keys to skip in the substep review text.
	 */
	public function remove_phone_number_from_text_lines( $field_keys_skip_list ) {
		$field_keys_skip_list[] = self::VAT_FIELD_KEY;
		return $field_keys_skip_list;
	}

	/**
	 * Add the VAT field value to the substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines( $review_text_lines = array() ) {
		// Bail if default billing field is not disabled in the plugin setttings to avoid phone number duplication
		if ( isset( $this->plugin_settings[ 'wc-chk-bphone' ] ) && 'nothing' === $this->plugin_settings[ 'wc-chk-bphone' ] ) { return $review_text_lines; }

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get prefix from the plugin
		$prefix = __( 'NIP: ', 'woogus' );

		// Get entered VAT number
		$vat_number = WC()->checkout->get_value( self::VAT_FIELD_KEY );

		// Add phone number with country code to the review text
		$review_text_lines[] = $prefix . $vat_number;

		return $review_text_lines;
	}



	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'input[name="validate-woocommerce-gus"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="validate-woocommerce-gus"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="validate-woocommerce-gus"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_WoocommerceGUS::instance();

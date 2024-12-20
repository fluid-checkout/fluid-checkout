<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Checkout Field Editor for WooCommerce Pro (by Themehigh).
 */
class FluidCheckout_WooCommerceCheckoutFieldEditorPRO extends FluidCheckout {

	/**
	 * Holds the instance of the public checkout class from the WooCommerce Checkout Field Editor PRO plugin.
	 */
	public $thwcfe = null;

	/**
	 * Holds the cached values.
	 */
	private static $cached_values = array();



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

		// Change priority for Checkout Field Editor plugin hooks
		add_filter( 'thwcfd_woocommerce_checkout_fields_hook_priority', array( $this, 'change_hook_priority' ), 10 );

		// Skip optional fields
		add_filter( 'fc_hide_optional_fields_skip_types', array( $this, 'add_optional_fields_skip_types' ), 10 );

		// Enhanced select fields
		add_filter( 'option_thwcfe_advanced_settings', array( $this, 'maybe_disable_enhanced_select2_fields_option' ), 10, 3 );
		
		// Add select2 field types
		add_filter( 'fc_select2_field_types', array( $this, 'add_select2_field_types' ), 10 );
		add_filter( 'fc_no_validation_icon_field_types', array( $this, 'add_no_validation_icon_field_types' ), 10 );

		// Persisted fields
		add_filter( 'thwcfe_woocommerce_form_field_value', array( FluidCheckout_Steps::instance(), 'change_default_checkout_field_value_from_session_or_posted_data' ), 100, 2 );
		add_filter( 'fc_customer_persisted_data_clear_fields_order_processed', array( $this, 'change_customer_persisted_data_clear_fields_order_processed' ), 10 );

		// Checkout field args
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_mailcheck_attributes' ), 100, 3 );
		add_filter( 'fc_checkout_field_args', array( $this, 'change_checkout_field_args' ), 120, 3 ); // Needs to run after the hooks from the compat with Brazilian Market

		// Formatted address
		add_filter( 'fc_formatted_address_replacements_custom_field_keys', array( $this, 'add_custom_fields_for_formatted_address_replacements' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Set instance of the plugin class
		$this->thwcfe = FluidCheckout::instance()->get_object_by_class_name_from_hooks( 'THWCFE_Public_Checkout' );

		// Bail if plugin class is not available
		if ( null === $this->thwcfe ) { return; }

		// Get hook priority
		$hp_cf = apply_filters( 'thwcfd_woocommerce_checkout_fields_hook_priority', $this->change_hook_priority() );

		// Output hidden fields
		add_filter( 'thwcfe_hidden_fields_display_position', array( $this, 'change_hidden_fields_display_position_hook' ), 10 );

		// Set substeps as incomplete
		add_filter( 'fc_is_substep_complete_contact', array( $this, 'maybe_set_substep_incomplete_contact' ), 10 );
		add_filter( 'fc_is_substep_complete_shipping_address', array( $this, 'maybe_set_substep_incomplete_shipping_address' ), 10 );
		add_filter( 'fc_is_substep_complete_order_notes', array( $this, 'maybe_set_substep_incomplete_order_notes' ), 10 );
		add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete_billing_address' ), 10 );
	}



	/**
	 * Change the priority to run the Checkout Field Editor plugin hooks before Fluid Checkout field argument changes.
	 *
	 * @param   int  $priority  Hook priority.
	 */
	public function change_hook_priority( $priority = 90 ) {
		return 90;
	}

	/**
	 * Change the hook to display the hidden fields.
	 */
	public function change_hidden_fields_display_position_hook( $hidden_fields_display_position ) {
		$hidden_fields_display_position = 'fc_checkout_after';
		return $hidden_fields_display_position;
	}



	/**
	 * Add fields to the optional fields add link skip list.
	 */
	public function add_optional_fields_skip_types( $skip_types ) {
		$skip_types = array_merge( $skip_types, array( 'heading', 'label', 'checkboxgroup' ) );
		return $skip_types;
	}



	/**
	 * Maybe disable enhanced select2 fields option for Checkout Field Editor for WooCommerce
	 * when using Fluid Checkout enhanced select fields.
	 *
	 * @param  mixed   $value   The value to return instead of the option value.
	 * @param  string  $option  Option name.
	 */
	public function maybe_disable_enhanced_select2_fields_option( $value, $option ) {
		// Bail if Fluid Checkout enhanced select fields are not enabled
		if ( ! FluidCheckout_Settings::instance()->get_option( 'fc_use_enhanced_select_components' ) ) { return $value; }

		// Maybe convert `$value` into an array
		if ( ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
			if ( ! is_array( $value ) ) {
				$value = array();
			}
		}

		// Disable enhanced select2 fields option for Checkout Field Editor for WooCommerce
		$value[ 'disable_select2_for_select_fields' ] = 'yes';

		return $value;
	}



	/**
	 * Add field types to the select2 field types list.
	 */
	public function add_select2_field_types( $select2_field_types ) {
		$select2_field_types = array_merge( $select2_field_types, array( 'multiselect' ) );
		return $select2_field_types;
	}



	/**
	 * Add field types to hide validation icon.
	 */
	public function add_no_validation_icon_field_types( $no_validation_icon_field_types ) {
		$no_validation_icon_field_types = array_merge( $no_validation_icon_field_types, array( 'checkboxgroup' ) );
		return $no_validation_icon_field_types;
	}



	/**
	 * Maybe set a step as incomplete if required fields from custom sections are missing a value.
	 *
	 * @param   bool   $is_substep_complete  Whether the substep is to be considered complete or not.
	 * @param   array  $hooks             The checkout hook names where to check for custom sections.
	 */
	public function maybe_set_step_incomplete( $is_substep_complete, $hooks = array() ) {
		// Bail if checkout editor object not available
		if ( null === $this->thwcfe ) { return $is_substep_complete; }

		// Bail if hooks is empty or not an array
		if ( ! is_array( $hooks ) || empty( $hooks ) ) { return $is_substep_complete; }
		
		// Get section ids
		$sections = array();
		foreach ( $hooks as $hook_name ) {
			$hook_sections = $this->thwcfe->get_custom_sections_by_hook( $hook_name );
			if ( is_array( $hook_sections ) ) {
				$sections = array_merge( $sections, $hook_sections );
			}
		}

		// Get cart info
		$cart_info = THWCFE_Utils::get_cart_summary();

		// Iterate sections
		foreach ( $sections as $sname ) {
			// Maybe return from cache
			$cache_key = 'section_incomplete_' . $sname;
			if ( array_key_exists( $cache_key, self::$cached_values ) ) {
				return self::$cached_values[ $cache_key ];
			}

			// Get section
			$section = THWCFE_Utils::get_checkout_section( $sname, $cart_info );

			// Skip invalid sections
			if ( ! THWCFE_Utils_Section::is_valid_section( $section ) ){ continue; }
			
			// Maybe skip section without fields
			if ( ! property_exists( $section, 'fields' ) || ! is_array( $section->fields ) || 0 === count( $section->fields ) ) { continue; }

			// Get section fields
			$fields = THWCFE_Utils_Section::get_fieldset( $section, $cart_info );
			$fields = THWCFE_Utils_Repeat::prepare_repeat_fields_set( $fields );

			// Validate required fields
			foreach( $fields as $field_key => $field_args ) {
				// Get required property
				// Use loose comparison for `required` attribute to allow type casting as some plugins use `1` instead of `true` to set fields as required.
				$required = array_key_exists( 'required', $field_args ) && true == $field_args[ 'required' ];

				// Skip optional fields
				if ( ! $required ) { continue; }

				// Check field value
				$field_value = WC()->checkout->get_value( $field_key );
				if ( empty( $field_value ) && 0 == strlen( strval( $field_value ) ) ) {
					// Update cache
					self::$cached_values[ $cache_key ] = false;

					return false;
				}
			}

			// Validate phone fields
			if ( method_exists( FluidCheckout_Validation::instance(), 'is_valid_phone_number' ) ) {
				foreach( $fields as $field_key => $field_args ) {
					// Skip fields not marked as phone validation
					$format = array_filter( isset( $field_args['validate'] ) ? (array) $field_args['validate'] : array() );
					if ( ! in_array( 'phone', $format, true ) ) { continue; }
	
					// Check field value
					$field_value = WC()->checkout->get_value( $field_key );
					if ( ! FluidCheckout_Validation::instance()->is_valid_phone_number( $field_value ) ) {
						// Update cache
						self::$cached_values[ $cache_key ] = false;

						return false;
					}
				}
			}
		}

		return $is_substep_complete;
	}

	/**
	 * Maybe set the contact substep as incomplete if required fields from custom sections are missing a value.
	 *
	 * @param   bool   $is_substep_complete  Whether the substep is to be considered complete or not.
	 */
	public function maybe_set_substep_incomplete_contact( $is_substep_complete ) {
		// Maybe return from cache
		$cache_key = 'substep_incomplete_contact';
		if ( array_key_exists( $cache_key, self::$cached_values ) ) {
			return self::$cached_values[ $cache_key ];
		}

		// Get hooks to check for custom sections
		$hooks = array( 'before_customer_details' );
		if ( ! is_user_logged_in() ) {
			$hooks[] = 'before_checkout_registration_form';
			$hooks[] = 'after_checkout_registration_form';
		}

		// Check if custom sections are complete
		$is_substep_complete = $this->maybe_set_step_incomplete( $is_substep_complete, $hooks );

		// Update cache
		self::$cached_values[ $cache_key ] = $is_substep_complete;

		return $is_substep_complete;
	}

	/**
	 * Maybe set the shipping address substep as incomplete if required fields from custom sections are missing a value.
	 *
	 * @param   bool   $is_substep_complete  Whether the substep is to be considered complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_address( $is_substep_complete ) {
		// Maybe return from cache
		$cache_key = 'substep_incomplete_shipping_address';
		if ( array_key_exists( $cache_key, self::$cached_values ) ) {
			return self::$cached_values[ $cache_key ];
		}

		// Check if custom sections are complete
		$hooks = array( 'before_checkout_shipping_form', 'after_checkout_shipping_form' );
		$is_substep_complete = $this->maybe_set_step_incomplete( $is_substep_complete, $hooks );

		// Update cache
		self::$cached_values[ $cache_key ] = $is_substep_complete;

		return $is_substep_complete;
	}

	/**
	 * Maybe set the order notes substep as incomplete if required fields from custom sections are missing a value.
	 *
	 * @param   bool   $is_substep_complete   Whether the substep is to be considered complete or not.
	 */
	public function maybe_set_substep_incomplete_order_notes( $is_substep_complete ) {
		// Maybe return from cache
		$cache_key = 'substep_incomplete_order_notes';
		if ( array_key_exists( $cache_key, self::$cached_values ) ) {
			return self::$cached_values[ $cache_key ];
		}

		// Check if custom sections are complete
		$hooks = array( 'before_order_notes', 'after_order_notes' );
		$is_substep_complete = $this->maybe_set_step_incomplete( $is_substep_complete, $hooks );

		// Update cache
		self::$cached_values[ $cache_key ] = $is_substep_complete;

		return $is_substep_complete;
	}

	/**
	 * Maybe set the billing address substep as incomplete if required fields from custom sections are missing a value.
	 *
	 * @param   bool   $is_substep_complete  Whether the substep is to be considered complete or not.
	 */
	public function maybe_set_substep_incomplete_billing_address( $is_substep_complete ) {
		// Maybe return from cache
		$cache_key = 'substep_incomplete_billing_address';
		if ( array_key_exists( $cache_key, self::$cached_values ) ) {
			return self::$cached_values[ $cache_key ];
		}

		// Check if custom sections are complete
		$hooks = array( 'after_customer_details', 'before_checkout_billing_form', 'after_checkout_billing_form' );
		$is_substep_complete = $this->maybe_set_step_incomplete( $is_substep_complete, $hooks );

		// Update cache
		self::$cached_values[ $cache_key ] = $is_substep_complete;

		return $is_substep_complete;
	}



	/**
	 * Change the list of fields to clear from session after placing an order, adding the custom fields not marked to be saved to the user meta data.
	 *
	 * @param   array  $clear_field_keys  Checkout field keys to clear from the session after placing an order.
	 */
	public function change_customer_persisted_data_clear_fields_order_processed( $clear_field_keys ) {
		// Define skip fields
		$clear_field_keys_skip_list = apply_filters( 'fc_thwcfe_clear_field_keys_skip_list', array(
			'billing_email',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_phone',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_country',
			'billing_postcode',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_phone',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_country',
			'shipping_postcode',
		) );

		// Get all checkout fields
		$field_groups = WC()->checkout()->get_checkout_fields();

		// Iterate checkout fields
		foreach ( $field_groups as $group_key => $fields ) {
			foreach( $fields as $field_key => $field_args ) {
				// Skip fields from the skip list
				if ( in_array( $field_key, $clear_field_keys_skip_list ) ) { continue; }

				// Skip fields marked to save to user meta
				if ( ! array_key_exists( 'user_meta', $field_args ) || ! empty( $field_args[ 'user_meta' ] ) ) { continue; }

				$clear_field_keys[] = $field_key;
			}
		}

		return $clear_field_keys;
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
	 * Change checkout fields args.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function change_checkout_field_args( $field_args ) {
		// Maybe return from cache
		$cache_key = 'checkout_field_args';
		if ( array_key_exists( $cache_key, self::$cached_values ) ) {
			return self::$cached_values[ $cache_key ];
		}

		// Remove previous priority changes, letting Checkout Field Editor plugin manage it
		foreach ( $field_args as $field_key => $old_args ) {
			$new_args = $old_args;
			unset( $new_args[ 'priority' ] );
			$field_args[ $field_key ] = $new_args;
		}

		// Update cache
		self::$cached_values[ $cache_key ] = $field_args;

		return $field_args;
	}



	/**
	 * Add custom address fields for the formatted addresses replacements.
	 *
	 * @param   array  $replacements  Custom field keys to be added to formatted address replacements.
	 */
	public function add_custom_fields_for_formatted_address_replacements( $custom_field_keys ) {
		// Bail if plugin class is not available
		if ( null === $this->thwcfe ) { return $custom_field_keys; }

		// Get custom field keys from settings
		$billing_keys  = $this->thwcfe->get_settings( 'custom_billing_address_keys' );
		$shipping_keys = $this->thwcfe->get_settings( 'custom_shipping_address_keys' );

		// Maybe add custom billing keys
		if ( is_array( $billing_keys ) && ! empty( $billing_keys ) ) {
			$custom_field_keys = array_merge( $custom_field_keys, $billing_keys );
		}

		// Maybe add custom shipping keys
		if ( is_array( $shipping_keys ) && ! empty( $shipping_keys ) ) {
			$custom_field_keys = array_merge( $custom_field_keys, $shipping_keys );
		}

		return $custom_field_keys;
	}

}

FluidCheckout_WooCommerceCheckoutFieldEditorPRO::instance();

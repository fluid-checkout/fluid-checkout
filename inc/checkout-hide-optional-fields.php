<?php
defined( 'ABSPATH' ) || exit;

/**
 * Customizations to the checkout optional fields.
 */
class FluidCheckout_CheckoutHideOptionalFields extends FluidCheckout {

	/**
	 * Class for an address field the country locale hides.
	 *
	 * `address-i18n.js` adds and removes this class when the customer changes country.
	 * Keep in sync with `localeHiddenFieldClass` in `js-src/address-i18n.js`.
	 *
	 * @since 5.0.1
	 */
	public const ADDRESS_LOCALE_HIDDEN_CLASS = 'fc-address-locale-hidden';



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
		// Bail if feature is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_hide_optional_fields' ) ) { return false; }

		return true;
	}



	/**
	 * Whether optional fields should be wrapped as expansible sections on the current page.
	 *
	 * Expansible markup needs Fluid Checkout collapsible assets, which load only on checkout and
	 * selected account pages. Outside those pages both the "Add ..." toggle and the field stay visible.
	 */
	public function should_hide_optional_fields_on_current_page() {
		// Bail if WooCommerce page helpers are not available
		if ( ! function_exists( 'is_checkout' ) ) { return false; }

		// Checkout form (not thank you or pay-for-order)
		if ( is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page() ) { return true; }

		// Account pages that also load Fluid Checkout form assets
		if ( function_exists( 'is_account_page' ) && is_account_page() && ( is_wc_endpoint_url( 'edit-address' ) || is_wc_endpoint_url( 'add-payment-method' ) ) ) { return true; }

		return (bool) apply_filters( 'fc_should_hide_optional_fields_on_current_page', false );
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// Bail if feature is not enabled
		if( ! $this->is_feature_enabled() ) { return; }

		// WooCommerce fields output
		add_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// WooCommerce fields output
		remove_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
	}



	/**
	 * Get the list of field ids to skip hidding when hide optional fields is enabled.
	 *
	 * @return  Array  List of field ids to skip hidding.
	 */
	public function get_hide_optional_fields_skip_list() {
		// Always skip these fields
		$skip_list = array( 'state', 'billing_state', 'shipping_state' );

		// Maybe skip "address line 2" fields
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_hide_optional_fields_skip_address_2' ) ) {
			$skip_list[] = 'address_2';
			$skip_list[] = 'shipping_address_2';
			$skip_list[] = 'billing_address_2';
		}

		/**
		 * Filters the field keys that are not hidden when optional fields are collapsed.
		 *
		 * @since 1.2.0
		 *
		 * @param string[] $skip_list Field keys to skip.
		 */
		return apply_filters( 'fc_hide_optional_fields_skip_list', $skip_list );
	}



	/**
	 * Whether "Add" link button labels should be lowercased for the current locale.
	 *
	 * @return bool
	 */
	public function should_lowercase_add_link_label() {
		// Get current locale
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = str_replace( '-', '_', $locale );

		// Get language code (part before underscore)
		$language = strtolower( strtok( $locale, '_' ) );

		/**
		 * Filters the locales and language codes that keep "Add" link labels capitalized.
		 *
		 * @since 5.0.0
		 */
		$preserve_case_languages = apply_filters(
			'fc_optional_fields_link_label_preserve_case_languages',
			array(
				// Capitalize every noun
				'de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal', 'lb_LU',
				// Turkish and Azerbaijani dotted/dotless i
				'tr_TR', 'az', 'az_TR',
				// Dutch IJ digraph
				'nl_NL', 'nl_NL_formal', 'nl_BE',
				// Greek: accents and final sigma
				'el',
				// Irish: mutation prefixes like tSeirbhís
				'ga',
				// Georgian: uppercase becomes heading-only capitals
				'ka_GE',
				// Lithuanian dotted i and Armenian ligature (rarely matters)
				'lt_LT', 'hy',
			)
		);
		$preserve_case_languages = array_map( 'strtolower', (array) $preserve_case_languages );
		$locale_key = strtolower( $locale );

		// Lowercase unless the language or the full locale preserves noun capitalization
		$should_lowercase = ! in_array( $language, $preserve_case_languages, true ) && ! in_array( $locale_key, $preserve_case_languages, true );

		/**
		 * Filters whether "Add" link labels are lowercased for the current locale.
		 *
		 * @since 5.0.0
		 *
		 * @param  string  $locale    Current locale.
		 * @param  string  $language  Language code.
		 */
		return (bool) apply_filters( 'fc_optional_fields_link_label_lowercase', $should_lowercase, $locale, $language );
	}

	/**
	 * Maybe lowercase a label used in an "Add" link button.
	 *
	 * @param   string  $label  The field label.
	 */
	public function maybe_lowercase_add_link_label( $label ) {
		// Bail if should not lowercase
		if ( ! $this->should_lowercase_add_link_label() ) { return $label; }

		// Lowercase with multibyte support when available
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $label, 'UTF-8' );
		}

		return strtolower( $label );
	}



	/**
	 * Whether a locale `hidden` flag means the field is hidden.
	 *
	 * @param   mixed  $hidden  Locale or field-arg value.
	 *
	 * @since 5.0.1
	 */
	public function is_locale_hidden_value( $hidden ) {
		return true === $hidden || 1 === $hidden || '1' === $hidden || 'yes' === $hidden || 'true' === $hidden;
	}

	/**
	 * Address group used to resolve the country for a checkout field key.
	 *
	 * @param   string  $field_key  Checkout field key.
	 *
	 * @return  string  `billing` or `shipping`.
	 *
	 * @since 5.0.1
	 */
	public function get_address_type_for_field_key( $field_key ) {
		if ( 0 === strpos( $field_key, 'shipping_' ) ) { return 'shipping'; }
		if ( 0 === strpos( $field_key, 'billing_' ) ) { return 'billing'; }

		// Unprefixed fields on the account edit-address form follow the address being edited.
		if ( is_wc_endpoint_url( 'edit-address' ) ) {
			global $wp;

			if ( isset( $wp->query_vars[ 'edit-address' ] ) ) {
				$load_address = wc_edit_address_i18n( sanitize_title( $wp->query_vars[ 'edit-address' ] ), true );

				if ( in_array( $load_address, array( 'billing', 'shipping' ), true ) ) {
					return $load_address;
				}
			}
		}

		return 'billing';
	}

	/**
	 * Locale field key for a checkout field key (`billing_postcode` to `postcode`).
	 *
	 * @param   string  $field_key  Checkout field key.
	 *
	 * @since 5.0.1
	 */
	public function get_locale_address_field_key( $field_key ) {
		if ( 0 === strpos( $field_key, 'shipping_' ) ) { return substr( $field_key, strlen( 'shipping_' ) ); }
		if ( 0 === strpos( $field_key, 'billing_' ) ) { return substr( $field_key, strlen( 'billing_' ) ); }

		return $field_key;
	}

	/**
	 * Country used to read address locale rules for a checkout field.
	 *
	 * @param   string  $field_key  Checkout field key.
	 *
	 * @since 5.0.1
	 */
	public function get_country_for_address_field( $field_key ) {
		$address_type = $this->get_address_type_for_field_key( $field_key );
		$country_key = $address_type . '_country';
		$country = '';

		if ( WC()->checkout() ) {
			$country = WC()->checkout()->get_value( $country_key );
		}

		if ( ! $country && WC()->customer instanceof WC_Customer ) {
			if ( 'shipping' === $address_type ) {
				$country = WC()->customer->get_shipping_country();
			} else {
				$country = WC()->customer->get_billing_country();
			}
		}

		if ( ! $country ) {
			$country = WC()->countries->get_base_country();
		}

		return $country;
	}

	/**
	 * Merged default and country address-locale entry for a checkout field.
	 *
	 * @param   string  $field_key  Checkout field key.
	 *
	 * @return  array
	 *
	 * @since 5.0.1
	 */
	public function get_merged_address_locale_field( $field_key ) {
		$locale_key = $this->get_locale_address_field_key( $field_key );
		$country = $this->get_country_for_address_field( $field_key );
		$locale = WC()->countries->get_country_locale();
		$field_locale = array();

		if ( isset( $locale[ 'default' ][ $locale_key ] ) && is_array( $locale[ 'default' ][ $locale_key ] ) ) {
			$field_locale = $locale[ 'default' ][ $locale_key ];
		}

		if ( $country && isset( $locale[ $country ][ $locale_key ] ) && is_array( $locale[ $country ][ $locale_key ] ) ) {
			$field_locale = array_merge( $field_locale, $locale[ $country ][ $locale_key ] );
		}

		return $field_locale;
	}

	/**
	 * Whether WooCommerce's address locale hides this field for the selected country.
	 *
	 * Country locale entries such as postcode for the United Arab Emirates set `hidden` and leave
	 * the field optional. Field args include `hidden` when WooCommerce copies it onto the field.
	 * When the arg is absent, or when WooCommerce seeded `hidden => false` before applying the locale
	 * overlay, read `hidden` from the merged country locale.
	 *
	 * @param   string  $field_key  Checkout field key.
	 * @param   array   $args       Field arguments.
	 *
	 * @since 5.0.1
	 */
	public function is_address_locale_field_hidden( $field_key, $args ) {
		if ( is_array( $args ) && array_key_exists( 'hidden', $args ) && $this->is_locale_hidden_value( $args[ 'hidden' ] ) ) {
			return true;
		}

		$field_locale = $this->get_merged_address_locale_field( $field_key );

		if ( ! array_key_exists( 'hidden', $field_locale ) ) { return false; }

		return $this->is_locale_hidden_value( $field_locale[ 'hidden' ] );
	}

	/**
	 * Add the locale-hidden class to the first form row in a field's markup.
	 *
	 * @param   string  $field  Field HTML.
	 *
	 * @since 5.0.1
	 */
	public function add_address_locale_hidden_class_to_field( $field ) {
		$class_name = self::ADDRESS_LOCALE_HIDDEN_CLASS;

		if ( false !== strpos( $field, $class_name ) ) { return $field; }

		$needle = 'class="form-row ';
		$position = strpos( $field, $needle );
		if ( false !== $position ) {
			return substr_replace( $field, 'class="form-row ' . $class_name . ' ', $position, strlen( $needle ) );
		}

		$needle = 'class="form-row"';
		$position = strpos( $field, $needle );
		if ( false !== $position ) {
			return substr_replace( $field, 'class="form-row ' . $class_name . '"', $position, strlen( $needle ) );
		}

		return $field;
	}

	/**
	 * Hide a field row when the address locale hides it and it is not wrapped in an "Add ..." section.
	 *
	 * @param   string  $field              Field HTML.
	 * @param   bool    $is_locale_hidden   Whether the address locale hides the field.
	 *
	 * @since 5.0.1
	 */
	public function maybe_hide_unwrapped_locale_field( $field, $is_locale_hidden ) {
		if ( ! $is_locale_hidden ) { return $field; }

		return $this->add_address_locale_hidden_class_to_field( $field );
	}



	/**
	 * Get the checkout fields args.
	 *
	 * @param   string  $field  Field html markup to be changed.
	 * @param   string  $key    Field key.
	 * @param   array   $args   Field args.
	 * @param   mixed   $value  Value of the field. Defaults to `null`.
	 */
	public function add_optional_form_field_link_button( $field, $key, $args, $value ) {
		// Bail if the current page does not load expansible section assets
		if ( ! $this->should_hide_optional_fields_on_current_page() ) { return $field; }

		// Country locale can hide a field while still leaving it optional, which is what creates the "Add ..." link.
		$is_locale_hidden = $this->is_address_locale_field_hidden( $key, $args );

		// Bail if field is required.
		// Use loose comparison for `required` attribute to allow type casting as some plugins use `1` instead of `true` to set fields as required.
		// A locale-hidden field is not required for the selected country, even if the field args still say it is.
		if ( ! $is_locale_hidden && array_key_exists( 'required', $args ) && true == $args[ 'required' ] ) { return $field; }

		// Bail if the field already has a value. Locale-hidden fields are cleared when the country is applied,
		// so they stay in an "Add ..." section and the link can come back for a country that shows the field.
		if ( ! empty( $value ) && ! $is_locale_hidden ) { return $field; }

		// Bail if field has already been hidden
		// Needed for compatibility with plugins that call `woocommerce_form_field` multiple times for the same field
		if ( false !== strpos( $field, 'id="fc-expansible-form-section__toggle--' . $key ) ) { return $field; }

		// Maybe skip optional field by type
		/**
		 * Filters the field types that are not collapsed behind an "Add" link.
		 *
		 * @since 1.2.0
		 *
		 * @param array $skip Whether to skip the default behavior.
		 */
		if ( in_array( $args[ 'type' ], apply_filters( 'fc_hide_optional_fields_skip_types', array( 'state', 'country', 'select', 'checkbox', 'radio', 'hidden' ) ) ) ) { return $this->maybe_hide_unwrapped_locale_field( $field, $is_locale_hidden ); }

		// Maybe skip optional field by class
		/**
		 * Filters the CSS classes that keep an optional field visible.
		 *
		 * @since 1.5.0
		 *
		 * @param array $skip Whether to skip the default behavior.
		 */
		$skip_field_container_classes = apply_filters( 'fc_hide_optional_fields_skip_by_class', array( 'fc-skip-hide-optional-field' ) );
		foreach ( $skip_field_container_classes as $skip_class ) {
			foreach ( $args[ 'class' ] as $field_class ) {
				// Skip if field class contains the skip class
				if ( -1 < strpos( $field_class, $skip_class ) ) { return $this->maybe_hide_unwrapped_locale_field( $field, $is_locale_hidden ); }
			}
		}

		// Maybe skip optional field by id
		if ( in_array( $key, $this->get_hide_optional_fields_skip_list() ) ) { return $this->maybe_hide_unwrapped_locale_field( $field, $is_locale_hidden ); }

		// Maybe skip optional field by other criteria
		/**
		 * Filters whether a single optional field is excluded from being collapsed.
		 *
		 * @since 4.0.6
		 *
		 * @param bool   $skip  Whether to skip the default behavior. Default false.
		 * @param string $key   Field key.
		 * @param array  $args  Field arguments.
		 * @param mixed  $value Field value. Empty values can be collapsed. Default null.
		 */
		if ( apply_filters( 'fc_hide_optional_fields_skip_field', false, $key, $args, $value ) ) { return $this->maybe_hide_unwrapped_locale_field( $field, $is_locale_hidden ); }

		// Set attribute `data-autofocus` to focus on the optional field when expanding the section
		$field = str_replace( 'name="'. esc_attr( $key ) .'"', 'name="'. esc_attr( $key ) .'" data-autofocus', $field );

		// Move container classes to expansible block.
		// Keep the "Add ..." section in the markup when the locale hides the field so a later country
		// change can show the link again, and hide the whole section until then.
		$container_class_esc = esc_attr( implode( ' ', $args['class'] ) );
		$section_class = 'form-row ' . $container_class_esc;
		if ( $is_locale_hidden ) {
			$section_class .= ' ' . self::ADDRESS_LOCALE_HIDDEN_CLASS;
		}
		$expansible_section_args = array(
			'section_attributes' => array(
				'class' => $section_class,
			),
		);

		// Remove the container class from the field element
		$field = str_replace( 'form-row '. $container_class_esc, 'form-row ', $field );

		// Start buffer
		ob_start();

		// Prepare field label for the toggle
		$form_field_label = $args[ 'label' ];

		// Maybe set field label as lowercase
		if ( ! array_key_exists( 'optional_expand_link_lowercase', $args ) || false !== $args[ 'optional_expand_link_lowercase' ] ) {
			$form_field_label = $this->maybe_lowercase_add_link_label( $form_field_label );
		}

		// Get toggle label
		/* translators: %s: Form field label */
		$toggle_label = array_key_exists( 'optional_expand_link_label', $args ) ? sanitize_text_field( $args[ 'optional_expand_link_label' ] ) : sprintf( __( 'Add %s', 'fluid-checkout' ), $form_field_label );

		// Filter to allow developer to change the optional field expansible toggle label
		/**
		 * Filters the toggle label of an expansible section.
		 *
		 * The dynamic portion of the hook name, `$key`, refers to the checkout field key.
		 *
		 * @since 1.2.0
		 *
		 * @param string $toggle_label Toggle label.
		 */
		$toggle_label = apply_filters( "fc_expansible_section_toggle_label_{$key}", $toggle_label );

		// Maybe add "optional" to toggle label
		/**
		 * Filters whether every expansible section toggle label includes the "optional" text.
		 *
		 * @since 2.0.2
		 *
		 * @param bool $label Label text. Default true.
		 */
		if ( true === apply_filters( 'fc_expansible_section_toggle_label_add_optional_text', true ) && true === 
			/**
			 * Filters whether to append the "optional" text to one expansible section toggle label.
			 *
			 * The dynamic portion of the hook name, `$key`, refers to the checkout field key for that
			 * optional field. This runs in addition to
			 * `fc_expansible_section_toggle_label_add_optional_text`, which applies to every optional
			 * field.
			 *
			 * @since 2.0.2
			 *
			 * @param bool $value Value to filter. Default true.
			 */
			apply_filters( "fc_expansible_section_toggle_label_{$key}_add_optional_text", true ) ) {
			$toggle_label .= ' (' . __( 'optional', 'woocommerce' ) . ')';
		}

		FluidCheckout_Steps::instance()->output_expansible_form_section_start_tag( $key, $toggle_label, $expansible_section_args );
		echo $field; // WPCS: XSS ok.
		FluidCheckout_Steps::instance()->output_expansible_form_section_end_tag();

		// Get value and clear buffer
		$field = ob_get_clean();

		return $field;
	}

}

FluidCheckout_CheckoutHideOptionalFields::instance();

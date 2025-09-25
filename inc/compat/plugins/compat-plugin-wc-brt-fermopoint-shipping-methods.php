<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: BRT Fermopoint (by BRT)
 */
class FluidCheckout_WC_BRT_FermopointShippingMethods extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'wc_brt_fermopoint_shipping_methods_custom';

	/**
	 * Session field name for the selected pickup location.
	 */
	public const SESSION_FIELD_NAME = 'wc_brt_fermopoint-pudo_id';

	/**
	 * Session field name for the data associated with the selected pickup location.
	 */
	public const SESSION_FIELD_NAME_DATA = 'wc_brt_fermopoint-selected_pudo';



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
		// Bail if Fermopoint classes are not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Move fermopoint details section
		remove_action( 'woocommerce_review_order_after_shipping', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_maps_or_list' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'add_maps_or_list' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Output hidden fields
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Add substep text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}

	/**
	 * Add or remove checkout page hooks.
	 */
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if required class is not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// Shipping method description
		remove_action( 'woocommerce_after_shipping_rate', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_shipping_description' ), 10 );
		add_action( 'fc_shipping_method_option_description', array( $this, 'add_shipping_method_description_without_refresh_button' ), 10, 2 );

		// Refresh button
		add_filter( 'fc_shipping_method_option_label_markup', array( $this, 'maybe_add_refresh_button_to_shipping_method_label' ), 10, 2 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Add validation script
		wp_register_script( 'fc-checkout-validation-fermopoint', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-brt-fermopoint-shipping-methods/checkout-validation-fermopoint' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-fermopoint', 'window.addEventListener("load",function(){CheckoutValidationFermopoint.init(fcSettings.checkoutValidationFermopoint);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-fermopoint' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Maybe replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Scripts
		wp_register_script( 'wc_brt_fermopoint_shipping_methods_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-brt-fermopoint-shipping-methods/wc_brt_fermopoint_shipping_methods_js' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		// Add validation settings
		$settings[ 'checkoutValidationFermopoint' ] = array(
			'validationMessages'  => array(
				'fermopoint_not_selected' => __( 'Selecting a collection point is required when shipping with FermoPoint.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Check whether the shipping method ID is BRT FermoPoint.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_brt_fermopoint( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Check whether target shipping method is selected.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if the target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_brt_fermopoint( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Add maps or list output from the plugin, replacing `tr` elements with `div`.
	 * COPIED AND ADAPTED FROM: WC_BRT_FermoPoint_Shipping_Methods::instance()->core->add_maps_or_list().
	 */
	public function add_maps_or_list() {
		// Bail if required class or object is not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || null === WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// CHANGE: Replace `tr` and `td` elements with `div`.
		echo '<div id="wc_brt_fermopoint_shipping_methods_custom-tr_container"><div><div id="wc_brt_fermopoint_shipping_methods_custom-div_container">';

		if ( WC_BRT_FermoPoint_Shipping_Methods::instance()->core->use_google_map == 'yes' ){
			echo "<h3 class='pudo-label'>Per procedere, selezionare sulla mappa il punto di ritiro BRT-Fermpoint</h3>";
		} 
		else {
			echo "<h3 class='pudo-label'>Per procedere, selezionare dalla lista il punto di ritiro BRT-Fermpoint</h3>";
		}

		if ( WC_BRT_FermoPoint_Shipping_Methods::instance()->core->use_geolocation == 'yes' ) {
			echo "<i class='geoloc-pudo-label'>La geolocalizzazione può avvenire attraverso l'indirizzo scelto per la spedizione, oppure abilitando il rilevamento della posizione del browser</i>";
		}
		else {
			echo "<i class='geoloc-pudo-label'>La geolocalizzazione avviene attraverso l'indirizzo scelto per la spedizione</i>";
		}
		
		if ( WC_BRT_FermoPoint_Shipping_Methods::instance()->core->use_google_map == 'yes' ){
			WC_BRT_FermoPoint_Shipping_Methods::instance()->core->initGoogleMap();
		} 
		else {
			WC_BRT_FermoPoint_Shipping_Methods::instance()->core->initListaPudo();
		}

		echo "<i class='payment-pudo-label'>Per questa modalità di spedizione, l'eventuale pagamento in contrassegno è disabilitato</i>";

		// CHANGE: Remove the `#wc_brt_fermopoint-custom_checkout_fields` section to avoid duplication of hidden fields.

		echo "</div></div></div>";

		echo '<div id="wc_brt_fermopoint_shipping_methods_custom-tr_alert-no_pudable_products" class="wc_brt_tr_alert"><div><div id="wc_brt_fermopoint_shipping_methods_custom-div_alert-no_pudable_products" class="wc_brt_div_alert">';
			echo '<div class="alert" role="alert">Alcuni dei prodotti all\'interno del carrello superano il peso e la dimensione massima consentita per la spedizione presso un punto di ritiro BRT-Fermopoint</div>';
		echo "</div></div></div>";

		echo '<div id="wc_brt_fermopoint_shipping_methods_custom-tr_alert-no_pudable_fields" class="wc_brt_tr_alert"><div><div id="wc_brt_fermopoint_shipping_methods_custom-div_alert-no_pudable_fields" class="wc_brt_div_alert">';
			echo '<div class="alert" role="alert">Per utilizzare il metodo di spedizione BRT-Fermopoint, compilare il campo "Paese", "email" e "telefono".</div>';
		echo "</div></div></div>";

		echo '<div id="wc_brt_fermopoint_shipping_methods_custom-tr_alert-no_pudo_found" class="wc_brt_tr_alert"><div><div id="wc_brt_fermopoint_shipping_methods_custom-div_alert-no_pudo_found" class="wc_brt_div_alert">';
			echo '<div class="alert" role="alert">Nessun BRT-Fermopoint trovato. Verificare di aver compilato correttamente i campi "CAP", "Città" e "Paese".</div>';
		echo "</div></div></div>";

		echo '<div id="wc_brt_fermopoint_shipping_methods_custom-tr_alert-generic_error" class="wc_brt_tr_alert"><div><div id="wc_brt_fermopoint_shipping_methods_custom-div_alert-generic_error" class="wc_brt_div_alert">';
			echo '<div class="alert" role="alert">Si è verificato un errore. Si prega di riprovare più tardi.</div>';
		echo "</div></div></div>";
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field values were not posted
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) || ! array_key_exists( self::SESSION_FIELD_NAME_DATA, $posted_data ) ) { return $posted_data; }

		// Bail if field values are empty
		if ( empty( $posted_data[ self::SESSION_FIELD_NAME ] ) || empty( $posted_data[ self::SESSION_FIELD_NAME_DATA ] ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( self::SESSION_FIELD_NAME, $posted_data[ self::SESSION_FIELD_NAME ] );
		WC()->session->set( self::SESSION_FIELD_NAME_DATA, $posted_data[ self::SESSION_FIELD_NAME_DATA ] );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Get the selected terminal data.
	 */
	public function get_selected_terminal_data() {
		// Get session field value
		$terminal_data = WC()->session->get( self::SESSION_FIELD_NAME_DATA );

		// Bail if terminal data is empty
		if ( empty( $terminal_data ) ) { return; }

		// Decode terminal data
		$terminal_data = json_decode( $terminal_data, true );

		// Get pickup point country
		$terminal_country = '';
		if ( isset( $terminal_data[ 'country' ] ) && WC_BRT_FermoPoint_Shipping_Methods::instance()->core && method_exists( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'translateIso3ToIso2CountryCode' ) ) {
			// Maybe transform country code
			$terminal_country = WC_BRT_FermoPoint_Shipping_Methods::instance()->core->translateIso3ToIso2CountryCode( $terminal_data[ 'country' ] );
		}

		// Get selected shipping country
		$shipping_country = WC()->customer->get_shipping_country();

		// Bail if country from the selected pickup point doesn't match the shipping country
		if ( ! empty( $terminal_country ) && $terminal_country !== $shipping_country ) { return; }

		// Get terminal address
		$address = '';
		if ( isset( $terminal_data[ 'street' ] ) && isset( $terminal_data[ 'streetNumber' ] ) ) {
			$address = $terminal_data[ 'street' ] . ' ' . $terminal_data[ 'streetNumber' ];
		}

		// Assign terminal object property values to the corresponding array keys
		$selected_terminal_data = array(
			'company' => isset( $terminal_data[ 'pointName' ] ) ? esc_html( $terminal_data[ 'pointName' ] ) : '',
			'address_1' => esc_html( $address ),
			'postcode' => isset( $terminal_data[ 'zipCode' ] ) ? esc_html( $terminal_data[ 'zipCode' ] ) : '',
			'city' => isset( $terminal_data[ 'town' ] ) ? esc_html( $terminal_data[ 'town' ] ) : '',
			'state' => isset( $terminal_data[ 'state' ] ) ? esc_html( $terminal_data[ 'state' ] ) : '',
			'country' => esc_html( $terminal_country )
		);

		return $selected_terminal_data;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Get fields values from session
		$pudo_data = WC()->session->get( self::SESSION_FIELD_NAME_DATA );
		$pudo_id = WC()->session->get( self::SESSION_FIELD_NAME );

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Maybe reset the PUDO ID value
		if ( empty( $terminal_data ) ) {
			$pudo_id = '';
		}

		// Output custom hidden fields
		echo '<div id="wc_brt_fermopoint-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="wc_brt_fermopoint-selected_pudo" name="wc_brt_fermopoint-selected_pudo" value="'. esc_attr( $pudo_data ) .'">';
		echo '<input type="hidden" id="wc_brt_fermopoint-pudo_id" name="wc_brt_fermopoint-pudo_id" value="'. esc_attr( $pudo_id ) .'" class="validate-fermopoint">';
		echo '</div>';
		echo '</div>';
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return $review_text_lines; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Bail if terminal data is empty
		if ( empty( $terminal_data ) ) { return $review_text_lines; }

		// Format data
		$formatted_address = WC()->countries->get_formatted_address( $terminal_data );

		// Add formatted address to the review text lines
		$review_text_lines[] = $formatted_address;

		return $review_text_lines;
	}



	/**
	 * Set the shipping substep as incomplete when shipping method is Fermopoint but a location has not yet been selected.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return $is_substep_complete; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Maybe set substep as incomplete if terminal is not selected
		if ( empty( $terminal_data ) || empty( $terminal_data[ 'company' ] ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Re-add shipping method description without the refresh button.
	 * 
	 * @param  string            $description  Shipping method description.
	 * @param  WC_Shipping_Rate  $method       Shipping method rate data.
	 */
	function add_shipping_method_description_without_refresh_button( $description, $method ) {
		// Bail if not a target shipping method
		if ( ! $this->is_shipping_method_brt_fermopoint( $method->id ) ) { return $description; }

		// Bail if required class or method is not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core || ! method_exists( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_shipping_description' ) ) { return $description; }

		// Get default description from the plugin
		ob_start();
		WC_BRT_FermoPoint_Shipping_Methods::instance()->core->add_shipping_description( $method, 0 ); // Use 0 as index as it's not used by the function
		$brt_description = ob_get_clean();

		// Remove `img` tag (refresh button)
		$brt_description = preg_replace( '/<img[^>]+>/', '', $brt_description );

		// Remove extra `div` and `em` tags for consistency with other shipping methods
		$brt_description = preg_replace( '/<\/?(div|em)[^>]*>/', '', $brt_description );

		// Maybe add plugin's shipping method description
		if ( ! empty( $brt_description ) ) {
			// Maybe add line break to existing description
			if ( ! empty( $description ) ) {
				$shipping_method_description .= ' <br>'; // Intentionally add a space before `<br>`
			}

			// Add description
			$description .= $brt_description;
		}

		// Output the description
		return $description;
	}

	/**
	 * Maybe add refresh button to the shipping method label.
	 * 
	 * @param  string  $label   The shipping method label.
	 * @param  object  $method  The shipping method object.
	 */
	public function maybe_add_refresh_button_to_shipping_method_label( $label, $method ) {
		// Bail if not a shipping method from this plugin
		if ( ! $this->is_shipping_method_brt_fermopoint( $method->id ) ) { return $label; }

		// Bail if function is not available
		if ( ! function_exists( 'wc_brt_fermopoint_shipping_methods' ) ) { return $label; }

		// Get required class instance
		$class_instance = wc_brt_fermopoint_shipping_methods();

		// Bail if class instance or its method is not available
		if ( ! is_object( $class_instance ) || ! method_exists( $class_instance, 'plugin_url' ) ) { return $label; }

		// Get button HTML (`img` tag is used by the plugin)
		// COPIED FROM: WC_BRT_FermoPoint_Shipping_Methods_Core::add_shipping_description()
		$button_html = "<img src='" . $class_instance->plugin_url() . "/includes/images/ic-update.png' class='updatePudo' title='Aggiorna BRT-Fermpoint' />";

		// Insert button before closing `span` tag
		$label = str_replace( '</span>', $button_html . '</span>', $label );

		return $label;
	}

}

FluidCheckout_WC_BRT_FermopointShippingMethods::instance();

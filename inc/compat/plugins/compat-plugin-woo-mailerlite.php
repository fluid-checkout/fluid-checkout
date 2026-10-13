<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MailerLite - WooCommerce integration.
 */
class FluidCheckout_WooMailerLite extends FluidCheckout {

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

		// Replace plugin checkout script with FC fork
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_enqueue_assets' ), 20 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkbox position and state
		$this->checkbox_hooks();

		// Substep review text
		add_filter( 'fc_substep_contact_text_lines', array( $this, 'maybe_add_substep_text_lines_contact' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'maybe_add_substep_text_lines_shipping' ), 10 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'maybe_add_substep_text_lines_billing' ), 10 );
	}

	/**
	 * Add or remove hooks for the checkbox position.
	 */
	public function checkbox_hooks() {
		// Get plugin settings
		$checkout          = $this->get_option_from_plugin( 'checkout', 'no' );
		$checkout_position = $this->get_option_from_plugin( 'checkout_position', 'checkout_billing' );

		// Bail if not enabled for checkout or position not set
		if ( 'yes' !== $checkout || ! $checkout_position ) { return; }

		// Set default position when using Fluid Checkout
		$checkout_new_position = 'fc_checkout_contact_after_fields';
		$checkout_new_priority = 20;

		// Define new positions based on settings
		$checkbox_hook_priority = array(
			'checkout_billing'                => array( 'fc_before_checkout_billing_only_form', 20 ),
			'checkout_billing_email'          => array( 'fc_checkout_contact_after_fields', 20 ),
			'checkout_shipping'               => array( 'woocommerce_checkout_shipping', 20 ),
			'checkout_after_customer_details' => array( 'fc_checkout_contact_after_fields', 20 ),
			'review_order_before_submit'      => array( 'woocommerce_review_order_before_submit', 20 ),
		);

		// Set new position if defined in settings
		if ( isset( $checkbox_hook_priority[ $checkout_position ] ) ) {
			$checkout_new_position = $checkbox_hook_priority[ $checkout_position ][0];
			$checkout_new_priority = $checkbox_hook_priority[ $checkout_position ][1];
		}

		// Reset checkbox field value
		add_filter( 'fc_parsed_posted_data_reset_field_keys', array( $this, 'add_checkbox_reset_posted_data_field_key' ), 10, 2 );

		// Move the checkbox to the new hook position
		// Older plugin versions
		remove_action( 'woocommerce_' . $checkout_position, 'woo_ml_checkout_label', 20 );
		// Current plugin versions (3.x)
		if ( class_exists( 'WooMailerLitePluginController' ) ) {
			remove_action( 'woocommerce_' . $checkout_position, array( WooMailerLitePluginController::instance(), 'addMlSubscribeCheckbox' ), PHP_INT_MAX );
		}
		add_action( $checkout_new_position, array( $this, 'output_woo_ml_checkout_label' ), $checkout_new_priority );

		// Maybe remove field from billing address
		// Older plugin versions
		remove_filter( 'woocommerce_checkout_fields', 'woo_ml_billing_checkout_fields', PHP_INT_MAX );
		// Current plugin versions (3.x)
		if ( class_exists( 'WooMailerLitePluginController' ) ) {
			remove_filter( 'woocommerce_checkout_fields', array( WooMailerLitePluginController::instance(), 'addBillingCheckoutFields' ), PHP_INT_MAX );
		}
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'woo-ml-public-script', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-mailerlite/woo-mailerlite-public' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/**
	 * Replace the plugin checkout script with the FC fork on checkout.
	 */
	public function replace_enqueue_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		wp_dequeue_script( 'woo-mailerlite-public' );
		wp_deregister_script( 'woo-mailerlite-public' );

		wp_enqueue_script( 'woo-ml-public-script' );
		wp_localize_script(
			'woo-ml-public-script',
			'wooMailerLitePublicData',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'woo_mailerlite_cart_nonce' ),
				'language'         => get_locale(),
				'checkboxSettings' => $this->get_checkbox_settings_for_script(),
			)
		);
	}

	/**
	 * Checkbox settings passed to the public checkout script.
	 *
	 * @return array
	 */
	public function get_checkbox_settings_for_script() {
		$enabled = 'yes' === $this->get_option_from_plugin( 'checkout', 'no' );
		$group   = $this->get_option_from_plugin( 'group' );

		if ( empty( $group ) ) {
			$enabled = false;
		}

		return array(
			'enabled'   => $enabled,
			'label'     => $this->get_option_from_plugin( 'checkout_label', '' ),
			'preselect' => 'yes' === $this->get_option_from_plugin( 'checkout_preselect', 'no' ),
			'hidden'    => 'yes' === $this->get_option_from_plugin( 'checkout_hide', 'no' ),
		);
	}



	/**
	 * Get an option from the plugin in different ways for different versions.
	 * 
	 * @param   string  $option         The option name.
	 * @param   mixed   $default_value  The default value to return if the option is not set.
	 * @return  mixed                   The option value.
	 */
	public function get_option_from_plugin( $option, $default_value = null ) {
		// Get plugin settings
		$value = null;

		// Current plugin versions (3.x) use WooMailerLiteOptions
		if ( class_exists( 'WooMailerLiteOptions' ) ) {
			$value = $this->get_option_from_woo_mailerlite_options( $option, $default_value );
		}
		// Legacy class-based settings API
		else if ( class_exists( 'MailerLite\Includes\Classes\Settings\MailerLiteSettings' ) ) {
			$value = MailerLite\Includes\Classes\Settings\MailerLiteSettings::getInstance()->getMlOption( $option, $default_value );
		}
		// Older helper function
		else if ( function_exists( 'woo_ml_get_option' ) ) {
			$value = woo_ml_get_option( $option, $default_value );
		}

		// Maybe fall back to the legacy WooCommerce settings option
		if ( null === $value || false === $value ) {
			$legacy_settings = get_option( 'woocommerce_mailerlite_settings', array() );
			if ( is_array( $legacy_settings ) && array_key_exists( $option, $legacy_settings ) ) {
				$value = $legacy_settings[ $option ];
			}
		}

		// Maybe use the default when still empty
		if ( null === $value || false === $value ) {
			$value = $default_value;
		}

		return $value;
	}

	/**
	 * Map Fluid Checkout option keys to WooMailerLiteOptions values.
	 *
	 * @param   string  $option         The option name used by Fluid Checkout.
	 * @param   mixed   $default_value  The default value to return if the option is not set.
	 */
	public function get_option_from_woo_mailerlite_options( $option, $default_value = null ) {
		// Map FC keys to current plugin option paths
		$option_map = array(
			'checkout'           => 'settings.subscribeOnCheckout',
			'checkout_position'  => 'settings.selectedCheckoutPosition',
			'checkout_preselect' => 'settings.checkoutPreselect',
			'checkout_hide'      => 'settings.checkoutHidden',
			'checkout_label'     => 'settings.checkoutLabel',
			'group'              => 'group',
		);

		// Bail if option is not mapped
		if ( ! isset( $option_map[ $option ] ) ) { return $default_value; }

		// Use a sentinel so falsey plugin values are not treated as missing
		$missing = new stdClass();
		$raw_value = WooMailerLiteOptions::get( $option_map[ $option ], $missing );

		// Bail if option is not set in the current plugin options
		if ( $missing === $raw_value ) { return $default_value; }

		// Normalize boolean-like options to yes/no strings expected by FC
		$boolean_options = array( 'checkout', 'checkout_preselect', 'checkout_hide' );
		if ( in_array( $option, $boolean_options, true ) ) {
			return $raw_value ? 'yes' : 'no';
		}

		// Normalize group option to a non-empty scalar when possible
		if ( 'group' === $option && is_array( $raw_value ) ) {
			// Maybe return the group id when available
			if ( ! empty( $raw_value[ 'id' ] ) ) {
				return $raw_value[ 'id' ];
			}

			return $default_value;
		}

		return $raw_value;
	}



	/**
	 * Add the contact substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function maybe_add_substep_text_lines_for_substep( $review_text_lines = array(), $substep = '', $contact_substep_positions = array() ) {
		// Get plugin settings
		$checkout          = $this->get_option_from_plugin( 'checkout', 'no' );
		$checkout_position = $this->get_option_from_plugin( 'checkout_position', 'checkout_billing' );

		// Bail if not enabled for checkout or position not set
		if ( 'yes' !== $checkout || ! $checkout_position ) { return $review_text_lines; }

		// Bail if not on the contact substep
		if ( ! in_array( $checkout_position, $contact_substep_positions ) ) { return $review_text_lines; }

		// Get checked state and posted data
		$checked = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'woo_ml_subscribe' );

		// Bail if checkbox is not checked
		if ( ! $checked ) { return $review_text_lines; }

		// Add the text lines
		$checkbox_label = $this->get_option_from_plugin( 'checkout_label', __( 'Yes, I want to receive your newsletter.', 'woo-mailerlite' ) );
		$review_text_lines[] = $checkbox_label;

		// Return the text lines
		return $review_text_lines;
	}

	/**
	 * Add the contact substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function maybe_add_substep_text_lines_contact( $review_text_lines = array() ) {
		return $this->maybe_add_substep_text_lines_for_substep( $review_text_lines, 'contact', array( 'checkout_billing_email', 'checkout_after_customer_details' ) );
	}

	/**
	 * Add the shipping substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function maybe_add_substep_text_lines_shipping( $review_text_lines = array() ) {
		return $this->maybe_add_substep_text_lines_for_substep( $review_text_lines, 'shipping', array( 'checkout_shipping' ) );
	}

	/**
	 * Add the shipping substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function maybe_add_substep_text_lines_billing( $review_text_lines = array() ) {
		return $this->maybe_add_substep_text_lines_for_substep( $review_text_lines, 'billing', array( 'checkout_billing' ) );
	}



	/**
	 * Shows the final purchase total at the bottom of the checkout page
	 * COPIED FROM woo-mailerlite/includes/hooks.php
	 */
	public function output_woo_ml_checkout_label() {
		// CHANGE: Remove check for the plugin activation state

		$checkout = $this->get_option_from_plugin('checkout', 'no');

		if ('yes' != $checkout) {
			return;
		}

		$group = $this->get_option_from_plugin('group');

		if (empty($group)) {
			return;
		}

		$label     = $this->get_option_from_plugin('checkout_label');
		$preselect = $this->get_option_from_plugin('checkout_preselect', 'no');
		$hidden    = $this->get_option_from_plugin('checkout_hide', 'no');

		if ('yes' === $hidden) {
			?>
			<input name="woo_ml_subscribe" type="hidden" id="woo_ml_subscribe" value="1" checked="checked"/>
			<?php
		} else {

			// CHANGE: Define whether the checkbox is checked based on the posted data
			$checked = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'woo_ml_subscribe' );
			$checkbox_state = empty( $checked ) ? ( 'yes' === $preselect ? 'checked' : '' ) : 'checked';

			woocommerce_form_field('woo_ml_subscribe', array(
				'type'  => 'checkbox',
				'label' => __($label, 'woo-mailerlite'),
				// CHANGE: Use the checked state based on posted data
				'checked' => $checkbox_state
			), 'checked' === $checkbox_state );
		}
	}



	/**
	 * Add newsletter checkbox field key to be cleared when not present in posted data.
	 * 
	 * @param   array  $field_keys   Field keys.
	 * @param   array  $posted_data  Posted data.
	 */
	public function add_checkbox_reset_posted_data_field_key( $field_keys, $posted_data ) {
		// Add customer location confirmation field key
		$field_keys[] = 'woo_ml_subscribe';

		return $field_keys;
	}

}

FluidCheckout_WooMailerLite::instance();

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
		remove_action( 'woocommerce_' . $checkout_position, 'woo_ml_checkout_label', 20 );
		add_action( $checkout_new_position, array( $this, 'output_woo_ml_checkout_label' ), $checkout_new_priority );

		// Maybe remove field from billing address
		remove_filter( 'woocommerce_checkout_fields', 'woo_ml_billing_checkout_fields', PHP_INT_MAX );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'woo-ml-public-script', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-mailerlite/public' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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

		// Get settings using different methods for older versions
		// Newer versions
		if ( class_exists( 'MailerLite\Includes\Classes\Settings\MailerLiteSettings' ) ) {
			$value = MailerLite\Includes\Classes\Settings\MailerLiteSettings::getInstance()->getMlOption( $option, $default_value );
		}
		// Older versions
		else if ( function_exists( 'woo_ml_get_option' ) ) {
			$value = woo_ml_get_option( $option, $default_value );
		}

		return $value;
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

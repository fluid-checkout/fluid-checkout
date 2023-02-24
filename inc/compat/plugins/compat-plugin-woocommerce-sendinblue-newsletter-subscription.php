<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Sendinblue - WooCommerce Email Marketing (by Sendinblue).
 */
class FluidCheckout_WooCommerceSendinblueNewsletterSubscription extends FluidCheckout {

	public $v3_cart_events_manager;
	public $v3_api_manager;
	public $v3_settings;



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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Get plugin version
		$plugin_version = $this->get_plugin_version( 'woocommerce-sendinblue-newsletter-subscription/woocommerce-sendinblue.php' );

		// Versions prior to 3.0.0
		if ( version_compare( $plugin_version, '3.0.0', '<' ) ) {
			$this->hooks_v2();
		}
		// Versions 3.0.0+
		else {
			$this->hooks_v3();
		}
	}

	/**
	 * Add or remove late hooks for v2 or lower.
	 */
	public function hooks_v2() {
		// Bail if SendInBlue class is not available
		if ( ! class_exists( 'WC_Sendinblue_Integration' ) || ! array_key_exists( 'WC_Sendinblue_Integration', $GLOBALS ) ) { return; }

		// Get instance and variables for the Sendinblue WooCommerce integration class
		$sendinblue_woocommerce = $GLOBALS['WC_Sendinblue_Integration'];
		$ws_opt_field = isset( $sendinblue_woocommerce->customizations['ws_opt_field'] ) ? $sendinblue_woocommerce->customizations['ws_opt_field'] : 'no';
		$display_location = isset( $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] ) ? $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] : '';

		// Move nonce field
		add_action( 'fc_checkout_after_steps', array( $this, 'output_nonce_field' ), 10 );

		// Replace the function that adds the checkbox as a checkout field
		// because it outputs the nonce in the wrong place if `WC()->checkout()->get_checkout_fields()` is called early
		remove_filter( 'woocommerce_checkout_fields', array( $sendinblue_woocommerce, 'maybe_add_checkout_fields' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_checkout_fields' ), 10 );

		// Order fields position
		if ( 'yes' == $ws_opt_field && 'order' == $display_location && 'yes' !== get_option( 'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step', 'yes' ) ) {
			add_filter( 'fc_substep_order_notes_text_lines', array( $this, 'add_substep_text_lines_order_notes' ), 10 );
		}

		// Maybe move checkbox to the contact step
		$this->move_checkbox_to_contact_step_hooks_v2();
	}

	/**
	 * Add or remove late hooks for v2 or lower.
	 */
	public function hooks_v3() {
		// Bail if classes do not exist
		if ( ! class_exists( 'SendinblueWoocommerce\Managers\ApiManager' ) || ! class_exists( 'SendinblueWoocommerce\Managers\CartEventsManagers' ) ) { return; }

		// Get the manager objects
		$this->v3_api_manager = new SendinblueWoocommerce\Managers\ApiManager();
		$this->v3_cart_events_manager = $this->get_object_by_class_name_from_hooks( 'SendinblueWoocommerce\Managers\CartEventsManagers' );
		
		// Bail if class or object is not available
		if ( null === $this->v3_api_manager || null === $this->v3_cart_events_manager ) { return; }

		// Get settings
		$this->v3_settings = $this->v3_api_manager->get_settings();

		// Move nonce field
		add_action( 'fc_checkout_after_steps', array( $this, 'output_nonce_field' ), 10 );

		// Replace the function that adds the checkbox as a checkout field
		// because it outputs the nonce in the wrong place if `WC()->checkout()->get_checkout_fields()` is called early
		remove_filter( 'woocommerce_checkout_fields', array( $this->v3_cart_events_manager, 'add_optin_billing' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_optin_field_v3' ), 10 );

		// Order fields position
		$is_order_display_location = ! empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED ] ) && 2 == $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::DISPLAY_OPT_IN_LOCATION ]; // 2 = Order notes
		if ( $is_order_display_location && 'yes' !== get_option( 'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step', 'yes' ) ) {
			add_filter( 'fc_substep_order_notes_text_lines', array( $this, 'add_substep_text_lines_order_notes' ), 10 );
		}

		// Maybe move checkbox to the contact step
		$this->move_checkbox_to_contact_step_hooks_v3();
	}

	/**
	 * Add or remove hooks for displaying the signup checkbox on the contact step.
	 */
	public function move_checkbox_to_contact_step_hooks_v2() {
		// Bail if should not move field to contact step
		if ( 'yes' !== get_option( 'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step', 'yes' ) ) { return; }

		// Get instance and variables for the Sendinblue WooCommerce integration class
		$sendinblue_woocommerce = $GLOBALS['WC_Sendinblue_Integration'];
		$ws_opt_field = isset( $sendinblue_woocommerce->customizations['ws_opt_field'] ) ? $sendinblue_woocommerce->customizations['ws_opt_field'] : 'no';
		$display_location = isset( $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] ) ? $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] : '';

		// Terms and conditions position
		if ( 'yes' == $ws_opt_field && 'terms_condition' == $display_location ) {
			remove_action( 'woocommerce_checkout_after_terms_and_conditions', array( $sendinblue_woocommerce, 'maybe_add_checkout_fields_terms' ), 10 );
			add_action( 'fc_checkout_after_contact_fields', array( $sendinblue_woocommerce, 'maybe_add_checkout_fields_terms' ), 10 );
		}

		// Billing or order fields position
		if ( 'yes' == $ws_opt_field && ( empty( $display_location ) || 'billing' == $display_location || 'order' == $display_location ) ) {
			remove_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_checkout_fields' ), 10 );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_checkout_fields_to_billing' ), 10 );
			add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'move_signup_field_to_contact_substep' ), 10 );
		}
	}

	/**
	 * Add or remove hooks for displaying the signup checkbox on the contact step.
	 */
	public function move_checkbox_to_contact_step_hooks_v3() {
		// Bail if should not move field to contact step
		if ( 'yes' !== get_option( 'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step', 'yes' ) ) { return; }
		
		// Bail if optin field is not set to be displayed on the checkout page
		if ( empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED ] ) ) { return; }

		// Remove fields from display location determined by Sendinblue
		remove_action( 'woocommerce_checkout_after_terms_and_conditions', array( $this->v3_cart_events_manager, 'add_optin_terms' ), 10 );
		remove_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_optin_field_v3' ), 10 );

		// Always add field to billing, then move it to the contact step
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_optin_field_to_billing_v3' ), 10 );
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'move_signup_field_to_contact_substep' ), 10 );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Define positions for new settings
		$index = count( $settings ) - 1;

		// Define setting to insert
		$insert_settings = array(
			array(
				'title'           => __( 'Sendinblue - WooCommerce Email Marketing', 'fluid-checkout' ),
				'desc'            => __( 'Move the sign up checkbox to the contact step', 'fluid-checkout' ),
				'desc_tip'        => __( 'When enabled, the checkbox will be moved to the contact step independently of the display location defined in the Sendinblue plugin settings.', 'fluid-checkout' ),
				'id'              => 'fc_compat_plugin_woocommerce_sendinblue_newsletter_subscription_move_checkbox_contact_step',
				'type'            => 'checkbox',
				'default'         => 'yes',
				'autoload'        => false,
			),
		);

		// Get token position
		$position_index = count( $settings ) - 1;

		// Insert at token position
		$new_settings = array_slice( $settings, 0, $position_index );
		$new_settings = array_merge( $new_settings, $insert_settings );
		$new_settings = array_merge( $new_settings, array_slice( $settings, $position_index, count( $settings ) ) );

		return $new_settings;
	}



	/**
	 * Output the Sendinblue opt in nonce field.
	 */
	public function output_nonce_field() {
		?>
		<input type="hidden" class="ws_opt_in_nonce" name="ws_opt_in_nonce" value="<?php echo wp_create_nonce( 'order_checkout_nonce' ); ?>">
		<?php
	}



	/**
	 * Add the sign up field to billing or order sections.
	 *
	 * @param   array  $checkout_fields  The checkout fields args.
	 */
	public function maybe_add_checkout_fields( $checkout_fields ) {
		// CHANGE: Get SendInBlue class object
		$sendinblue_woocommerce = $GLOBALS['WC_Sendinblue_Integration'];

		$display_location = isset( $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] ) ? $sendinblue_woocommerce->customizations['ws_opt_checkbox_location'] : '';

		if ( empty( $display_location ) ) {
			$display_location = 'billing';
		}
		$ws_opt_field = isset( $sendinblue_woocommerce->customizations['ws_opt_field'] ) ? $sendinblue_woocommerce->customizations['ws_opt_field'] : 'no';
		if ( 'yes' == $ws_opt_field ) {
			$checkout_fields[ $display_location ]['ws_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $sendinblue_woocommerce->customizations['ws_opt_field_label'] ),
				'default' => 'checked' == $sendinblue_woocommerce->customizations['ws_opt_default_status'] ? 1 : 0,
			);
		}

		// CHANGE: Removed nonce field output for `GET` requests, moved to a more appropriate place

		return $checkout_fields;
	}

	/**
	 * Add the sign up field always in the billing section, to then be moved to the contact step via other hooks.
	 *
	 * @param   array  $checkout_fields  The checkout fields args.
	 */
	public function maybe_add_checkout_fields_to_billing( $checkout_fields ) {
		// CHANGE: Get SendInBlue class object
		$sendinblue_woocommerce = $GLOBALS['WC_Sendinblue_Integration'];

		// CHANGE: Removed unnecessary checks for display location

		$ws_opt_field = isset( $sendinblue_woocommerce->customizations['ws_opt_field'] ) ? $sendinblue_woocommerce->customizations['ws_opt_field'] : 'no';
		if ( 'yes' == $ws_opt_field ) {
			// CHANGE: Always add field to billing section, then it will be moved to contact step
			$checkout_fields[ 'billing' ]['ws_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $sendinblue_woocommerce->customizations['ws_opt_field_label'] ),
				'default' => 'checked' == $sendinblue_woocommerce->customizations['ws_opt_default_status'] ? 1 : 0,
			);
		}

		// CHANGE: Removed nonce field output for `GET` requests, moved to a more appropriate place

		return $checkout_fields;
	}

	/**
	 * Maybe add optin field to billing or order notes section.
	 */
	public function maybe_add_optin_field_v3( $checkout_fields ) {
		// Billing section
		if ( ! empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED ] ) && 1 == $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::DISPLAY_OPT_IN_LOCATION ] ) { // 1 = Billing
			$checkout_fields['billing']['ws_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $this->checkout_label() ),
				'default' => 'checked' == empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED ] ) ? 0 : 1,
			);
		}

		// Order notes section
		if ( ! empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED ] ) && 2 == $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::DISPLAY_OPT_IN_LOCATION ] ) { // 2 = Order notes
			$checkout_fields['order']['ws_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $this->checkout_label() ),
				'default' => 'checked' == empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED ] ) ? 0 : 1,
			);
		}

		// CHANGE: Removed nonce field output for `GET` requests, moved to a more appropriate place

		return $checkout_fields;
	}

	/**
	 * Maybe add optin field to billing or order notes section.
	 */
	public function add_optin_field_to_billing_v3( $checkout_fields ) {
		// Billing or order notes section
		$checkout_fields['billing']['ws_opt_in'] = array(
			'type'    => 'checkbox',
			'label'   => esc_attr( $this->checkout_label() ),
			'default' => 'checked' == empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED ] ) ? 0 : 1,
		);

		return $checkout_fields;
	}



	/**
	 * Move sign up field to the contact substep.
	 */
	public function move_signup_field_to_contact_substep( $contact_field_ids ) {
		// Fields after existing fields
		$contact_field_ids = array_merge( $contact_field_ids, array( 'ws_opt_in' ) );

		return $contact_field_ids;
	}



	/**
	 * Add the signup value to order notes substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_order_notes( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }
		
		// Get checkout field
		$field_key = 'ws_opt_in';
		$checkout_order_fields = WC()->checkout->get_checkout_fields( 'order' );
		$field_value = WC()->checkout()->get_value( $field_key );

		// The order notes value
		if ( ! empty( $field_value ) && array_key_exists( $field_key, $checkout_order_fields ) ) {
			$review_text_lines[] = FluidCheckout_Steps::instance()->get_field_display_value( $field_value, $field_key, $checkout_order_fields[ $field_key ] );
		}

		return $review_text_lines;
	}



	/**
	 * Get the checkbox label for the checkout page.
	 */
	public function checkout_label() {
		$label = 'Add me to the newsletter';
		if ( ! empty( $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::DISPLAY_OPT_IN_LABEL ] ) ) {
			$label = $this->v3_settings[ SendinblueWoocommerce\Clients\SendinblueClient::DISPLAY_OPT_IN_LABEL ];
		}

		return $label;
	}

}

FluidCheckout_WooCommerceSendinblueNewsletterSubscription::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: The Bluehost Plugin (by Bluehost).
 */
class FluidCheckout_TheBluehostPlugin extends FluidCheckout {

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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Checkout fields
		add_action( 'after_setup_theme', array( $this, 'undo_ecommerce_hooks' ), 100 );
	}

	/**
	 * Undo hooks from the Bluehost plugin.
	 */
	public function undo_ecommerce_hooks() {
		// Bail if class is not available
		$class_name = 'NewfoldLabs\WP\Module\ECommerce\ECommerce';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Prevent swapping address fields
		remove_filter( 'woocommerce_checkout_fields', array( $class_object, 'swap_billing_shipping_fields' ), 10 );

		// Remove custom fields
		// - Shipping phone fields should be managed by Fluid Checkout
		// - Shipping email field may be added back depending on the integration settings
		remove_filter( 'woocommerce_shipping_fields', array( $class_object, 'add_phone_number_email_to_shipping_form' ), 10 );
		remove_action( 'woocommerce_checkout_create_order', array( $class_object, 'save_custom_shipping_fields' ), 10 );
		remove_action( 'woocommerce_admin_order_data_after_shipping_address', array( $class_object, 'display_custom_shipping_fields_in_admin' ), 10 );

		// Maybe add shipping email field
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_integration_bluehost_plugin_custom_fields' ) ) {
			// Shipping email
			add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_email_field' ), 10 );
			add_action( 'woocommerce_checkout_create_order', array( $this, 'save_custom_shipping_email_field' ), 10 );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_custom_shipping_email_field_in_admin' ), 10 );

			// Shipping address
			add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'remove_shipping_email_substep_review_text_skip_fields' ), 10 );
		}
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'The Bluehost Plugin', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_bluehost_plugin_options',
			),

			array(
				'title'           => __( 'Custom fields', 'fluid-checkout' ),
				'desc'            => __( 'Enable custom shipping email field', 'fluid-checkout' ),
				'desc_tip'        => __( 'When enabled, the shipping email field from the Bluehost plugin will be added to the checkout form.', 'fluid-checkout' ),
				'id'              => 'fc_integration_bluehost_plugin_custom_fields',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_bluehost_plugin_custom_fields' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_bluehost_plugin_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Add shipping email field.
	 *
	 * @param   array  $fields   Array with all fields for the current section.
	 */
	public function add_shipping_email_field( $fields ) {
		// Bail if the shipping email field has already been added
		if ( array_key_exists( 'shipping_email', $fields ) ) { return $fields; }

		// Add the shipping email field
		$fields[ 'shipping_email' ] = array(
			'label'         => __( 'Email Address', 'wp_module_ecommerce' ), // Intentionally use the text domain from the Bluehost plugin
			'required'      => true,
			'class'         => array( 'form-row-wide' ),
			'clear'         => true,
		);

		return $fields;
	}

	/**
	 * Save the shipping email field to order meta.
	 * Copied and adapted from the bluehost plugin.
	 * 
	 * @param   WC_Order  $order  Order object.
	 */
	public function save_custom_shipping_email_field( $order ) {
		// Get shipping email
		$shipping_email = isset( $_POST[ 'shipping_email' ] ) ? sanitize_email( $_POST[ 'shipping_email' ] ) : '';

		// Maybe save shipping email to order
		if ( ! empty( $shipping_email ) ) {
			$order->update_meta_data( '_shipping_email', $shipping_email );
		}
	}

	/**
	 * Display phone number and email fields in order admin
	 */
	public function display_custom_shipping_email_field_in_admin( $order ) {
		// Get the shipping email from the order
		$shipping_email = $order->get_meta('_shipping_email');

		// Maybe display the shipping email
		if ( ! empty( $shipping_email ) ) {
			// Intentionally use the text domain below from the Bluehost plugin
			echo '<p><strong>' . __( 'Email Address', 'wp_module_ecommerce') . ':</strong> ' . esc_html( $shipping_email ) . '</p>';
		}
	}

	/**
	 * Remove shipping email field from fields to skip adding to the substep review text.
	 *
	 * @param   array  $field_keys_skip_list  Array with all fields to skip.
	 */
	public function remove_shipping_email_substep_review_text_skip_fields( $field_keys_skip_list ) {
		// Remove the shipping email field
		$field_keys_skip_list = array_diff( $field_keys_skip_list, array( 'shipping_email' ) );

		return $field_keys_skip_list;
	}

}

FluidCheckout_TheBluehostPlugin::instance();

<?php
/**
 * Fluid Checkout Local Pickup Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_LocalPickup', false ) ) {
	FluidCheckout_Settings_LocalPickup::hooks();
	return;
}

/**
 * FluidCheckout_Settings_LocalPickup.
 */
class FluidCheckout_Settings_LocalPickup {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
		$sections = array_merge( $sections, array(
			'local_pickup' => __( 'Local pickup', 'fluid-checkout' ),
		) );

		return $sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_settings( $settings, $current_section ) {
		if ( 'local_pickup' !== $current_section ) { return $settings; }

		/**
		 * Filters the options for the local pickup shipping zone fields setting.
		 *
		 * @since 5.0.0
		 */
		$local_pickup_shipping_zone_field_options = apply_filters( 'fc_local_pickup_shipping_zone_fields_options', array() );

		/**
		 * Filters the Local pickup settings.
		 *
		 * @since 5.0.0
		 */
		return apply_filters(
			'fc_local_pickup_settings',
			array(
				array(
					'title' => __( 'Local pickup', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_local_pickup_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'local-pickup' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-local-pickup/' ),
				),

				array(
					'title'                 => __( 'Local pickup', 'fluid-checkout' ),
					'desc'                  => __( 'Removes shipping address section when a local pickup shipping method is selected.', 'fluid-checkout' ),
					'desc_tip'              => __( 'Replaces the shipping address with the pickup point location when a local pickup shipping method is selected.', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_local_pickup',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_local_pickup' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => '',
					'id'                    => 'fc_local_pickup_edit_pickup_locations',
					'type'                  => 'fc_button',
					'button_text'           => __( 'Edit pickup locations', 'fluid-checkout' ),
					'url'                   => admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pickup_location' ),
					'button_style'          => 'secondary',
					'open_in_new_tab'       => true,
					'show_external_icon'    => true,
					'is_option'             => false,
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Default delivery type', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Choose which delivery type will be selected by default.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_default_delivery_type',
					'type'                  => 'fc_select',
					'options'               => array(
						'ship'   => _x( 'Shipping to address', 'Delivery type', 'fluid-checkout' ),
						'pickup' => _x( 'Local pickup', 'Delivery type', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_default_delivery_type' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => '',
					'desc'                  => __( 'Use local pickup label from WooCommerce settings', 'fluid-checkout' ),
					'desc_tip'              => __( 'When enabled, the pickup option label on checkout matches the title from WooCommerce pickup location settings. When disabled, the label uses the translatable "Local pickup" text from Fluid Checkout.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_use_woocommerce_pickup_label',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_use_woocommerce_pickup_label' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Shipping zone fields', 'fluid-checkout' ),
					'desc'                  => __( 'Choose which fields will be displayed for the local pickup shipping zone filter fields. Leave empty to show all available options.', 'fluid-checkout' ),
					'desc_tip'              => __( 'These fields will be displayed as a filter for the local pickup shipping methods. Some fields might still appear at checkout when not selected here, if compatible plugins require them to work properly.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_shipping_zone_fields',
					'type'                  => 'fc_multiselect',
					'class'                 => 'fc-enhanced-select',
					'options'               => $local_pickup_shipping_zone_field_options,
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_shipping_zone_fields' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Order shipping address', 'fluid-checkout' ),
					'desc'                  => '',
					'desc_tip'              => __( 'Choose which address to save as the shipping address for local pickup orders.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_save_shipping_address',
					'type'                  => 'fc_select',
					'options'               => array(
						'same_as_pickup_location'    => __( 'Save the selected pickup location', 'fluid-checkout' ),
						'same_as_billing'            => __( 'Save same as the billing address', 'fluid-checkout' ),
						'no'                         => __( 'Do not save any shipping address', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_save_shipping_address' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_local_pickup_options',
				),

				array(
					'title' => __( 'Shipping methods', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_local_pickup_shipping_methods_options',
				),

				array(
					'title'                 => __( 'Shipping methods', 'fluid-checkout' ),
					'desc'                  => __( 'This setting is shared between the Checkout and Local pickup sections. On the Local pickup section, it also decides whether the Ship/Pickup selector shows inside the shipping method section (before the address) or as a separate section (after).', 'fluid-checkout' ),
					'desc_tip'              => __( 'Choose in which position to display the shipping methods section.', 'fluid-checkout' ),
					'id'                    => 'fc_shipping_methods_substep_position',
					'html_id'               => 'fc_shipping_methods_substep_position_local_pickup',
					'type'                  => 'select',
					'options'               => array(
						'before_shipping_address' => __( 'Before shipping address', 'fluid-checkout' ),
						'after_shipping_address'  => __( 'After shipping address', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_shipping_methods_substep_position' ),
					'custom_attributes'     => array(
						'data-fc-settings-sync-group' => 'fc_shipping_methods_substep_position',
					),
					'autoload'              => false,
					'display_only'          => true,
					'is_option'             => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_local_pickup_shipping_methods_options',
				),
			)
		);
	}



	/**
	 * Sanitize the local pickup shipping zone fields option to ensure it's saved as an array.
	 *
	 * @since 5.0.0
	 *
	 * @param  mixed  $value      The option value.
	 * @param  mixed  $option     The option arguments.
	 * @param  mixed  $raw_value  The raw value of the option.
	 */
	public static function sanitize_local_pickup_shipping_zone_fields( $value, $option, $raw_value ) {
		// Bail without changing the saved value when a disabled field was not submitted, eg. when disabled in the Lite version.
		// The settings save skips a `null` value, so returning an empty array would wipe the saved value.
		// Check for the disabled field, as a cleared multiselect also submits no value and should still save an empty array.
		if ( null === $raw_value && is_array( $option ) && ! empty( $option[ 'disabled' ] ) ) { return null; }

		// Ensure the value is an array, as a cleared multiselect submits no value.
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		// Sanitize the field keys.
		$value = array_map( 'sanitize_text_field', $value );

		return $value;
	}

}

FluidCheckout_Settings_LocalPickup::hooks();

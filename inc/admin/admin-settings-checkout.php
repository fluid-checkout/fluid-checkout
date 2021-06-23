<?php
/**
 * WooCommerce Checkout Settings
 *
 * @package fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Checkout', false ) ) {
	return new WC_Settings_FluidCheckout_Checkout();
}

/**
 * WC_Settings_FluidCheckout_Checkout.
 */
class WC_Settings_FluidCheckout_Checkout extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'fc_checkout';
		$this->label = __( 'Fluid Checkout', 'fluid-checkout' );

		parent::__construct();

		$this->hooks();
	}


	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Enqueue
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_styles' ), 10 );

		// Field types
		add_action( 'woocommerce_admin_field_fc_layout_selector', array( $this, 'output_field_type_fc_layout_seletor' ), 10 );
		add_action( 'woocommerce_admin_field_fc_image_uploader', array( $this, 'output_field_type_fc_image_uploader' ), 10 );
	}



	public function admin_enqueue_scripts_styles( $hook ) {
		// SCRIPT: MEDIA UPLOADER
		if ( in_array( $hook, array( 'woocommerce_page_wc-settings' ) ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'fc-admin-image-uploader', FluidCheckout::$directory_url . '/js/admin/admin-image-uploader'. FluidCheckout::$asset_version . '.js' , array( 'jquery', 'media-upload', 'media-views' ), null, true );
		}
	}



	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''             => __( 'Checkout Options', 'fluid-checkout' ),
			'address'      => __( 'Address Fields', 'fluid-checkout' ),
			'advanced'     => __( 'Advanced', 'fluid-checkout' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}



	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}



	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}



	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		if ( 'advanced' === $current_section ) {
			$settings = apply_filters(
				'fc_checkout_'.$current_section.'_settings',
				array(
					array(
						'title' => __( 'Layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_advanced_layout_options',
					),

					array(
						'title'         => __( 'Order summary', 'fluid-checkout' ),
						'desc'          => __( 'Make the order summary stay visible while scrolling', 'fluid-checkout' ),
						'id'            => 'fc_enable_checkout_sticky_order_summary',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Progress bar', 'fluid-checkout' ),
						'desc'          => __( 'Make the checkout progress bar stay visible while scrolling', 'fluid-checkout' ),
						'desc_tip'      => __( 'Applies only to multi-step layouts.', 'fluid-checkout' ),
						'id'            => 'fc_enable_checkout_sticky_progress_bar',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Header and Footer', 'fluid-checkout' ),
						'desc'          => __( 'We recommend using the Fluid Checkout header and footer to avoid distractions at the checkout page. <a href="https://baymard.com/blog/cart-abandonment" target="_blank">Read the research about cart abandonment</a>.', 'fluid-checkout' ),
						'desc_tip'      => __( 'Controls whether to use the Fluid Checkout page header and footer or keep the currently active theme\'s.', 'fluid-checkout' ),
						'id'            => 'fc_hide_site_header_footer_at_checkout',
						'type'          => 'radio',
						'options'       => array(
							'yes'       => __( 'Use Fluid Checkout header and footer', 'fluid-checkout' ),
							'no'        => __( 'Use theme\'s page header and footer for the checkout page', 'fluid-checkout' ),
						),
						'default'       => 'yes',
						'autoload'      => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_advanced_layout_options',
					),

					array(
						'title' => __( 'Troubleshooting', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_advanced_debug_options',
					),

					array(
						'title'         => __( 'Debug options', 'fluid-checkout' ),
						'desc'          => __( 'Load unminified assets', 'fluid-checkout' ),
						'desc_tip'      => __( 'Loading unminified assets affect the the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
						'id'            => 'fc_load_unminified_assets',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_advanced_debug_options',
					),
				)
			);
		}
		else if ( 'address' === $current_section ) {
			$settings = apply_filters(
				'fc_checkout_'.$current_section.'_settings',
				array(
					array(
						'title' => __( 'Address Fields', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_advanced_debug_options',
					),

					array(
						'title'         => __( 'Billing Address', 'fluid-checkout' ),
						'desc'          => __( 'Billing address same as the shipping address checked by default', 'fluid-checkout' ),
						'desc_tip'      => __( 'It is recommended to leave this option checked. The billing address at checkout will start with the option "Billing same as shipping" checked by default. This will reduce significantly the number of open input fields at the checkout, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#3-default-billing--shipping-and-hide-the-fields-entirely" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'            => 'fc_default_to_billing_same_as_shipping',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'autoload'      => false,
					),

					array(
						'title'         => __( 'Shipping phone', 'fluid-checkout' ),
						'desc'          => __( 'Add a phone field to the shipping address form', 'fluid-checkout' ),
						'id'            => 'fc_shipping_phone_field_visibility',
						'options'       => array(
							'no'        => _x( 'Hidden', 'Shipping phone field visibility', 'fluid-checkout' ),
							'optional'  => _x( 'Optional', 'Shipping phone field visibility', 'fluid-checkout' ),
							'required'  => _x( 'Required', 'Shipping phone field visibility', 'fluid-checkout' ),
						),
						'default'       => 'no',
						'type'          => 'select',
						'autoload'      => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_advanced_debug_options',
					),
				)
			);
		}
		else {
			$settings = apply_filters(
				'fc_checkout_general_settings',
				array(
					array(
						'title' => __( 'Layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_layout_options',
					),

					array(
						'title'             => __( 'Checkout Layout', 'fluid-checkout' ),
						'id'                => 'fc_checkout_layout',
						'type'              => 'fc_layout_selector',
						'options'           => FluidCheckout_Steps::instance()->get_allowed_checkout_layouts(),
						'default'           => 'multi-step',
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-layout',
						'class'             => 'fc-checkout-layout__option',
					),

					array(
						'title'             => __( 'Logo image', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose an image to be displayed on the checkout page header. Only applies when using the plugin\'s checkout header.', 'fluid-checkout' ),
						'id'                => 'fc_checkout_logo_image',
						'type'              => 'fc_image_uploader',
						'autoload'          => false,
						'wrapper_class'     => 'fc-checkout-logo-image',
					),

					array(
						'title'             => __( 'Header background color', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose a background color for the checkout page header. Only applies when using the plugin\'s checkout header.', 'fluid-checkout' ),
						'desc'              => __( 'HTML color value. ie: #f3f3f3', 'fluid-checkout' ),
						'id'                => 'fc_checkout_header_background_color',
						'type'              => 'text',
						'autoload'          => false,
						'class'             => 'colorpick',
					),

					array(
						'title'             => __( 'Optional fields', 'fluid-checkout' ),
						'desc'              => __( 'Hide optional fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'It is recommended to keep this options checked to reduce the number of open input fields, <a href="https://baymard.com/blog/checkout-flow-average-form-fields#1-address-line-2--company-name-can-safely-be-collapsed-behind-a-link" target="_blank">read the research</a>.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_hide_optional_fields',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Do not hide "Address line 2" fields behind a link button', 'fluid-checkout' ),
						'desc_tip'          => __( 'Recommended only whe most customers actually need the "Address line 2" field, or when getting the right shipping address is crucial (ie. if delivering food and other perishable products).', 'fluid-checkout' ),
						'id'                => 'fc_hide_optional_fields_skip_address_2',
						'default'           => 'no',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc'              => __( 'Display an additional "Place order" and terms checkbox below the order summary in the sidebar.', 'fluid-checkout' ),
						'desc_tip'          => __( 'Recommended if most of the orders have only a few different products in the cart, and product variations do not take too much space on the order summary.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_place_order_sidebar',
						'default'           => 'no',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_layout_options',
					),

					array(
						'title' => __( 'Features', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_checkout_features_options',
					),

					array(
						'title'             => __( 'Coupon Codes', 'fluid-checkout' ),
						'desc'              => __( 'Show coupon codes as a substep of the payment step', 'fluid-checkout' ),
						'desc_tip'          => __( 'Only applicable if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_coupon_codes',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the coupon codes section title', 'fluid-checkout' ),
						'id'                => 'fc_display_coupon_code_section_title',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Gift options', 'fluid-checkout' ),
						'desc'              => __( 'Display gift message and other gift options at the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'Allow customers to add a gift message and other gift related options to the order.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_gift_options',
						'default'           => 'no',
						'type'              => 'checkbox',
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the gift message fields always expanded', 'fluid-checkout' ),
						'id'                => 'fc_default_gift_options_expanded',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => '',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),
					array(
						'desc'              => __( 'Display the gift message as part of the order details table', 'fluid-checkout' ),
						'desc_tip'          => __( 'This option affects the order confirmation page (thank you page), order details at account pages, emails and packing slips.', 'fluid-checkout' ),
						'id'                => 'fc_display_gift_message_in_order_details',
						'type'              => 'checkbox',
						'default'           => 'no',
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
						'autoload'          => false,
					),

					array(
						'title'             => __( 'Checkout Widget Areas', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols on the checkout page.', 'fluid-checkout' ),
						'id'                => 'fc_enable_checkout_widget_areas',
						'default'           => 'yes',
						'type'              => 'checkbox',
						'autoload'          => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_checkout_features_options',
					),
				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}



	/**
	 * Output the layout selector setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_layout_seletor( $value ) {
		// Custom attribute handling.
		$custom_attributes_esc = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes_esc[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		$option_value = $value['value'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
				</th>
				<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
					<fieldset>
						<?php echo $description; // WPCS: XSS ok. ?>
						<ul>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							?>
							<li>
								<label><input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									value="<?php echo esc_attr( $key ); ?>"
									type="radio"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									<?php echo implode( ' ', $custom_attributes_esc ); // WPCS: XSS ok. ?>
									<?php checked( $key, $option_value ); ?>
									/> <?php echo esc_html( $val ); ?></label>
							</li>
							<?php
						}
						?>
						</ul>
						<style>
							<?php
							foreach ( $value['options'] as $key => $val ) {
								$option_image_url = apply_filters( 'fc_checkout_layout_option_image_url', FluidCheckout::$directory_url . 'images/admin/fc-layout-'. esc_attr( $key ) .'.png', $key, $val );
								?>
								.forminp-fc_layout_selector .fc-checkout-layout__option[value="<?php echo esc_attr( $key ); ?>"]:after {
									background-image: url( <?php echo esc_url( $option_image_url ) ?> );
								}
								<?php
							}
							?>
						</style>
					</fieldset>
				</td>
			</tr>
		<?php
	}



	/**
	 * Output the image selector setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_image_uploader( $value ) {
		// Custom attribute handling.
		$custom_attributes = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		$option_value = $value['value'];
		$image_url = $option_value ? wp_get_attachment_image_url( $option_value, 'full' ) : '';
		?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
				</th>
				<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
					<fieldset>
						<?php echo $description; // WPCS: XSS ok. ?>

						<div class="image-upload__wrapper <?php echo esc_attr( $value['class'] ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
							<div class="image-upload-preview">
								<div id="<?php echo esc_attr( $value['id'] ); ?>_preview" class="placeholder">
								<?php
									if ( empty( $image_url ) ) {
										echo _x( 'No image selected.', 'Image uploader.', 'fluid-checkout' );
									}
									else {
										echo '<img src="' . esc_attr( $image_url ) . '">';
									}
								?>
								</div>
								<div class="actions">
									<button
										id="<?php echo esc_attr( $value['id'] ); ?>_select_button"
										type="button"
										class="button image-upload-select-button"
										data-dialog-title="<?php echo esc_attr ( __( 'Select an image', 'fluid-checkout' ) ); ?>"
										data-dialog-button-text="<?php echo esc_attr ( __( 'Select an image', 'fluid-checkout' ) ); ?>"
										data-library-type="image"
										data-preview-id="<?php echo esc_attr( $value['id'] ); ?>_preview"
										data-control-id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( __( 'Select an image', 'fluid-checkout' ) ); ?></button>
									<button
										id="<?php echo esc_attr( $value['id'] ); ?>clear_button"
										type="button"
										class="button image-upload-clear-button"
										data-preview-id="<?php echo esc_attr( $value['id'] ); ?>_preview"
										data-control-id="<?php echo esc_attr( $value['id'] ); ?>"
										data-message="<?php echo esc_attr( __( 'No image selected.', 'Image uploader.', 'fluid-checkout' ) ); ?>"><?php echo esc_html( __( 'Remove image', 'Clear image selection on admin pages.', 'fluid-checkout' ) ); ?></button>
								</div>
							</div>
						</div>

					</fieldset>
				</td>
			</tr>
		<?php
	}
}

return new WC_Settings_FluidCheckout_Checkout();

<?php
/**
 * Fluid Checkout Settings Page.
 *
 * @package fluid-checkout
 * @version 1.3.1
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
		add_action( 'woocommerce_admin_field_fc_paragraph', array( $this, 'output_field_type_fc_paragraph' ), 10 );
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
			'' => __( 'Checkout Options', 'fluid-checkout' ),
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
		return apply_filters( 'woocommerce_get_settings_' . $this->id, array(), $current_section );
	}



	/**
	 * Output the paragraph setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_paragraph( $value ) {
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		?>
		<tr valign="top">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<p>
					<?php echo $description; // WPCS: XSS ok. ?>
				</p>
			</td>
		</tr>
		<?php
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

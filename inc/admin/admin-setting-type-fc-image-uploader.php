<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_ImageUploader extends FluidCheckout {

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
		// Field types
		add_action( 'woocommerce_admin_field_fc_image_uploader', array( $this, 'output_field_type_fc_image_uploader' ), 10 );

		// Scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_styles' ), 10 );
	}



	/**
	 * Enqueue the setting type scripts and styles.
	 *
	 * @param   string  $hook  Current admin page hook.
	 */
	public function register_scripts_styles( $hook ) {
		// Bail if not on WooCommerce Settings
		if ( 'woocommerce_page_wc-settings' !== $hook ) { return; }
		
		wp_register_script( 'fc-admin-image-uploader', FluidCheckout::$directory_url . '/js/admin/admin-image-uploader'. FluidCheckout::$asset_version . '.js' , array( 'jquery', 'media-upload', 'media-views' ), null, true );
	}



	/**
	 * Output the image selector setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_image_uploader( $value ) {
		// Enqueue assets
		wp_enqueue_media();
		wp_enqueue_script( 'fc-admin-image-uploader' );
		
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

FluidCheckout_Admin_SettingType_ImageUploader::instance();

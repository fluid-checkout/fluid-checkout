<?php
defined( 'ABSPATH' ) || exit;

/**
 * Image uploader field type for the Fluid Checkout settings page.
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
		add_action( 'fc_admin_settings_render_field_fc_image_uploader', array( $this, 'output_field' ), 10 );

		// Scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_styles' ), 10 );
	}



	/**
	 * Enqueue the setting type scripts and styles.
	 *
	 * @param   string  $hook  Current admin page hook.
	 */
	public function register_scripts_styles( $hook ) {
		// Bail if not on the settings page
		if ( ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page() ) { return; }

		wp_register_script( 'fc-admin-image-uploader', FluidCheckout_Enqueue::instance()->get_script_url( '/js/admin/admin-image-uploader' ), array( 'jquery', 'media-upload', 'media-views' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Enqueue assets
		wp_enqueue_media();
		wp_enqueue_script( 'fc-admin-image-uploader' );

		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$option_value = $value[ 'value' ];
		$image_url = $option_value ? wp_get_attachment_image_url( $option_value, 'full' ) : '';

		$renderer->output_field_start( $value, array( 'label_for' => false ) );
		?>
		<fieldset>
			<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<div class="image-upload__wrapper <?php echo esc_attr( $value[ 'class' ] ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $value[ 'id' ] ); ?>" id="<?php echo esc_attr( $value[ 'id' ] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
				<div class="image-upload-preview">
					<div id="<?php echo esc_attr( $value[ 'id' ] ); ?>_preview" class="placeholder">
					<?php
						if ( empty( $image_url ) ) {
							echo esc_html( _x( 'No image selected.', 'Image uploader.', 'fluid-checkout' ) );
						}
						else {
							echo '<img src="' . esc_url( $image_url ) . '">';
						}
					?>
					</div>
					<div class="actions">
						<button
							id="<?php echo esc_attr( $value[ 'id' ] ); ?>_select_button"
							type="button"
							class="button image-upload-select-button"
							data-dialog-title="<?php echo esc_attr( __( 'Select an image', 'fluid-checkout' ) ); ?>"
							data-dialog-button-text="<?php echo esc_attr( __( 'Select an image', 'fluid-checkout' ) ); ?>"
							data-library-type="image"
							data-preview-id="<?php echo esc_attr( $value[ 'id' ] ); ?>_preview"
							data-control-id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
							<?php disabled( $renderer->is_field_disabled( $value ) ); ?>><?php echo esc_html( __( 'Select an image', 'fluid-checkout' ) ); ?></button>
						<button
							id="<?php echo esc_attr( $value[ 'id' ] ); ?>clear_button"
							type="button"
							class="button image-upload-clear-button"
							data-preview-id="<?php echo esc_attr( $value[ 'id' ] ); ?>_preview"
							data-control-id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
							data-message="<?php echo esc_attr( _x( 'No image selected.', 'Image uploader.', 'fluid-checkout' ) ); ?>"
							<?php disabled( $renderer->is_field_disabled( $value ) ); ?>><?php echo esc_html( _x( 'Remove image', 'Clear image selection on admin pages.', 'fluid-checkout' ) ); ?></button>
					</div>
				</div>
			</div>

		</fieldset>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_ImageUploader::instance();

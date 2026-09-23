<?php
defined( 'ABSPATH' ) || exit;

/**
 * Textarea field type for the Fluid Checkout settings page, with support for disabled fields.
 */
class FluidCheckout_Admin_SettingType_Textarea extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_textarea', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );

		$renderer->output_field_start( $value );
		echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<textarea
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
			<?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $renderer->is_field_disabled( $value ) ); ?>
			><?php echo esc_textarea( $value[ 'value' ] ); ?></textarea>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_Textarea::instance();

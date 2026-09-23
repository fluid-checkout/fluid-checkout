<?php
defined( 'ABSPATH' ) || exit;

/**
 * Select field type for the Fluid Checkout settings page, with support for disabled fields and options.
 */
class FluidCheckout_Admin_SettingType_Select extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_select', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$option_value = $value[ 'value' ];
		$options = isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ? $value[ 'options' ] : array();

		$renderer->output_field_start( $value );
		?>
		<select
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			<?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $renderer->is_field_disabled( $value ) ); ?>
			>
			<?php
			foreach ( $options as $key => $args ) {
				// Handle options type
				if ( ! is_array( $args ) ) {
					$args = array( 'label' => $args );
				}
				?>
				<option value="<?php echo esc_attr( $key ); ?>"
					<?php $renderer->output_option_selected( $option_value, $key ); ?>
					<?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'disabled' : ''; ?>
				><?php echo esc_html( $args[ 'label' ] ); ?></option>
				<?php
			}
			?>
		</select> <?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_Select::instance();

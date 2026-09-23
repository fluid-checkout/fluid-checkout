<?php
defined( 'ABSPATH' ) || exit;

/**
 * Input field types for the Fluid Checkout settings page, with support for disabled fields.
 */
class FluidCheckout_Admin_SettingType_Input extends FluidCheckout {

	/**
	 * Input types supported by this field type, prefixed with `fc_` in the settings arrays.
	 */
	const INPUT_TYPES = array( 'text', 'password', 'datetime', 'datetime-local', 'date', 'month', 'time', 'week', 'number', 'email', 'url', 'tel' );



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
		foreach ( self::INPUT_TYPES as $input_type ) {
			add_action( 'fc_admin_settings_render_field_fc_' . $input_type, array( $this, 'output_field' ), 10 );
		}
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();

		// Get input type from the field type
		$input_type = preg_replace( '/^fc_/', '', $value[ 'type' ] );

		$field_description = $renderer->get_field_description( $value );
		$suffix_label = isset( $value[ 'suffix_label' ] ) ? $value[ 'suffix_label' ] : '';
		$has_suffix_label = '' !== $suffix_label;

		$renderer->output_field_start( $value );

		if ( $has_suffix_label ) {
			echo '<span class="fc-settings-input-group fc-settings-input-group--split">';
		}
		?>
		<input
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			type="<?php echo esc_attr( $input_type ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			value="<?php echo esc_attr( $value[ 'value' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
			<?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $renderer->is_field_disabled( $value ) ); ?>
		/>
		<?php if ( $has_suffix_label ) : ?>
			<input
				type="text"
				class="fc-settings-input-group__label"
				value="<?php echo esc_attr( $suffix_label ); ?>"
				disabled
				tabindex="-1"
				aria-hidden="true"
			/>
			</span>
		<?php else : ?>
			<?php echo esc_html( $value[ 'suffix' ] ); ?>
		<?php endif; ?>
		<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_Input::instance();

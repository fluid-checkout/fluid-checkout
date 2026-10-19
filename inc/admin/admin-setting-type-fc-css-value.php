<?php
defined( 'ABSPATH' ) || exit;

/**
 * CSS value field type for the Fluid Checkout settings page: a text input for any CSS value, with an optional color swatch and a button that clears the value.
 */
class FluidCheckout_Admin_SettingType_CssValue extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_css_value', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values. Set `swatch` to `true` to show a color swatch.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$has_swatch = ! empty( $value[ 'swatch' ] );
		$is_disabled = $renderer->is_field_disabled( $value );

		// Define the swatch color, white unless the value or default is a hex color, as color inputs only take hex colors
		$swatch_color = '#ffffff';

		// Maybe show the value color
		if ( preg_match( '/^#[0-9a-f]{6}$/i', (string) $value[ 'value' ] ) ) {
			$swatch_color = $value[ 'value' ];
		}
		// Otherwise, maybe show the color of the default shown as placeholder
		elseif ( preg_match( '/^#[0-9a-f]{6}$/i', (string) $value[ 'placeholder' ] ) ) {
			$swatch_color = $value[ 'placeholder' ];
		}

		// Get the accessible names of the swatch and the clear button
		/* translators: %s: Settings field title. */
		$swatch_label = sprintf( __( 'Choose a color for %s', 'fluid-checkout' ), $value[ 'title' ] );
		/* translators: %s: Settings field title. */
		$clear_label = sprintf( __( 'Clear %s', 'fluid-checkout' ), $value[ 'title' ] );

		$renderer->output_field_start( $value );
		?>
		<span class="fc-settings-input-group fc-settings-css-value" data-fc-settings-css-value>
			<?php if ( $has_swatch ) : ?>
				<input
					type="color"
					class="fc-settings-css-value__swatch"
					value="<?php echo esc_attr( strtolower( $swatch_color ) ); ?>"
					aria-label="<?php echo esc_attr( $swatch_label ); ?>"
					data-fc-settings-css-value-swatch
					<?php disabled( $is_disabled ); ?>
				/>
			<?php endif; ?>
			<input
				name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
				id="<?php echo esc_attr( $renderer->get_field_html_id( $value ) ); ?>"
				type="text"
				dir="ltr"
				style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
				value="<?php echo esc_attr( $value[ 'value' ] ); ?>"
				class="fc-settings-css-value__input <?php echo esc_attr( $value[ 'class' ] ); ?>"
				placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
				autocomplete="off"
				spellcheck="false"
				data-fc-settings-css-value-input
				<?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php disabled( $is_disabled ); ?>
			/>
			<button
				type="button"
				class="button fc-settings-css-value__clear"
				aria-label="<?php echo esc_attr( $clear_label ); ?>"
				data-fc-settings-css-value-clear
				<?php disabled( $is_disabled ); ?>
			><?php echo esc_html( __( 'Clear', 'fluid-checkout' ) ); ?></button>
		</span>
		<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_CssValue::instance();

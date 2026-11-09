<?php
defined( 'ABSPATH' ) || exit;

/**
 * Disabled Google API key admin field stub (visible when PRO is not providing the real field).
 */
class FluidCheckout_Admin_SettingType_GoogleApiKey extends FluidCheckout {

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
		// Field output (priority 5 so PRO can replace at 10)
		add_action( 'fc_admin_settings_render_field_fc_gaa_google_places_api_key', array( $this, 'output_api_key_field_card' ), 5 );
	}



	/**
	 * Output the disabled Google API key input, Test, and Guided setup controls.
	 *
	 * @param   array  $value             Admin settings args values.
	 * @param   bool   $show_description  Whether to output the field description below the controls.
	 */
	public function output_api_key_controls( $value, $show_description = false ) {
		$option_value = isset( $value[ 'value' ] ) ? $value[ 'value' ] : '';
		$field_id     = isset( $value[ 'id' ] ) ? $value[ 'id' ] : 'fc_gaa_google_places_api_key';
		$field_name   = $value[ 'field_name' ] ?? $field_id;
		?>
		<div class="fc-gaa-google-api-validation__api-key-field-row">
			<input
				name="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $field_id ); ?>"
				type="text"
				style="<?php echo esc_attr( $value[ 'css' ] ?? '' ); ?>"
				value="<?php echo esc_attr( $option_value ); ?>"
				class="<?php echo esc_attr( $value[ 'class' ] ?? '' ); ?>"
				placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ?? '' ); ?>"
				disabled
			/>

			<div class="fc-gaa-google-api-validation__api-key-field-actions">
				<button type="button" class="button button-primary" disabled><?php echo esc_html( __( 'Test API key', 'fluid-checkout' ) ); ?></button>
				<button type="button" class="button button-secondary" disabled><?php echo esc_html( __( 'Guided setup', 'fluid-checkout' ) ); ?></button>
			</div>
		</div>
		<?php

		// Maybe output description below the controls
		if ( $show_description && class_exists( 'FluidCheckout_Admin_Settings_Renderer' ) ) {
			$field_description = FluidCheckout_Admin_Settings_Renderer::instance()->get_field_description( $value );

			echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Output the disabled Google API key field on the Fluid Checkout settings page cards.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_api_key_field_card( $value ) {
		// Bail if the settings renderer is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Renderer' ) ) { return; }

		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$renderer->output_field_start( $value, array( 'label_for' => true, 'tooltip' => true ) );
		$this->output_api_key_controls( $value, true );
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_GoogleApiKey::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report enable checkbox with preview action.
 */
class FluidCheckout_Admin_SettingType_TelemetryEnable extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_telemetry_enable', array( $this, 'output_field' ), 10 );

		// Save
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_option_value' ), 10, 3 );
	}



	/**
	 * Sanitize the site report enable option value on save.
	 *
	 * @param mixed $value     Sanitized option value.
	 * @param array $option    Option definition.
	 * @param mixed $raw_value Raw option value.
	 */
	public function sanitize_option_value( $value, $option, $raw_value ) {
		if ( empty( $option['type'] ) || 'fc_telemetry_enable' !== $option['type'] ) {
			return $value;
		}

		return ( '1' === $raw_value || 'yes' === $raw_value ) ? 'yes' : 'no';
	}



	/**
	 * Output the setting field.
	 *
	 * @param array $value Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer      = FluidCheckout_Admin_Settings_Renderer::instance();
		$option_value  = $value[ 'value' ];
		$has_title     = '' !== $value[ 'title' ];
		$label_text    = ! empty( $value[ 'desc' ] ) ? wp_kses_post( $value[ 'desc' ] ) : '';
		$desc_tip_html = '';

		if ( ! empty( $value[ 'desc_tip' ] ) && true !== $value[ 'desc_tip' ] ) {
			$desc_tip_html = '<p class="description">' . wp_kses_post( $value[ 'desc_tip' ] ) . '</p>';
		}

		$renderer->output_field_start( $value, array( 'fieldset' => true, 'label_for' => false, 'tooltip' => false ) );
		?>
		<?php if ( $has_title ) : ?>
			<legend class="screen-reader-text"><span><?php echo esc_html( $value[ 'title' ] ); ?></span></legend>
		<?php endif; ?>
		<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>">
			<input
				name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
				id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
				type="checkbox"
				class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
				value="1"
				<?php checked( $option_value, 'yes' ); ?>
				<?php disabled( $renderer->is_field_disabled( $value ) ); ?>
			/>
			<?php echo $label_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</label>
		<?php echo $desc_tip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="fc-telemetry-enable-actions">
			<button
				type="button"
				class="button button-secondary fc-telemetry-preview-button"
				aria-haspopup="dialog"
			><?php esc_html_e( 'Preview report data', 'fluid-checkout' ); ?></button>
			<button
				type="button"
				class="button button-secondary fc-telemetry-send-now-button<?php echo 'yes' === $option_value ? '' : ' is-hidden'; ?>"
			><?php esc_html_e( 'Send now', 'fluid-checkout' ); ?></button>
		</p>
		<p class="fc-telemetry-enable-actions__feedback is-hidden" aria-live="polite"></p>
		<?php
		$renderer->output_field_end( $value, array( 'fieldset' => true ) );
	}

}

FluidCheckout_Admin_SettingType_TelemetryEnable::instance();

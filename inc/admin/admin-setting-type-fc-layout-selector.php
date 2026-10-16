<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sectioned radio buttons field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_LayoutSelector extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_layout_selector', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_disabled = $renderer->is_field_disabled( $value );
		$field_description = $renderer->get_field_description( $value );
		$custom_attributes_html = $renderer->get_custom_attributes_html( $value );
		$option_value = $this->get_selectable_option_value( $value );
		$group_label = ! empty( $value[ 'title' ] ) ? $value[ 'title' ] : __( 'Options', 'fluid-checkout' );

		$renderer->output_field_start( $value, array( 'label_for' => false ) );
		?>
		<div class="fc-settings-sectioned-buttons" role="radiogroup" aria-label="<?php echo esc_attr( $group_label ); ?>">
			<?php foreach ( $value[ 'options' ] as $key => $args ) : ?>
				<?php
				// Normalize option args
				if ( ! is_array( $args ) ) {
					$args = array( 'label' => $args );
				}

				$option_disabled = $field_disabled || ( array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] );
				$option_classes = 'fc-settings-sectioned-buttons__option';
				$option_classes .= $option_disabled ? ' is-disabled' : '';
				$option_classes .= (string) $key === (string) $option_value ? ' is-selected' : '';
				?>
				<label class="<?php echo esc_attr( $option_classes ); ?>">
					<input
						name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						<?php echo $custom_attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php checked( $key, $option_value ); ?>
						<?php disabled( $option_disabled ); ?>
						/>
					<span><?php echo esc_html( $args[ 'label' ] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$renderer->output_field_end( $value );
	}

	/**
	 * Get the option value that can be selected in the UI.
	 * Falls back to the field default when the saved value is a disabled PRO option.
	 *
	 * @param  array  $value  Admin settings args values.
	 */
	public function get_selectable_option_value( $value ) {
		$option_value = $value[ 'value' ];
		$options = isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ? $value[ 'options' ] : array();

		// Bail if the current value is not a known option
		if ( ! array_key_exists( $option_value, $options ) ) {
			return $value[ 'default' ];
		}

		$args = $options[ $option_value ];
		if ( ! is_array( $args ) ) {
			$args = array( 'label' => $args );
		}

		// Force Lite-compatible value when the saved option is disabled
		if ( array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ) {
			return $value[ 'default' ];
		}

		return $option_value;
	}

}

FluidCheckout_Admin_SettingType_LayoutSelector::instance();

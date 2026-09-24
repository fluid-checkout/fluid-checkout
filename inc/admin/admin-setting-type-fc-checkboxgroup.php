<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkbox group field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Checkboxgroup extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_checkboxgroup', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param array $value Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer         = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$description      = $field_description[ 'description' ];
		$tooltip_html     = $field_description[ 'tooltip_html' ];
		$option_value     = is_array( $value[ 'value' ] ) ? $value[ 'value' ] : array();
		$options          = isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ? $value[ 'options' ] : array();
		$disabled_options = ! empty( $value[ 'disabled_options' ] ) && is_array( $value[ 'disabled_options' ] ) ? $value[ 'disabled_options' ] : array();
		$required_options = ! empty( $value[ 'required_options' ] ) && is_array( $value[ 'required_options' ] ) ? $value[ 'required_options' ] : array();
		$has_title        = '' !== $value[ 'title' ];
		$use_toggle       = $renderer->uses_toggle_checkboxes();

		$renderer->output_field_start( $value, array( 'fieldset' => true, 'label_for' => false ) );

		// Titles of fields continuing a field group are displayed with the options, as the row label belongs to the first field
		$is_group_start = $renderer->is_current_field_row_start();
		?>
		<?php if ( $has_title ) : ?>
			<legend class="screen-reader-text"><span><?php echo esc_html( $value[ 'title' ] ); ?></span></legend>
		<?php endif; ?>

		<?php if ( ( $has_title && ! $is_group_start ) || $description || ( $tooltip_html && ! $is_group_start ) ) : ?>
			<p class="description">
				<?php if ( $has_title && ! $is_group_start ) : ?>
					<strong><?php echo esc_html( $value[ 'title' ] ); ?></strong>
					<?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<br>
				<?php endif; ?>
				<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
		<?php endif; ?>

		<?php foreach ( $options as $option_key => $option_data ) : ?>
			<?php
			$option_label       = is_array( $option_data ) ? ( $option_data[ 'label' ] ?? '' ) : $option_data;
			$option_description = is_array( $option_data ) ? ( $option_data[ 'description' ] ?? '' ) : '';
			$is_disabled        = $renderer->is_field_disabled( $value ) || in_array( $option_key, $disabled_options, true );
			$is_required        = in_array( $option_key, $required_options, true );
			$is_checked         = $is_required || in_array( (string) $option_key, $option_value, true );
			$input_id           = $value[ 'id' ] . '_' . sanitize_html_class( (string) $option_key );
			?>
			<div class="fc-checkboxgroup-option">
				<?php if ( $is_disabled && $is_checked ) : ?>
					<input
						type="hidden"
						name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>[]"
						value="<?php echo esc_attr( $option_key ); ?>"
					/>
				<?php endif; ?>
				<?php if ( $use_toggle ) : ?>
					<span class="fc-settings-switch<?php echo $is_disabled ? ' fc-settings-switch--disabled' : ''; ?>">
						<input
							id="<?php echo esc_attr( $input_id ); ?>"
							type="checkbox"
							name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>[]"
							class="fc-settings-toggle fc-settings-toggle--round"
							value="<?php echo esc_attr( $option_key ); ?>"
							<?php checked( $is_checked, true ); ?>
							<?php disabled( $is_disabled ); ?>
						/>
						<label for="<?php echo esc_attr( $input_id ); ?>"></label>
					</span>
					<label class="fc-settings-switch__text" for="<?php echo esc_attr( $input_id ); ?>">
						<strong><?php echo esc_html( $option_label ); ?></strong>
					</label>
				<?php else : ?>
					<label for="<?php echo esc_attr( $input_id ); ?>">
						<input
							id="<?php echo esc_attr( $input_id ); ?>"
							type="checkbox"
							name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>[]"
							value="<?php echo esc_attr( $option_key ); ?>"
							<?php checked( $is_checked, true ); ?>
							<?php disabled( $is_disabled ); ?>
						/>
						<strong><?php echo esc_html( $option_label ); ?></strong>
					</label>
				<?php endif; ?>
				<?php if ( ! empty( $option_description ) ) : ?>
					<span class="description fc-checkboxgroup-option__description"><?php echo esc_html( $option_description ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php
		$renderer->output_field_end( $value, array( 'fieldset' => true ) );
	}

}

FluidCheckout_Admin_SettingType_Checkboxgroup::instance();

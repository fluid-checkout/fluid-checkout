<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkbox group admin setting field.
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
		add_action( 'woocommerce_admin_field_fc_checkboxgroup', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param array $value Admin settings args values.
	 */
	public function output_field( $value ) {
		$visibility_class = array();

		if ( ! isset( $value['show_if_checked'] ) ) {
			$value['show_if_checked'] = false;
		}

		if ( 'yes' === $value['show_if_checked'] ) {
			$visibility_class[] = 'hidden_option';
		}

		$container_class = implode( ' ', $visibility_class );
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		$option_value       = is_array( $value['value'] ) ? $value['value'] : array();
		$disabled_options   = ! empty( $value['disabled_options'] ) && is_array( $value['disabled_options'] ) ? $value['disabled_options'] : array();
		$required_options   = ! empty( $value['required_options'] ) && is_array( $value['required_options'] ) ? $value['required_options'] : array();
		$checkboxgroup      = $value['checkboxgroup'] ?? '';
		$has_title          = isset( $value['title'] ) && '' !== $value['title'];
		?>
		<fieldset class="<?php echo esc_attr( $container_class ); ?>">
			<?php if ( $has_title ) : ?>
				<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
			<?php endif; ?>

			<?php if ( $has_title || $description || $tooltip_html ) : ?>
				<p class="description">
					<?php if ( $has_title ) : ?>
						<strong><?php echo esc_html( $value['title'] ); ?></strong>
						<?php echo $tooltip_html; // WPCS: XSS ok. ?>
						<br>
					<?php endif; ?>
					<?php echo $description; // WPCS: XSS ok. ?>
				</p>
			<?php endif; ?>

			<?php foreach ( $value['options'] as $option_key => $option_data ) : ?>
				<?php
				$option_label       = is_array( $option_data ) ? ( $option_data['label'] ?? '' ) : $option_data;
				$option_description = is_array( $option_data ) ? ( $option_data['description'] ?? '' ) : '';
				$is_disabled        = in_array( $option_key, $disabled_options, true );
				$is_required        = in_array( $option_key, $required_options, true );
				$is_checked         = $is_required || in_array( (string) $option_key, $option_value, true );
				?>
				<p class="fc-checkboxgroup-option">
					<label>
						<?php if ( $is_disabled && $is_checked ) : ?>
							<input
								type="hidden"
								name="<?php echo esc_attr( $value['field_name'] ); ?>[]"
								value="<?php echo esc_attr( $option_key ); ?>"
							/>
						<?php endif; ?>
						<input
							type="checkbox"
							name="<?php echo esc_attr( $value['field_name'] ); ?>[]"
							value="<?php echo esc_attr( $option_key ); ?>"
							<?php checked( $is_checked, true ); ?>
							<?php disabled( $is_disabled ); ?>
						/>
						<strong><?php echo esc_html( $option_label ); ?></strong>
					</label>
					<?php if ( ! empty( $option_description ) ) : ?>
						<br>
						<span class="description"><?php echo esc_html( $option_description ); ?></span>
					<?php endif; ?>
				</p>
			<?php endforeach; ?>
		</fieldset>
		<?php

		if ( 'end' === $checkboxgroup ) {
			?>
			</fieldset>
			</td>
			</tr>
			<?php
		}
	}

}

FluidCheckout_Admin_SettingType_Checkboxgroup::instance();

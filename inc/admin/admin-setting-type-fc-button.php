<?php
defined( 'ABSPATH' ) || exit;

/**
 * Button field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Button extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_button', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$button_text = ! empty( $value[ 'button_text' ] ) ? $value[ 'button_text' ] : $value[ 'title' ];
		$url = ! empty( $value[ 'url' ] ) ? $value[ 'url' ] : '';
		$open_in_new_tab = ! array_key_exists( 'open_in_new_tab', $value ) || false !== $value[ 'open_in_new_tab' ];
		$button_style = ! empty( $value[ 'button_style' ] ) ? $value[ 'button_style' ] : 'secondary';
		$show_external_icon = ! array_key_exists( 'show_external_icon', $value ) || false !== $value[ 'show_external_icon' ];
		$is_disabled = $renderer->is_control_disabled( $value );

		$button_classes = array( 'fc-settings-button' );
		if ( 'primary' === $button_style ) {
			$button_classes[] = 'fc-settings-button--primary';
		}

		$renderer->output_field_start( $value, array( 'label_for' => false ) );

		if ( $is_disabled || empty( $url ) ) {
			?>
			<span class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?> disabled" aria-disabled="true">
				<?php echo esc_html( $button_text ); ?>
				<?php if ( $show_external_icon && class_exists( 'FluidCheckout_Admin' ) ) : ?>
					<?php echo FluidCheckout_Admin::instance()->get_external_tab_icon_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</span>
			<?php
		}
		else {
			?>
			<a
				class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>"
				href="<?php echo esc_url( $url ); ?>"
				<?php if ( $open_in_new_tab ) : ?>
					target="_blank"
					rel="noopener noreferrer"
				<?php endif; ?>
				<?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php echo esc_html( $button_text ); ?>
				<?php if ( $show_external_icon && class_exists( 'FluidCheckout_Admin' ) ) : ?>
					<?php echo FluidCheckout_Admin::instance()->get_external_tab_icon_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</a>
			<?php
		}

		echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_Button::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Paragraph field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Paragraph extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_paragraph', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$allowed_html = wp_kses_allowed_html( 'post' );

		// Allow settings-page navigation attributes on in-content links
		if ( ! isset( $allowed_html[ 'a' ] ) || ! is_array( $allowed_html[ 'a' ] ) ) {
			$allowed_html[ 'a' ] = array();
		}
		$allowed_html[ 'a' ][ 'data-fc-settings-nav-link' ] = true;
		$allowed_html[ 'a' ][ 'data-fc-settings-preview-keep-page' ] = true;
		?>
		<div class="fc-settings-field fc-settings-field--paragraph" <?php echo $renderer->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<p><?php echo wp_kses( $value[ 'desc' ], $allowed_html ); ?></p>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Paragraph::instance();

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
		?>
		<div class="fc-settings-field fc-settings-field--paragraph forminp-<?php echo esc_attr( sanitize_title( $value[ 'type' ] ) ); ?>">
			<p><?php echo wp_kses_post( $value[ 'desc' ] ); ?></p>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Paragraph::instance();

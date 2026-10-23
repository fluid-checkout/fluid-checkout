<?php
defined( 'ABSPATH' ) || exit;

/**
 * Horizontal separator field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Separator extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_separator', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $_value  Unused admin settings args values.
	 */
	public function output_field( $_value ) {
		?>
		<div class="fc-settings-field fc-settings-field--separator" role="separator" aria-hidden="true"></div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Separator::instance();

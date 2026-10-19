<?php
defined( 'ABSPATH' ) || exit;

/**
 * Group title field type for the Fluid Checkout settings page, a title with a separator above it for a group of fields inside a card.
 */
class FluidCheckout_Admin_SettingType_GroupTitle extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_group_title', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values. Custom attributes are added to the title row, such as conditional attributes.
	 */
	public function output_field( $value ) {
		?>
		<div class="fc-settings-field fc-settings-field--group-title" <?php echo FluidCheckout_Admin_Settings_Renderer::instance()->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<h4 class="fc-settings-field__group-title"><?php echo esc_html( $value[ 'title' ] ); ?></h4>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_GroupTitle::instance();

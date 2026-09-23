<?php
defined( 'ABSPATH' ) || exit;

/**
 * Design template selector field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_TemplateSelector extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_template_selector', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$renderer = FluidCheckout_Admin_Settings_Renderer::instance();
		$field_description = $renderer->get_field_description( $value );
		$option_value = $value[ 'value' ];
		$custom_attributes_html = $renderer->get_custom_attributes_html( $value );

		$renderer->output_field_start( $value, array( 'label_for' => false ) );
		?>
		<fieldset>
			<ul>
			<?php foreach ( $value[ 'options' ] as $key => $args ) : ?>
				<li>
					<label <?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'class="disabled"' : ''; ?>><input
						name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						<?php echo $custom_attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php checked( $key, $option_value ); ?>
						<?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'disabled' : ''; ?>
						/> <?php echo esc_html( $args[ 'label' ] ); ?></label>
				</li>
			<?php endforeach; ?>
			</ul>
		</fieldset>
		<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<style>
			<?php
			foreach ( $value[ 'options' ] as $key => $val ) {
				$option_image_url = apply_filters( 'fc_design_template_option_image_url', FluidCheckout::$directory_url . 'images/admin/fc-template-'. esc_attr( $key ) .'.png', $key, $val );
				?>
				.forminp-fc_template_selector label:has( .fc-design-template__option[value="<?php echo esc_attr( $key ); ?>"] ):after {
					background-image: url( <?php echo esc_url( $option_image_url ) ?> );
				}
				<?php
			}
			?>
		</style>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_TemplateSelector::instance();

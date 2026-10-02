<?php
defined( 'ABSPATH' ) || exit;

/**
 * Layout selector field type for the Fluid Checkout settings page.
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
	 * Get the image URL for a layout option.
	 *
	 * @param  string  $key  Option key.
	 * @param  array   $val  Option args.
	 */
	public function get_option_image_url( $key, $val ) {
		$image_url = FluidCheckout::$directory_url . 'images/admin/fc-layout-' . esc_attr( $key ) . '.png';

		/**
		 * Filter the layout selector option image URL.
		 *
		 * @param  string  $image_url  Image URL.
		 * @param  string  $key        Option key.
		 * @param  array   $val        Option args.
		 */
		return apply_filters( 'fc_layout_selector_option_image_url', $image_url, $key, $val );
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
		$option_value = $value[ 'value' ];
		$group_label = ! empty( $value[ 'title' ] ) ? $value[ 'title' ] : __( 'Layout', 'fluid-checkout' );

		$renderer->output_field_start( $value, array( 'label_for' => false ) );
		?>
		<div class="fc-settings-layout-options" role="group" aria-label="<?php echo esc_attr( $group_label ); ?>">
			<?php foreach ( $value[ 'options' ] as $key => $args ) : ?>
				<?php
				$option_disabled = $field_disabled || ( array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] );
				$option_classes = 'fc-settings-layout-option';
				$option_classes .= $option_disabled ? ' is-disabled' : '';
				$option_classes .= (string) $key === (string) $option_value ? ' is-selected' : '';
				$image_url = $this->get_option_image_url( $key, $args );
				?>
				<label class="<?php echo esc_attr( $option_classes ); ?>">
					<input
						name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						<?php echo $custom_attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php checked( $key, $option_value ); ?>
						<?php disabled( $option_disabled ); ?>
						/>
					<span class="fc-settings-layout-option__image" aria-hidden="true">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="">
					</span>
					<span class="fc-settings-layout-option__label"><?php echo esc_html( $args[ 'label' ] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$renderer->output_field_end( $value );
	}

}

FluidCheckout_Admin_SettingType_LayoutSelector::instance();

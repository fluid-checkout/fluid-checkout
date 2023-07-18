<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
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
		add_action( 'woocommerce_admin_field_fc_template_selector', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Custom attribute handling.
		$custom_attributes_esc = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes_esc[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		$option_value = $value['value'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<fieldset>
					<ul>
					<?php
					foreach ( $value['options'] as $key => $args ) {
						?>
						<li>
							<label <?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'class="disabled"' : ''; ?>><input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								value="<?php echo esc_attr( $key ); ?>"
								type="radio"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes_esc ); // WPCS: XSS ok. ?>
								<?php checked( $key, $option_value ); ?>
                                        <?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'disabled' : ''; ?>
								/> <?php echo esc_html( $args[ 'label' ] ); ?></label>
						</li>
						<?php
					}
					?>
					</ul>
					<?php echo $description; // WPCS: XSS ok. ?>
					<style>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							$option_image_url = apply_filters( 'fc_design_template_option_image_url', FluidCheckout::$directory_url . 'images/admin/fc-template-'. esc_attr( $key ) .'.png', $key, $val );
							?>
							.forminp-fc_template_selector .fc-design-template__option[value="<?php echo esc_attr( $key ); ?>"]:after {
								background-image: url( <?php echo esc_url( $option_image_url ) ?> );
							}
							<?php
						}
						?>
					</style>
				</fieldset>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_TemplateSelector::instance();

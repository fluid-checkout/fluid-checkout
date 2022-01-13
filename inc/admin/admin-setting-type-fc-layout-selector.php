<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
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
		add_action( 'woocommerce_admin_field_fc_layout_selector', array( $this, 'output_field_type_fc_layout_seletor' ), 10 );
	}



	/**
	 * Output the layout selector setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_layout_seletor( $value ) {
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
					<?php echo $description; // WPCS: XSS ok. ?>
					<ul>
					<?php
					foreach ( $value['options'] as $key => $val ) {
						?>
						<li>
							<label><input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								value="<?php echo esc_attr( $key ); ?>"
								type="radio"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes_esc ); // WPCS: XSS ok. ?>
								<?php checked( $key, $option_value ); ?>
								/> <?php echo esc_html( $val ); ?></label>
						</li>
						<?php
					}
					?>
					</ul>
					<style>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							$option_image_url = apply_filters( 'fc_checkout_layout_option_image_url', FluidCheckout::$directory_url . 'images/admin/fc-layout-'. esc_attr( $key ) .'.png', $key, $val );
							?>
							.forminp-fc_layout_selector .fc-checkout-layout__option[value="<?php echo esc_attr( $key ); ?>"]:after {
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

FluidCheckout_Admin_SettingType_LayoutSelector::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Textarea extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_textarea', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Custom attribute handling.
		$custom_attributes = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
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
				<?php echo $description; // WPCS: XSS ok. ?>

				<textarea
					name="<?php echo esc_attr( $value['field_name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					<?php echo array_key_exists( 'disabled', $value ) && false !== $value[ 'disabled' ] ? 'disabled' : ''; ?>
					><?php echo esc_textarea( $option_value ); // WPCS: XSS ok. ?></textarea>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Textarea::instance();

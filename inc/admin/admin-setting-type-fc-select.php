<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Select extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_select', array( $this, 'output_field' ), 10 );
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
				<select
					name="<?php echo esc_attr( $value['field_name'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					<?php echo array_key_exists( 'disabled', $value ) && false !== $value[ 'disabled' ] ? 'disabled' : ''; ?>
					>
					<?php
					foreach ( $value['options'] as $key => $args ) {
						// Handle options type
						if ( ! is_array( $args ) ) {
							$args = array( 'label' => $args );
						}
						?>
						<option value="<?php echo esc_attr( $key ); ?>"
							<?php
							if ( is_array( $option_value ) ) {
								selected( in_array( (string) $key, $option_value, true ), true );
							} else {
								selected( $option_value, (string) $key );
							}
							?>
							<?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'disabled' : ''; ?>
						><?php echo esc_html( $args[ 'label' ] ); ?></option>
						<?php
					}
					?>
				</select> <?php echo $description; // WPCS: XSS ok. ?>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Select::instance();

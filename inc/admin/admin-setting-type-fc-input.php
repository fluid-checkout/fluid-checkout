<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Input extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_text', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_password', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_datetime', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_datetime-local', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_date', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_month', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_time', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_week', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_number', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_email', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_url', array( $this, 'output_field' ), 10 );
		add_action( 'woocommerce_admin_field_fc_tel', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Get field type
		$hook_name = current_action();
		$field_type = str_replace( 'woocommerce_admin_field_fc_', '', $hook_name );

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
		<tr class="<?php echo esc_attr( $value['row_class'] ); ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input
					name="<?php echo esc_attr( $value['field_name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $field_type ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					<?php echo array_key_exists( 'disabled', $value ) && false !== $value[ 'disabled' ] ? 'disabled' : ''; ?>
					/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Input::instance();

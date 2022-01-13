<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
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
		add_action( 'woocommerce_admin_field_fc_paragraph', array( $this, 'output_field_type_fc_paragraph' ), 10 );
	}



	/**
	 * Output the paragraph setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_type_fc_paragraph( $value ) {
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		?>
		<tr valign="top">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<p>
					<?php echo $description; // WPCS: XSS ok. ?>
				</p>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Paragraph::instance();

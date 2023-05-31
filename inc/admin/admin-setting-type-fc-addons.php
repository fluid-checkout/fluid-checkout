<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Addons extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_addons', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		?>
		<tr valign="top" class="fc-addons__row fc-addons__row--pro">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<ul class="fc-addons fc-addons--pro">
					<div class="fc-addons__image">image</div>
					<h3 class="fc-addons__item-title">title</h3>
					<div class="fc-addons__item-description">description</div>
					<div class="fc-addons__item-actions">actions</div>
				</ul>
			</td>
		</tr>

		<tr valign="top" class="fc-addons__row">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<ul class="fc-addons">
					<li class="fc-addons__item">
						<div class="fc-addons__image">image</div>
						<h3 class="fc-addons__item-title">Google Address Autocomplete</h3>
						<div class="fc-addons__item-description">description</div>
						<div class="fc-addons__item-actions">actions</div>
					</li>

					<li class="fc-addons__item">
						<div class="fc-addons__image">image</div>
						<h3 class="fc-addons__item-title">Address book</h3>
						<div class="fc-addons__item-description">description</div>
						<div class="fc-addons__item-actions">actions</div>
					</li>

					<li class="fc-addons__item">
						<div class="fc-addons__image">image</div>
						<h3 class="fc-addons__item-title">EU-VAT assistant</h3>
						<div class="fc-addons__item-description">description</div>
						<div class="fc-addons__item-actions">actions</div>
					</li>
				</ul>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Addons::instance();

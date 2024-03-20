<?php
/**
 * Form shipping dpd terminals template.
 *
 * @category Form shipping
 * @package  Dpd
 * @author   DPD
 */

defined( 'ABSPATH' ) || exit;
?>

<?php // CHANGE: Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>
<div class="wc_shipping_dpd_terminals form-row form-row-wide validate-required">
	<?php // CHANGE: Add label to use `label` element and fix markup for required attribute ?>
	<label for="<?php echo $field_id ?>"><?php echo esc_html( __( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ); ?>&nbsp;<abbr class="required" aria-label="<?php echo esc_attr( __( '(Required)', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'required', 'woocommerce' ) ); ?>">*</abbr></label>
	<span class="woocommerce-input-wrapper">
		<select  name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" style="width: 100%;">
			<option value="" <?php selected( $selected, '' ); ?>><?php echo esc_html__( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ); ?></option>
			<?php foreach ( $terminals as $group_name => $locations ) : ?>
				<optgroup label="<?php echo esc_attr( $group_name ); ?>">
					<?php foreach ( $locations as $location ) : ?>
						<option data-cod="<?php echo esc_attr( $location->cod ); ?>" value="<?php echo esc_html( $location->parcelshop_id ); ?>" <?php selected( $selected, $location->parcelshop_id ); ?>><?php echo esc_html( $location->name ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php // CHANGE: END - Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>

<?php
/**
 * Form shipping dpd terminals template.
 *
 * @category Form shipping
 * @package  Dpd
 * @author   DPD
 */

// CHANGE: Add abspath check and exit
defined( 'ABSPATH' ) || exit;
?>

<?php // CHANGE: Replace custom-dropdown table row with a select form field for FC checkout ?>
<?php // CHANGE: Remove th / custom-dropdown / selected-option / pudo-search markup ?>
<div class="wc_shipping_dpd_terminals form-row form-row-wide validate-required">
	<?php // CHANGE: Add label to use `label` element and fix markup for required attribute ?>
	<label for="<?php echo $field_id ?>"><?php echo esc_html( __( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ); ?>&nbsp;<abbr class="required" aria-label="<?php echo esc_attr( __( '(Required)', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'required', 'woocommerce' ) ); ?>">*</abbr></label>
	<span class="woocommerce-input-wrapper">
		<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" style="width: 100%;">
			<option value="" <?php selected( $selected, '' ); ?>><?php echo esc_html( __( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ); ?></option>
			<?php foreach ( $terminals as $group_name => $locations ) : ?>
				<optgroup label="<?php echo esc_html( $group_name ); ?>">
					<?php foreach ( $locations as $location ) : ?>
						<?php // CHANGE: Remove status == 1 filter around terminal options ?>
						<option data-cod="<?php echo esc_attr( $location->cod ); ?>" value="<?php echo esc_html( $location->parcelshop_id ); ?>" <?php selected( $selected, $location->parcelshop_id ); ?>><?php echo esc_html( $location->name ); ?></option>
					<?php endforeach; // CHANGE: Close locations loop ?>
				</optgroup>
			<?php endforeach; // CHANGE: Close terminal groups loop ?>
			<?php // CHANGE: Remove load-more-btn / load_more_page markup ?>
		</select>
		<?php // CHANGE: Remove wc_shipping_dpd_parcels_terminal hidden input markup ?>
	</span>
</div>
<?php // CHANGE: Remove remaining custom-dropdown closing markup ?>
<?php // CHANGE: END - Replace custom-dropdown table row with a select form field for FC checkout ?>

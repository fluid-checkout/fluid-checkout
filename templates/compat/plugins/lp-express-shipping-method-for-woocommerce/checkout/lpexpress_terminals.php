<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); ?>
<?php // CHANGE: Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>
<div class="lpexpress-terminals-field form-row form-row-wide fc-select2-field validate-required">
	<?php // CHANGE: Add a label to the field ?>
	<label for="<?php echo $field_id ?>"><?php echo esc_html( __( 'Parcel terminal', 'fluid-checkout' ) ); ?>&nbsp;<abbr class="required" aria-label="<?php echo esc_attr( __( '(Required)', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'required', 'woocommerce' ) ); ?>">*</abbr></label>
	<span class="woocommerce-input-wrapper">
		<select name="<?php echo $field_name ?>" id="<?php echo $field_id ?>" class="lpexpress_select_field" style="width: 100%">
			<option value="" <?php selected( $selected, '' ); ?>><?php _ex( '- Choose terminal -', 'empty value label for terminals', 'lp-express-shipping-method-for-woocommerce' ) ?></option>
			<?php foreach( $terminals as $group_name => $locations ) : ?>
				<optgroup label="<?php echo $group_name ?>">
					<?php foreach( $locations as $location ) : ?>
						<option value="<?php echo $location->place_id ?>"<?php selected( $selected, $location->place_id ); ?>><?php echo $location->name ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php // CHANGE: END - Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>

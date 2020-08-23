<?php
/**
 * Checkout Address Book Entries
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/address-book-entries.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

$checked_same_as_address = FluidCheckout_AddressBook::instance()->{'get_'.$address_type.'_address_entry_checked_state'}( $address_entry_same_as, false );
$checked_same_as_address_attribute = $checked_same_as_address ? 'data-address-same-as-checked="1"' : '';
?>
<div class="address-book address-book__<?php echo esc_attr( $address_type ); ?>" <?php echo $checked_same_as_address_attribute; ?>>

<?php if ( count( $address_book_entries ) > 0 ) : ?>

    <?php
    echo apply_filters( 'wfc_address_book_entries_start_tag_markup', sprintf( '<ul id="address_book_%1$s" class="address-book__entries">', esc_attr( $address_type ) ), $address_book_entries, $address_type ); ?>

	<?php
	$address_entry_template = '
	<li class="address-book-entry" %6$s>
		<input type="radio" name="%1$s_address_id" id="address_book_entry_%1$s_%2$s%8$s" data-address-type="%1$s" value="%2$s" class="address-book__entry-radio" data-address=\'%4$s\' %3$s />
		<label for="address_book_entry_%1$s_%2$s%8$s" class="address-book__entry-label">%5$s</label>
		%7$s
    </li>';
    

    // "SAME AS" ADDRESS
	if ( is_array( $address_entry_same_as ) && array_key_exists( 'address_id', $address_entry_same_as ) ) {
        $same_as_address_label = sprintf( '<span class="address-book-entry__same-as-label">%s</span>', sprintf( __( 'Same as %s', 'woocommerce-fluid-checkout' ), $same_as_address_type_label ) );
		$same_as_address_label .= apply_filters( 'wfc_address_book_entry_label_markup', FluidCheckout_AddressBook::instance()->get_billing_address_entry_display_label( $address_entry_same_as ), $address_entry_same_as, $address_type );

		$new_address_item = false;
		echo apply_filters( 'wfc_address_book_entry_markup',
			sprintf( $address_entry_template,
				$address_type,
				$address_entry_same_as[ 'address_id' ],
				'data-address-book-same ' . checked( $checked_same_as_address, true, false ),
				wp_json_encode( $address_entry_same_as ),
                $same_as_address_label,
                'data-address-book-same-as-entry',
                sprintf( '<input type="hidden" name="%1$s_address_same_as" id="address_book_entry_%1$s_same_as" value="1"/>', $address_type ),
                '_same_as'
			), $address_entry_same_as, $address_type, $same_as_address_label, $new_address_item, $checked_same_as_address );
	}
	

	// SAVED ADDRESSES
	$first = ! is_array( $address_entry_same_as );
	foreach ( $address_book_entries as $address_id => $address_entry ) :
		// Skip saved address used for "same as" option
		$same_as_address_id = is_array( $address_entry_same_as ) && array_key_exists( 'address_id', $address_entry_same_as ) ? $address_entry_same_as[ 'address_id' ] : false;
		if ( $address_id == $same_as_address_id ) { continue; }

		$checked_address = FluidCheckout_AddressBook::instance()->{'get_'.$address_type.'_address_entry_checked_state'}( $address_entry, $first );
		$address_label = apply_filters( 'wfc_address_book_entry_label_markup', FluidCheckout_AddressBook::instance()->get_billing_address_entry_display_label( $address_entry ), $address_entry, $address_type );
		$new_address_item = false;
		echo apply_filters( 'wfc_address_book_entry_markup',
			sprintf( $address_entry_template,
				$address_type,
				$address_id,
				checked( $checked_address, true, false ),
				wp_json_encode( $address_entry ),
                $address_label,
                '', // No extra list item attributes
                '', // No extra elements
                '' // No extra radio id parts
			), $address_entry, $address_type, $address_label, $new_address_item, $checked_address );
		
		$first = false;
	endforeach; 
	
	
	// NEW ADDRESS
	$new_address_entry = array( 'address_id' => 'new' );
	$new_address_item = true;
    $checked_new_address = is_array( $address_entry_same_as ) && array_key_exists( 'address_id', $address_entry_same_as ) && $address_entry_same_as['address_id'] == 'new' ? false : FluidCheckout_AddressBook::instance()->{'get_'.$address_type.'_address_entry_checked_state'}( $new_address_entry, false );
    $address_label = __( 'Use a different address', 'woocommerce-fluid-checkout' );
	echo apply_filters( 'wfc_address_book_entry_markup',
		sprintf( $address_entry_template,
			$address_type,
			$new_address_entry[ 'address_id' ],
			'data-address-book-new ' . checked( $checked_new_address, true, false ),
			wp_json_encode( array_merge( $new_address_entry, FluidCheckout::instance()->get_user_geo_location() ) ), // default address values
            $address_label,
            'data-address-book-new-entry',
            '', // No extra elements
            '' // No extra radio id parts
		), $new_address_entry, $address_type, $address_label, $new_address_item, $checked_new_address );
	?>
	
	<?php echo apply_filters( 'wfc_address_book_entries_end_tag_markup', '</ul>', $address_book_entries, $address_type ); ?>

<?php endif; ?>

</div>

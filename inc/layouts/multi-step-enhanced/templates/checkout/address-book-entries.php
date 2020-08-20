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
?>
<div class="address-book address-book__<?php echo esc_attr( $address_type ); ?>">

<?php if ( count( $address_book_entries ) > 0 ) : ?>

	<?php echo apply_filters( 'wfc_address_book_entries_start_tag_markup', sprintf( '<ul id="address_book_%1$s" class="address-book__entries">', esc_attr( $address_type ) ), $address_book_entries, $address_type ); ?>

	<?php
	$address_entry_template = '<li class="address-book-entry"><input type="radio" name="%1$s_address_id" id="address_book_entry_%1$s_%2$s" data-address-type="%1$s" value="%2$s" class="address-book__entry-radio" data-address=\'%4$s\' %3$s />
		<label for="address_book_entry_%1$s_%2$s" class="address-book__entry-label">%5$s</label>
	</li>';
	
	$first = true;
	foreach ( $address_book_entries as $address_id => $address_entry ) :
		$checked_address = FluidCheckout_AddressBook::instance()->{'get_'.$address_type.'_address_entry_checked_state'}( $address_entry, $first );
		
		$address_label = apply_filters( 'wfc_address_book_entry_label_markup',
			sprintf( '%1$s %2$s %3$s %4$s %5$s',
				array_key_exists( 'company', $address_entry ) ? '<span class="address-book-entry__company">'.$address_entry['company'].'</span>' : '',
				array_key_exists( 'first_name', $address_entry ) ? '<span class="address-book-entry__name">'.$address_entry['first_name'] . ' ' . $address_entry['last_name'].'</span>' : '',
				'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
				array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
				'<span class="address-book-entry__location">'.$address_entry['city'] . ' ' . $address_entry['state'] . ' ' . $address_entry['country'].'</span>'
			), $address_entry, $address_type );

		$new_address_item = true;
		echo apply_filters( 'wfc_address_book_entry_markup',
			sprintf( $address_entry_template,
				$address_type,
				$address_id,
				checked( $checked_address, true, false ),
				wp_json_encode( $address_entry ),
				$address_label
			), $address_entry, $address_type, $address_label, $new_address_item, $checked_address );
		
		$first = false;
	endforeach; ?>

	<?php
	$new_address_entry = array( 'address_id' => 'new' );
	$new_address_item = true;
	$checked_new_address = FluidCheckout_AddressBook::instance()->{'get_'.$address_type.'_address_entry_checked_state'}( $new_address_entry, false );
	echo apply_filters( 'wfc_address_book_entry_markup',
		sprintf( $address_entry_template,
			$address_type,
			$new_address_entry[ 'address_id' ],
			'data-address-book-new ' . checked( $checked_new_address, true, false ),
			wp_json_encode( array_merge( $new_address_entry, FluidCheckout::instance()->get_user_geo_location() ) ), // default address values
			__( 'Enter a new address', 'woocommerce-fluid-checkout' )
		), $new_address_entry, $address_type, $address_label, $new_address_item, $checked_new_address );
	?>
	
	<?php echo apply_filters( 'wfc_address_book_entries_end_tag_markup', '</ul>', $address_book_entries, $address_type ); ?>

<?php endif; ?>

</div>

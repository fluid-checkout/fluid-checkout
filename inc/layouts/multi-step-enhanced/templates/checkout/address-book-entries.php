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

<?php if ( sizeof( $address_book_entries ) > 0 ) : ?>

	<?php echo apply_filters( 'wfc_address_book_entries_start_tag_markup', '<ul id="address_book" class="address-book__entries">' ); ?>

	<?php
	$address_entry_template = '<li class="address-book-entry"><input type="radio" name="address_book_%1$d" id="address_book_entry_%1$d_%2$s" data-address-type="%1$d" value="%2$s" class="address-book__entry-radio" %3$s />
		<label for="address_book_entry_%1$d_%2$s" class="address-book__entry-label">%4$s</label>
	</li>';
	
	foreach ( $address_book_entries as $address_id => $address_entry ) :
		$checked_address = sizeof( $address_book_entries ) === 1 || ( array_key_exists( 'default', $address_entry ) && $address_entry['default'] === true );
		
		$address_label = apply_filters( 'wfc_address_book_entry_markup',
			sprintf( '%1$s %2$s %3$s %4$s %5$s',
			array_key_exists( 'company', $address_entry ) ? '<div class="address-book-entry__company">'.$address_entry['company'].'</div>' : '',
			array_key_exists( 'first_name', $address_entry ) ? '<div class="address-book-entry__name">'.$address_entry['first_name'] . ' ' . $address_entry['last_name'].'</div>' : '',
			'<div class="address-book-entry__address_1">'.$address_entry['address_1'].'</div>',
			array_key_exists( 'address_2', $address_entry ) ? '<div class="address-book-entry__address_2">'.$address_entry['address_2'].'</div>' : '',
			'<div class="address-book-entry__location">'.$address_entry['city'] . ' ' . $address_entry['state'] . ' ' . $address_entry['country'].'</div>'
		), $address_entry, $address_type );

		// TODO: Add edit address link/button markup
		echo apply_filters( 'wfc_address_book_entry_markup',
			sprintf( $address_entry_template,
				$address_type,
				$address_id,
				checked( $checked_address, true, false ),
				$address_label
			),
			$address_entry, $address_type );
		
	endforeach; ?>

	<?php
	// New address option
	echo apply_filters( 'wfc_address_book_entry_markup',
		sprintf( $address_entry_template,
			$address_type,
			'-1', // Identifies the new address option
			'', // New address option unchecked
			__( 'Enter a new address', 'woocommerce-fluid-checkout' )
		),
		$address_entry, $address_type );
	?>
	
	<?php echo apply_filters( 'wfc_address_book_entries_end_tag_markup', '</ul>' ); ?>

<?php endif; ?>

</div>

<?php
/**
 * Account Address Book Entries
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/address-book-entries.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="u-columns woocommerce-Addresses col2-set addresses">

<?php

$address_entry_template = '
<div class="wfc-address-book-entry" data-address-book-entry="%4$s" data-address=\'%5$s\'>
    <address>%3$s</address>
    <a href="%1$s" class="edit">%2$s</a>
</div>';

// SAVED ADDRESSES
foreach ( $address_book_entries as $address_id => $address_entry ) :
    $address_label = apply_filters( 'wfc_address_book_entry_label_account_markup', FluidCheckout_AddressBook::instance()->get_account_address_entry_display_label( $address_entry ), $address_entry );

    echo apply_filters( 'wfc_address_book_entry_account_markup',
        sprintf( $address_entry_template,
        
            '#', // temp edit button url
            esc_html__( 'Edit', 'woocommerce-fluid-checkout' ),
            wp_kses_post( $address_label ),
            $address_id,
            wp_json_encode( $address_entry )

        ), $address_entry, $address_label );
endforeach;

?>
</div>

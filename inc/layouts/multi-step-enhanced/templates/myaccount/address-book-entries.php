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

<?php do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php
$address_entry_template = '
<div class="wfc-address-book-entry" data-address-book-entry="%1$s" data-address=\'%2$s\'>
    <address>%3$s</address>
    <button class="edit button" data-address-book-entry-id="%1$s">%4$s</a>
</div>';

// SAVED ADDRESSES
foreach ( $address_book_entries as $address_id => $address_entry ) :
    $address_label = apply_filters( 'wfc_address_book_entry_label_account_markup', FluidCheckout_AddressBook::instance()->get_account_address_entry_display_label( $address_entry ), $address_entry );

    echo apply_filters( 'wfc_address_book_entry_account_markup',
        sprintf( $address_entry_template,
            $address_id,
            wp_json_encode( $address_entry ),
            wp_kses_post( $address_label ),
            esc_html__( 'Edit', 'woocommerce-fluid-checkout' )
        ), $address_entry, $address_label );
endforeach;
?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>

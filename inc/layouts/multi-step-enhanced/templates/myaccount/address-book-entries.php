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

$page_title = $address_id == 'new' ? __( 'New address', 'woocommerce-fluid-checkout' ) : __( 'Edit address', 'woocommerce-fluid-checkout' );
?>

<?php do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $address_id ) : ?>
    
    <?php
    $address_entry_template = '
    <div class="wfc-address-book-entry" data-address-book-entry="%1$s" data-address=\'%2$s\'>
        <address>%3$s</address>
        <a href="%5$s" class="edit button" data-address-book-entry-id="%1$s">%4$s</a>
        <a href="%7$s" class="delete button button-secondary" data-address-book-entry-id="%1$s">%6$s</a>
    </div>';

    // SAVED ADDRESSES
    foreach ( $address_book_entries as $address_id => $address_entry ) :
        $address_label = apply_filters( 'wfc_address_book_entry_label_account_markup', FluidCheckout_AddressBook::instance()->get_account_address_entry_display_label( $address_entry ), $address_entry );

        echo apply_filters( 'wfc_address_book_entry_account_markup',
            sprintf( $address_entry_template,
                $address_id,
                wp_json_encode( $address_entry ),
                wp_kses_post( $address_label ),
                esc_html__( 'Edit', 'woocommerce-fluid-checkout' ),
                esc_url( wc_get_endpoint_url( 'edit-address', $address_id ) ),
                esc_html__( 'Delete', 'woocommerce-fluid-checkout' ),
                esc_url( wc_get_endpoint_url( 'delete-address', $address_id ) )
            ),
            $address_entry,
            $address_label
        );
    endforeach;
    ?>

    <div class="row">
        <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'new' ) ); ?>" class="button button-secondary" ><?php esc_html_e( 'Add address', 'woocommerce-fluid-checkout' ); ?></a>
    </div>

<?php else : ?>

    <form method="post">

		<h3><?php echo apply_filters( 'wfc_edit_address_entry_title', $page_title, $address_id ); ?></h3><?php // @codingStandardsIgnoreLine ?>

		<div class="woocommerce-address-fields">
			<?php do_action( "wfc_before_edit_address_form_{$address_id}" ); ?>

			<div class="woocommerce-address-fields__field-wrapper">
				<?php
				foreach ( $address as $key => $field ) {
					woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
				}
				?>
			</div>

			<?php do_action( "wfc_after_edit_address_form_{$address_id}" ); ?>

			<div class="row">
				<button type="submit" class="button" name="save_address" value="<?php esc_attr_e( 'Save address', 'woocommerce-fluid-checkout' ); ?>"><?php esc_html_e( 'Save address', 'woocommerce-fluid-checkout' ); ?></button>
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>" class="button button-secondary" ><?php esc_html_e( 'Cancel', 'woocommerce-fluid-checkout' ); ?></a>
				<?php wp_nonce_field( 'woocommerce-edit_address_book', 'woocommerce-edit-address-book-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address_book" />
			</div>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>

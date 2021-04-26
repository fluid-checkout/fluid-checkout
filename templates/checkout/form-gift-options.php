<?php
/**
 * Checkout gift options form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-gift-options.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="wfc-gift-options">

    <?php do_action( 'wfc_checkout_gift_options_before' ); ?>

    <?php
    foreach ( $checkbox_field as $key => $field ) {
        woocommerce_form_field( $key, $field, $has_gift_options_checked );
    }
    ?>

    <div id="wfc-gift-options__field-wrapper" <?php echo $has_gift_options_checked ? 'class="is-collapsed"' : ''; ?> data-collapsible data-collapsible-content <?php echo $has_gift_options_checked ? 'data-collapsible-initial-state="expanded"' : 'data-collapsible-initial-state="collapsed"'; ?>>
        <div class="collapsible-content__inner">

            <?php do_action( 'wfc_checkout_gift_options_before_fields' ); ?>

            <?php
            foreach ( $display_fields as $key => $field ) {
                woocommerce_form_field( $key, $field, $gift_options[ $key ] );
            }
            ?>

            <?php do_action( 'wfc_checkout_gift_options_after_fields' ); ?>

        </div>

    </div>

    <?php do_action( 'wfc_checkout_gift_options_after' ); ?>

</div>


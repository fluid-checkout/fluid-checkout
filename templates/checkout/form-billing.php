<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-billing-fields">
	<?php // CHANGE: Remove billing section title ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php // CHANGE: Display billing fields except those added at contact step ?>
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );
		foreach ( $fields as $key => $field ) {
			/**
			 * The variable `$ignore_fields` is passed in by woocommerce fluid checkout
			 * @see Hook `wfc_checkout_contact_step_field_ids`
			 */
			if ( ! in_array( $key, $ignore_fields ) ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

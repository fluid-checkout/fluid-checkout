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
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @fc-version 1.2.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-billing-fields">
	<?php // CHANGE: Remove billing section title ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<?php // CHANGE: Add markup for collapsible-block component ?>
	<div id="woocommerce-billing-fields__field-wrapper" class="woocommerce-billing-fields__field-wrapper <?php echo $is_billing_same_as_shipping ? 'is-collapsed' : ''; ?>" data-collapsible data-collapsible-content data-collapsible-initial-state="<?php echo $is_billing_same_as_shipping ? 'collapsed' : 'expanded'; ?>">
		<div class="collapsible-content__inner">
		<?php // CHANGE: Display billing fields which might be copied from shipping fields ?>
		<?php
		foreach ( $billing_same_as_shipping_fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
		<?php // CHANGE: Add markup for collapsible-block component ?>
		</div>
	</div>

	<?php // CHANGE: Display billing only fields ?>
	<?php if ( count( $billing_only_fields ) > 0 ) : ?>
	<div class="woocommerce-billing-only-fields__field-wrapper">
		<?php
		foreach ( $billing_only_fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

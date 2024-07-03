<?php
/**
 * Checkout shipping information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-shipping.php.
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
 * @fc-version 3.1.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

// CHANGE: Get shipping fields lists separately
$shipping_same_as_billing_fields = FluidCheckout_Steps::instance()->get_shipping_same_billing_fields();
$shipping_only_fields = FluidCheckout_Steps::instance()->get_shipping_only_fields();

// CHANGE: Get initial state for collapsible-block component
$collapsible_initial_state = FluidCheckout_Steps::instance()->is_billing_country_allowed_for_shipping() === null ? 'expanded' : ( $is_shipping_same_as_billing ? 'collapsed' : 'expanded' );
$collapsible_initial_state = apply_filters( 'fc_checkout_shipping_collapsible_initial_state', $collapsible_initial_state );
?>


<div class="woocommerce-shipping-fields">

	<?php do_action( 'fc_checkout_before_step_shipping_fields_inside' ); ?>

	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<?php // CHANGE: Output "ship to different address" option via hook ?>
		<?php do_action( 'fc_before_checkout_shipping_address_wrapper', $checkout ); ?>

		<div class="shipping_address">

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<?php // CHANGE: Add markup for collapsible-block component ?>
			<div id="woocommerce-shipping-fields__field-wrapper" class="woocommerce-shipping-fields__field-wrapper <?php echo 'collapsed' === $collapsible_initial_state ? 'is-collapsed' : ''; ?>" data-collapsible data-collapsible-content data-collapsible-initial-state="<?php echo esc_attr( $collapsible_initial_state ); ?>">
				<?php // CHANGE: Display shipping fields which might be copied from shipping to billing fields ?>
				<?php
				foreach ( $shipping_same_as_billing_fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php // CHANGE: Display shipping only fields ?>
			<?php do_action( 'fc_before_checkout_shipping_only_form', $checkout ); ?>

			<?php // CHANGE: Display shipping only fields ?>
			<?php if ( $shipping_only_fields && count( $shipping_only_fields ) > 0 ) : ?>
			<div class="woocommerce-shipping-only-fields__field-wrapper">
				<?php
				foreach ( $shipping_only_fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php endif; ?>

	<?php
	// CHANGE: Added for compatibility with plugins that use this action hook
	do_action( 'woocommerce_checkout_shipping', $checkout );
	?>

	<?php do_action( 'fc_checkout_after_step_shipping_fields_inside' ); ?>

</div>


<?php
/**
 * Order review section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order-section.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 2.2.0
 * @wc-version 3.5.0
 * @wc-original checkout/form-checkout.php
 */

defined( 'ABSPATH' ) || exit;

// Prepare attribute strings
$attributes_str = implode( ' ', array_map( array( FluidCheckout::instance(), 'map_html_attributes' ), array_keys( $attributes ), $attributes ) );
$attributes_inner_str = implode( ' ', array_map( array( FluidCheckout::instance(), 'map_html_attributes' ), array_keys( $attributes_inner ), $attributes_inner ) );

// Since version 2.2.0 the order summary is always output to the sidebar
// We need to keep passing this variables as a parameter to all custom action hooks for backwards compatibility
$is_sidebar_widget = true;
?>

<?php do_action( 'fc_checkout_before_order_review', $is_sidebar_widget ); ?>

<div <?php echo $attributes_str; // WPCS: XSS ok. ?>>

	<div <?php echo $attributes_inner_str; // WPCS: XSS ok. ?>>

		<?php do_action( 'fc_checkout_before_order_review_inside', $is_sidebar_widget ); ?>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

		<div class="fc-checkout-order-review__head">

			<?php do_action( 'fc_checkout_after_order_review_title_before', $is_sidebar_widget ); ?>

			<h3 class="fc-checkout-order-review-title fc-step__substep-title"><?php echo esc_html( $order_review_title ); ?></h3>

			<?php do_action( 'fc_checkout_after_order_review_title_after', $is_sidebar_widget ); ?>

		</div>

		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

		<?php do_action( 'fc_checkout_order_review_sidebar_before_actions', $is_sidebar_widget ); ?>

		<div class="fc-checkout-order-review__actions-mobile">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="fc-checkout-order-review__edit-cart"><?php echo esc_html( __( 'Edit cart', 'fluid-checkout' ) ); ?></a>
			<button type="button" class="fc-checkout-order-review__close-order-summary <?php echo esc_attr( apply_filters( 'fc_order_summary_continue_button_classes', 'button' ) ); ?>" data-flyout-close aria-label="<?php echo esc_html( __( 'Close and continue with checkout', 'fluid-checkout' ) ); ?>"><?php echo esc_html( __( 'Continue', 'fluid-checkout' ) ); ?></button>
		</div>

		<?php do_action( 'fc_checkout_after_order_review_inside', $is_sidebar_widget ); ?>

	</div>
</div>

<?php do_action( 'fc_checkout_after_order_review', $is_sidebar_widget ); ?>

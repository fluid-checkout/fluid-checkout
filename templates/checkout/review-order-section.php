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
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

$attributes = array();
$attributes_inner = array();

if ( $is_sidebar_widget ) {
	$attributes = array_merge( $attributes, array(
		'id' => 'wfc-checkout-order-review',
		'data-flyout' => true,
		'data-flyout-order-review' => true,
		'data-flyout-open-animation-class' => 'fade-in-down',
		'data-flyout-close-animation-class' => 'fade-out-up',
	) );

	$attributes_inner = array_merge( $attributes_inner, array(
		'data-flyout-content' => true,
	) );
}

$attributes_str = implode( ' ', array_map( array( FluidCheckout::instance(), 'map_html_attributes' ), array_keys( $attributes ), $attributes ) );
$attributes_inner_str = implode( ' ', array_map( array( FluidCheckout::instance(), 'map_html_attributes' ), array_keys( $attributes_inner ), $attributes_inner ) );
?>

<?php do_action( 'wfc_checkout_before_order_review' ); ?>

<div class="wfc-checkout-order-review" <?php echo $attributes_str; ?>>
	
	<div class="wfc-checkout-order-review__inner" <?php echo $attributes_inner_str; ?>>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
		
		<?php // CHANGE: Remove id attribute to avoid duplicate element id, added a class to apply styles to the element ?>
		<h3 class="wfc-checkout-order-review-title"><?php echo esc_html( $order_review_title ); ?></h3>
		
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

		<?php if ( $is_sidebar_widget ) : ?>
			<div class="wfc-checkout-order-review__actions">
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout-order-review__edit-cart"><?php echo __( 'Edit Cart', 'woocommerce-fluid-checkout' ); ?></a>
				<button href="#" class="button" data-flyout-close><?php echo __( 'Continue', 'woocommerce-fluid-checkout' ); ?></button>
			</div>
		<?php endif; ?>
	
	</div>
</div>

<?php do_action( 'wfc_checkout_after_order_review' ); ?>

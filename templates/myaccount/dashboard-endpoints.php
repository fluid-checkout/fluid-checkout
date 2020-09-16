<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard-endpoints.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$list_classes = apply_filters( 'wfc_account_dashboard_endpoints_list_classes', '' );
?>

<ul class="wfc-dashboard-navigation <?php echo esc_attr( $list_classes ); ?>">

	<?php foreach ( $dashboard_endpoints as $endpoint => $endpoint_values ) :
		$image_html = FluidCheckout_AccountPages::instance()->get_account_dashboard_endpoint_image_html( $endpoint, $endpoint_values );
		$item_classes = apply_filters( 'wfc_account_dashboard_endpoints_item_classes', 'wfc-dashboard-navigation__item--'. $endpoint );
		?>
		<li class="wfc-dashboard-navigation__item <?php echo esc_attr( $item_classes ); ?>">
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">
				<?php if ( ! empty( $image_html ) ) : ?>
					<div class="wfc-dashboard-navigation__item-image"><?php echo $image_html; ?></div>
				<?php endif; ?>
				<div class="wfc-dashboard-navigation__item-label"><?php echo esc_html( $endpoint_values['label'] ); ?></div>
				<div class="wfc-dashboard-navigation__item-description"><?php echo wp_kses_post( $endpoint_values['description'] ); ?></div>
			</a>
		</li>
	<?php endforeach; ?>

</ul>

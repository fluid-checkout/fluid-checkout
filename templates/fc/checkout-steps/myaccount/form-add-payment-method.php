<?php
/**
 * Add payment method form form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-add-payment-method.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.9.0
 * @fc-version 5.0.0
 */

defined( 'ABSPATH' ) || exit;

$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

if ( $available_gateways ) : ?>
	<form id="add_payment_method" method="post">

		<?php // CHANGE: Add wrapper class ?>
		<div id="fc-wrapper" class="fc-wrapper <?php
			/**
			 * Filters the CSS classes of the wrapper on the add-payment-method page.
			 *
			 * @since 2.0.0
			 *
			 * @param string $classes CSS classes. Default empty string.
			 */
			echo esc_attr( apply_filters( 'fc_wrapper_classes_add_payment_method_page', '' ) );
		?>">

		<div id="payment" class="woocommerce-Payment">
			<?php // CHANGE: Add class `wc_payment_methods` ?>
			<ul class="woocommerce-PaymentMethods wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e( 'Payment methods', 'woocommerce' ); ?>">
				<?php
				// Chosen Method.
				if ( count( $available_gateways ) ) {
					current( $available_gateways )->set_current();
				}

				foreach ( $available_gateways as $gateway ) {
					// CHANGE: Removed inline payment method list item markup in favor of checkout template
					// CHANGE: Use template file from checkout to display payment method.
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
				// CHANGE: Removed inline payment method closing brace
				?>
			</ul>

			<?php do_action( 'woocommerce_add_payment_method_form_bottom' ); ?>

			<div class="form-row">
				<?php wp_nonce_field( 'woocommerce-add-payment-method', 'woocommerce-add-payment-method-nonce' ); ?>
				<?php
				// CHANGE: Add filter for custom add-payment-method button classes
				$fc_add_payment_method_button_classes = implode( ' ', apply_filters( 'fc_add_payment_method_button_classes', array() ) );
				$fc_add_payment_method_theme_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
				?>
				<button type="submit" class="woocommerce-Button woocommerce-Button--alt button alt<?php echo esc_attr( $fc_add_payment_method_theme_button_class ); ?> <?php echo esc_attr( $fc_add_payment_method_button_classes ); ?>" id="place_order" value="<?php esc_attr_e( 'Add payment method', 'woocommerce' ); ?>"><?php esc_html_e( 'Add payment method', 'woocommerce' ); ?></button>
				<?php // CHANGE: END - Add filter for custom add-payment-method button classes ?>
				<input type="hidden" name="woocommerce_add_payment_method" id="woocommerce_add_payment_method" value="1" />
			</div>
		</div>

		</div>
		<?php // CHANGE: END - Add wrapper class ?>

	</form>
<?php else : ?>
	<?php wc_print_notice( esc_html__( 'New payment methods can only be added during checkout. Please contact us if you require assistance.', 'woocommerce' ), 'notice' ); ?>
<?php endif; ?>

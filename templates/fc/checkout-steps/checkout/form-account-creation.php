<?php
/**
 * Checkout account creation form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-account-creation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 2.3.0
 * @wc-version 3.6.0
 * @wc-original checkout/form-billing.php
 */

defined( 'ABSPATH' ) || exit;

// CHANGE: Determine create account checked state
$create_account_checked = FluidCheckout_Steps::instance()->is_create_account_checked() || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) );
$collapsible_initial_state = $create_account_checked ? 'expanded' : 'collapsed';
?>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<?php // CHANGE: Use variable to determine checked state ?>
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( $create_account_checked, true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account', 'fluid-checkout' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<?php // CHANGE: Add class `fc-field-group` and collapsible block attributes ?>
			<div class="create-account fc-field-group <?php echo 'collapsed' === $collapsible_initial_state ? 'is-collapsed' : ''; ?>" data-collapsible data-collapsible-content data-autofocus data-collapsible-initial-state="<?php echo esc_attr( $collapsible_initial_state ); ?>">
				<div class="collapsible-content__inner">
					<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>

					<?php // CHANGE: Removed the `clear` div element as clearing is applied via CSS ?>
				<?php // CHANGE: Close collapsible block inner content element ?>
				</div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>

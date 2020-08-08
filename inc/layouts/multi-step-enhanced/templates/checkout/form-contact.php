<?php
/**
 * Checkout contact form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-contact.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @see     templates/checkout/form-billing.php
 * @package woocommerce-fluid-checkout
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'wfc_checkout_before_step_contact_fields' ); ?>

<div class="wfc-contact-fields">
	
	<?php if ( is_user_logged_in() ) : ?>
		<div class="wfc-contact-user-data" data-user-data-wrapper>
			<?php do_action( 'wfc_checkout_before_user_data' ); ?>

			<ul class="wfc-user-data">
				<?php
				foreach ( $user_data as $key => $value ) :
					echo '<li class="wfc-user-data__'.$key.'">'.$value.'</li>';
				endforeach;
				?>
				<li class="wfc-user-data__edit"><a href="#edit-info" data-user-contact-edit role="button"><?php _e( 'Edit', 'woocommerce-fluid-checkout' ) ?></a></li>
			</ul>
			
			<?php do_action( 'wfc_checkout_after_user_data' ); ?>
		</div>
		<noscript>
			<style type="text/css">
			.wfc-user-identification { display: none !important; }
			.wfc-contact-fields__wrapper { display: block !important; }
			</style>
		</noscript>
	<?php endif; ?>

	
	<div class="wfc-contact-fields__wrapper">
		<?php do_action( 'wfc_checkout_contact_before_fields' ); ?>

		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );
		foreach ( $fields as $key => $field ) {
			// Display only fields in display list
			if ( in_array( $key, $display_fields ) ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
		}
		?>
		
		<?php do_action( 'wfc_checkout_contact_after_fields' ); ?>
	</div>

</div>

<?php do_action( 'wfc_checkout_after_contact_fields' ); ?>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>

<?php do_action( 'wfc_checkout_after_step_contact_fields' ); ?>

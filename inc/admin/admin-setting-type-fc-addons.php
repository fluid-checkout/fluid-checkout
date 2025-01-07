<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Addons extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Field types
		add_action( 'woocommerce_admin_field_fc_addons', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		?>

		<?php if ( ! FluidCheckout::instance()->is_pro_installed() && ! FluidCheckout::instance()->is_pro_activated() ) : ?>
		<tr valign="top" class="fc-dashboard-section__row fc-dashboard-section__row--special-offers">

			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">

				<h3 class="fc-dashboard-section__row-title"><?php echo esc_html( __( 'Special offers', 'fluid-checkout' ) ); ?></h3>

				<p><?php echo wp_kses_post( __( 'Extend all the goodness of Fluid Checkout to your cart page and thank you pages.', 'fluid-checkout' ) ); ?></p>

				<ul class="fc-addons-list fc-addons-list--pro">
					<li class="fc-addons__item fc-addons__item--pro">
						<div class="fc-addons__item-header">
							<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fluid-checkout-pro-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?>">
							<div class="fc-addons__item-title-section">
								<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?></h3>
								<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Design templates, optimized cart and order received pages, account matching, and much more.', 'fluid-checkout' ) ); ?></p>
							</div>
						</div>
						<div class="fc-addons__item-description">
							<p><?php echo wp_kses_post( __( 'The best tools to further <strong>improve your WooCommerce checkout conversion rate</strong> and make the purchase experience consistent on the entire journey.', 'fluid-checkout' ) ); ?></p>
							<ul class="fc-addons__item-features-list">
								<li><?php echo esc_html( __( 'More design templates', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Optimized cart page', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Optimized order received / thank you page', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Edit cart contents at checkout page', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Account matching / user matching', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Express Checkout buttons from supported payment methods', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Trust symbols on the cart and thank you pages', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'More positions for the coupon code on the checkout page', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Integrated coupon code field on the cart page', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'International phone numbers', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Gift messages and packing slips', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Local pickup and in-store collection', 'fluid-checkout' ) ); ?></li>
							</ul>
						</div>
						<div class="fc-addons__item-actions">
							<a href="<?php echo esc_url( 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Upgrade to PRO &mdash; 99 EUR', 'fluid-checkout' ) ); ?></a>
						</div>
					</li>

					<li class="fc-addons__item fc-addons__item--bundle">
						<div class="fc-addons__item-header">
							<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fluid-checkout-pro-bundle-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Fluid Checkout PRO – Bundle', 'fluid-checkout' ) ); ?>">
							<div class="fc-addons__item-title-section">
								<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Fluid Checkout PRO – Bundle', 'fluid-checkout' ) ); ?></h3>
								<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'All PRO features + All add-ons for a special price.', 'fluid-checkout' ) ); ?></p>
							</div>
						</div>
						<div class="fc-addons__item-description">
							<p><?php echo wp_kses_post( __( 'The bundle includes all add-ons we currently offer and Fluid Checkout add-ons we launch in the future.', 'fluid-checkout' ) ); ?></p>
							<ul class="fc-addons__item-features-list">
								<li><strong><?php echo esc_html( __( '226 EUR value (Save 42%)', 'fluid-checkout' ) ); ?></strong></li>
								<li><?php echo esc_html( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Google Address Autocomplete', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'Address Book', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'EU-VAT Assistant', 'fluid-checkout' ) ); ?></li>
								<li><?php echo esc_html( __( 'All future add-ons *', 'fluid-checkout' ) ); ?></li>
							</ul>
							<p><?php echo wp_kses_post( __( '* For as long as you have an active subscription.', 'fluid-checkout' ) ); ?></p>
						</div>
						<div class="fc-addons__item-actions">
							<a href="<?php echo esc_url( 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro-bundle&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Get the Bundle – Only 129 EUR', 'fluid-checkout' ) ); ?></a>
						</div>
					</li>
				</ul>

				<div class="fc-dashboard__disclaimer">
					<ul>
						<li><?php echo wp_kses_post( __( 'All prices shown in EUR. If there are any divergencies with the prices on our website, the offers shown on the website superseed these and will be applied.', 'fluid-checkout' ) ); ?></li>
					</ul>
				</div>

			</td>

		</tr>
		<?php endif; ?>

		<tr valign="top" class="fc-dashboard-section__row">
			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				
				<h3 class="fc-dashboard-section__row-title"><?php echo esc_html( __( 'Add-ons', 'fluid-checkout' ) ); ?></h3>
				<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Enhance your checkout experience with these add-ons.', 'fluid-checkout' ) ); ?></p>
				
				<ul class="fc-addons-list">
					<?php if ( FluidCheckout::instance()->is_pro_installed() ) : ?>

						<?php if ( ! FluidCheckout_AdminNotices::instance()->is_dismissed( 'bundle_offer' ) ) : ?>
						<li class="fc-addons__item fc-addons__item--bundle">
							<div class="fc-addons__item-header">
								<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fluid-checkout-pro-bundle-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Fluid Checkout PRO – Bundle', 'fluid-checkout' ) ); ?>">
								<div class="fc-addons__item-title-section">
									<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Fluid Checkout PRO – Bundle', 'fluid-checkout' ) ); ?></h3>
									<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'All PRO features + All add-ons for a special price.', 'fluid-checkout' ) ); ?></p>
								</div>
							</div>
							<div class="fc-addons__item-description">
								<p><?php echo wp_kses_post( __( 'The bundle includes all add-ons we currently offer and Fluid Checkout add-ons we launch in the future.', 'fluid-checkout' ) ); ?></p>
								<p><strong><?php echo esc_html( __( '226 EUR value (Save 42%)', 'fluid-checkout' ) ); ?></strong></p>
							</div>
							<div class="fc-addons__item-actions">
								<a href="<?php echo esc_url( 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro-bundle&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Get the Bundle – Only 129 EUR', 'fluid-checkout' ) ); ?></a>
								<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'dismiss_notice', 'fc_notice' => 'bundle_offer', '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'I already have it – Hide this offer', 'fluid-checkout' ) ); ?></a>
							</div>
						</li>
						<?php endif; ?>

						<li class="fc-addons__item fc-addons__item--pro">
							<div class="fc-addons__item-header">
								<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fluid-checkout-pro-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?>">
								<div class="fc-addons__item-title-section">
									<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?></h3>
									<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Design templates, optimized cart and order received pages, account matching, and much more.', 'fluid-checkout' ) ); ?></p>
								</div>
							</div>
							<div class="fc-addons__item-description">
								<p><?php echo wp_kses_post( __( 'The best tools to further <strong>improve your WooCommerce checkout conversion rate</strong> and make the purchase experience consistent on the entire journey.', 'fluid-checkout' ) ); ?></p>
							</div>
							<div class="fc-addons__item-actions">
								<?php if ( FluidCheckout::instance()->is_pro_activated() ) : ?>
									<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'activate_plugin', 'plugin' => 'fluid-checkout-pro/fluid-checkout-pro.php', '_wpnonce' => wp_create_nonce( 'fc-activate-plugin' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'Installed &mdash; Activate', 'fluid-checkout' ) ); ?></a>
								<?php endif; ?>
							</div>
						</li>
					<?php endif; ?>

					<li class="fc-addons__item">
						<div class="fc-addons__item-header">
							<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fc-google-address-autocomplete-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Google Address Autocomplete', 'fluid-checkout' ) ); ?>">
							<div class="fc-addons__item-title-section">
								<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Google Address Autocomplete', 'fluid-checkout' ) ); ?></h3>
								<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Up to 40% less checkout fields to fill in.', 'fluid-checkout' ) ); ?></p>
							</div>
						</div>
						<div class="fc-addons__item-description">
							<p><?php echo wp_kses_post( __( 'Avoid delivery delays and unsatisfied customers. Collect the <strong>correct address information</strong> from the first time they buy with you.', 'fluid-checkout' ) ); ?></p>
						</div>
						<div class="fc-addons__item-actions">
							<?php if ( FluidCheckout::instance()->is_plugin_activated( 'fc-google-address-autocomplete/fc-google-address-autocomplete.php' ) ) : ?>
								<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
							<?php elseif ( FluidCheckout::instance()->is_plugin_installed( 'fc-google-address-autocomplete/fc-google-address-autocomplete.php' ) ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'activate_plugin', 'plugin' => 'fc-google-address-autocomplete/fc-google-address-autocomplete.php', '_wpnonce' => wp_create_nonce( 'fc-activate-plugin' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'Installed &mdash; Activate', 'fluid-checkout' ) ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( 'https://fluidcheckout.com/fc-google-address-autocomplete/?mtm_campaign=addons&mtm_kwd=fc-gaa&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Get this add-on &mdash; 29 EUR', 'fluid-checkout' ) ); ?></a>
							<?php endif; ?>
						</div>
					</li>

					<li class="fc-addons__item">
						<div class="fc-addons__item-header">
							<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fc-address-book-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'Address Book', 'fluid-checkout' ) ); ?>">
							<div class="fc-addons__item-title-section">
								<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'Address book', 'fluid-checkout' ) ); ?></h3>
								<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Multiple saved addresses for shipping and billing.', 'fluid-checkout' ) ); ?></p>
							</div>
						</div>
						<div class="fc-addons__item-description">
							<p><?php echo wp_kses_post( __( 'Let customers <strong>save multiple shipping and billing addresses</strong> on their account and choose which ones to use at checkout and cart pages.', 'fluid-checkout' ) ); ?></p>
						</div>
						<div class="fc-addons__item-actions">
							<?php if ( FluidCheckout::instance()->is_plugin_activated( 'fc-address-book/fc-address-book.php' ) ) : ?>
								<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
							<?php elseif ( FluidCheckout::instance()->is_plugin_installed( 'fc-address-book/fc-address-book.php' ) ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'activate_plugin', 'plugin' => 'fc-address-book/fc-address-book.php', '_wpnonce' => wp_create_nonce( 'fc-activate-plugin' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'Installed &mdash; Activate', 'fluid-checkout' ) ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( 'https://fluidcheckout.com/fc-address-book/?mtm_campaign=addons&mtm_kwd=fc-adb&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Get this add-on &mdash; 59 EUR', 'fluid-checkout' ) ); ?></a>
							<?php endif; ?>
						</div>
					</li>

					<li class="fc-addons__item">
						<div class="fc-addons__item-header">
							<img class="fc-addons__item-image" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/addons/fc-vat-assistant-icon.png' ); ?>" alt="<?php echo esc_attr( __( 'EU-VAT Assistant', 'fluid-checkout' ) ); ?>">
							<div class="fc-addons__item-title-section">
								<h3 class="fc-addons__item-title"><?php echo esc_html( __( 'EU-VAT Assistant', 'fluid-checkout' ) ); ?></h3>
								<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Simplified EU-VAT validation for your store.', 'fluid-checkout' ) ); ?></p>
							</div>
						</div>
						<div class="fc-addons__item-description">
							<p><?php echo wp_kses_post( __( 'Collect and <strong>validate EU VAT numbers at the checkout page</strong>, removes tax charges on reverse charge basis and confirms customer location when needed.', 'fluid-checkout' ) ); ?></p>
						</div>
						<div class="fc-addons__item-actions">
							<?php if ( FluidCheckout::instance()->is_plugin_activated( 'fc-vat-assistant/fc-vat-assistant.php' ) ) : ?>
								<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
							<?php elseif ( FluidCheckout::instance()->is_plugin_installed( 'fc-vat-assistant/fc-vat-assistant.php' ) ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'activate_plugin', 'plugin' => 'fc-vat-assistant/fc-vat-assistant.php', '_wpnonce' => wp_create_nonce( 'fc-activate-plugin' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'Installed &mdash; Activate', 'fluid-checkout' ) ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( 'https://fluidcheckout.com/fc-eu-vat-assistant/?mtm_campaign=addons&mtm_kwd=fc-vat&mtm_source=lite-plugin' ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( __( 'Get this add-on &mdash; 39 EUR', 'fluid-checkout' ) ); ?></a>
							<?php endif; ?>
						</div>
					</li>

				</ul>

				<div class="fc-dashboard__disclaimer">
					<ul>
						<li><?php echo wp_kses_post( __( 'All add-ons are sold separately and require the <strong>Fluid Checkout Lite</strong> plugin to be installed and activated, except when noted.', 'fluid-checkout' ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'All prices shown in EUR. If there are any divergencies with the prices on our website, the offers shown on the website superseed these and will be applied.', 'fluid-checkout' ) ); ?></li>
					</ul>
				</div>

			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Addons::instance();

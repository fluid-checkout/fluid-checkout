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

		// Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
	}



	/**
	 * Enqueue Dashboard Add-ons AJAX script on the Lite dashboard.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		$current_screen = get_current_screen();

		// Bail if not on WooCommerce settings page
		if ( ! $current_screen || 'woocommerce_page_wc-settings' !== $current_screen->id ) { return; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		// Bail if not on dashboard settings page
		if ( 'fc_checkout' !== $current_tab || ! empty( $current_section ) ) { return; }

		wp_enqueue_script(
			'fc-admin-dashboard-addons',
			FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-dashboard-addons' ),
			array(),
			null,
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);

		// EXCEPTION: Runtime values — AJAX URL and nonces for dashboard site-key / add-on actions.
		wp_localize_script(
			'fc-admin-dashboard-addons',
			'fcAdminDashboardAddonsSettings',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'validateNonce' => wp_create_nonce( 'fc-validate-site-key' ),
				'clearNonce'    => wp_create_nonce( 'fc-clear-site-key' ),
				'activateNonce' => wp_create_nonce( 'fc-activate-plugin' ),
				'installNonce'  => wp_create_nonce( 'fc-install-plugin' ),
				'i18n'          => array(
					'processing'         => __( 'Processing…', 'fluid-checkout' ),
					'genericError'       => __( 'Something went wrong. Please try again.', 'fluid-checkout' ),
					'validate'           => __( 'Validate', 'fluid-checkout' ),
					'removeKey'          => __( 'Remove key', 'fluid-checkout' ),
					'activate'           => __( 'Activate', 'fluid-checkout' ),
					'manageLicenses'     => __( 'Manage licenses', 'fluid-checkout' ),
					'manageLicensesUrl'  => $this->get_manage_licenses_url(),
					'descriptionEmpty'   => __( 'Paste your site key to install and activate products you already own.', 'fluid-checkout' ),
					'descriptionSaved'   => __( 'Install and activate below the add-ons you own, or purchase the add-ons you need.', 'fluid-checkout' ),
					'getSiteKey'         => __( 'Get your site key', 'fluid-checkout' ),
					'getSiteKeyUrl'      => FluidCheckout::FC_LICENSES_ACCOUNT_URL,
				),
			)
		);
	}



	/**
	 * Get the Fluid Checkout My Account site detail URL for this merchant site.
	 *
	 * @return string
	 */
	private function get_manage_licenses_url() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';
		$base = untrailingslashit( FluidCheckout::FC_LICENSES_ACCOUNT_URL );

		if ( '' === $host ) {
			return trailingslashit( $base );
		}

		return $base . '/' . rawurlencode( $host );
	}



	/**
	 * Get the add-ons catalog for the Dashboard add-ons list.
	 */
	public function get_addons_catalog() {
		$directory_url = FluidCheckout::$directory_url;

		$catalog = array(
			array(
				'id'            => 'bundle',
				'type'          => 'bundle',
				'item_class'    => 'fc-addons__item--bundle',
				'show_when'     => 'pro_installed',
				'dismiss_notice'=> 'bundle_offer',
				'title'         => __( 'Fluid Checkout PRO – Bundle', 'fluid-checkout' ),
				'subtitle'      => __( 'All PRO features + All add-ons for a special price.', 'fluid-checkout' ),
				'description'   => __( 'The bundle includes all add-ons we currently offer and Fluid Checkout add-ons we launch in the future.', 'fluid-checkout' ),
				'value_note'    => __( '226 EUR value (Save 42%)', 'fluid-checkout' ),
				'image'         => $directory_url . 'images/admin/addons/fluid-checkout-pro-bundle-icon.png',
				'purchase_url'  => 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro-bundle&mtm_source=lite-plugin',
				'purchase_label'=> __( 'Get the Bundle – Only 129 EUR', 'fluid-checkout' ),
			),
			array(
				'id'            => 'fluid-checkout-pro',
				'type'          => 'plugin',
				'item_class'    => 'fc-addons__item--pro',
				'plugin_file'   => 'fluid-checkout-pro/fluid-checkout-pro.php',
				'plugin_slug'   => 'fluid-checkout-pro',
				'title'         => __( 'Fluid Checkout PRO', 'fluid-checkout' ),
				'subtitle'      => __( 'Design templates, optimized cart and order received pages, account matching, and much more.', 'fluid-checkout' ),
				'description'   => __( 'The best tools to further <strong>improve your WooCommerce checkout conversion rate</strong> and make the purchase experience consistent on the entire journey.', 'fluid-checkout' ),
				'image'         => $directory_url . 'images/admin/addons/fluid-checkout-pro-icon.png',
				'purchase_url'  => 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro&mtm_source=lite-plugin',
				'purchase_label'=> __( 'Upgrade to PRO &mdash; 99 EUR', 'fluid-checkout' ),
			),
			array(
				'id'            => 'fc-google-address-autocomplete',
				'type'          => 'plugin',
				'plugin_file'   => 'fc-google-address-autocomplete/fc-google-address-autocomplete.php',
				'plugin_slug'   => 'fc-google-address-autocomplete',
				'title'         => __( 'Google Address Autocomplete', 'fluid-checkout' ),
				'subtitle'      => __( 'Up to 40% less checkout fields to fill in.', 'fluid-checkout' ),
				'description'   => __( 'Avoid delivery delays and unsatisfied customers. Collect the <strong>correct address information</strong> from the first time they buy with you.', 'fluid-checkout' ),
				'image'         => $directory_url . 'images/admin/addons/fc-google-address-autocomplete-icon.png',
				'purchase_url'  => 'https://fluidcheckout.com/fc-google-address-autocomplete/?mtm_campaign=addons&mtm_kwd=fc-gaa&mtm_source=lite-plugin',
				'purchase_label'=> __( 'Get this add-on &mdash; 29 EUR', 'fluid-checkout' ),
			),
			array(
				'id'            => 'fc-address-book',
				'type'          => 'plugin',
				'plugin_file'   => 'fc-address-book/fc-address-book.php',
				'plugin_slug'   => 'fc-address-book',
				'title'         => __( 'Address book', 'fluid-checkout' ),
				'subtitle'      => __( 'Multiple saved addresses for shipping and billing.', 'fluid-checkout' ),
				'description'   => __( 'Let customers <strong>save multiple shipping and billing addresses</strong> on their account and choose which ones to use at checkout and cart pages.', 'fluid-checkout' ),
				'image'         => $directory_url . 'images/admin/addons/fc-address-book-icon.png',
				'purchase_url'  => 'https://fluidcheckout.com/fc-address-book/?mtm_campaign=addons&mtm_kwd=fc-adb&mtm_source=lite-plugin',
				'purchase_label'=> __( 'Get this add-on &mdash; 59 EUR', 'fluid-checkout' ),
			),
			array(
				'id'            => 'fc-vat-assistant',
				'type'          => 'plugin',
				'plugin_file'   => 'fc-vat-assistant/fc-vat-assistant.php',
				'plugin_slug'   => 'fc-vat-assistant',
				'title'         => __( 'EU-VAT Assistant', 'fluid-checkout' ),
				'subtitle'      => __( 'Simplified EU-VAT validation for your store.', 'fluid-checkout' ),
				'description'   => __( 'Collect and <strong>validate EU VAT numbers at the checkout page</strong>, removes tax charges on reverse charge basis and confirms customer location when needed.', 'fluid-checkout' ),
				'image'         => $directory_url . 'images/admin/addons/fc-vat-assistant-icon.png',
				'purchase_url'  => 'https://fluidcheckout.com/fc-eu-vat-assistant/?mtm_campaign=addons&mtm_kwd=fc-vat&mtm_source=lite-plugin',
				'purchase_label'=> __( 'Get this add-on &mdash; 39 EUR', 'fluid-checkout' ),
			),
		);

		return apply_filters( 'fc_addons_catalog', $catalog );
	}



	/**
	 * Whether a catalog item should be shown.
	 *
	 * @param array $addon Catalog item.
	 */
	private function should_show_addon( $addon ) {
		$show_when = isset( $addon['show_when'] ) ? $addon['show_when'] : '';

		if ( 'pro_installed' === $show_when && ! FluidCheckout::instance()->is_pro_installed() ) {
			return false;
		}

		if ( ! empty( $addon['dismiss_notice'] ) && FluidCheckout_AdminNotices::instance()->is_dismissed( $addon['dismiss_notice'] ) ) {
			return false;
		}

		return true;
	}



	/**
	 * Get HTML for the Dashboard add-ons list items (without the wrapping `<ul>`).
	 *
	 * @return string
	 */
	public function get_addons_list_html() {
		ob_start();

		foreach ( $this->get_addons_catalog() as $addon ) {
			$this->output_addon_item( $addon );
		}

		return (string) ob_get_clean();
	}



	/**
	 * Output the site key field at the top of the Add-ons section.
	 */
	private function output_site_key_field() {
		// Bail if licenses client is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) { return; }

		$has_site_key  = FC_Licenses_Client::has_site_key();
		$display_value = FC_Licenses_Client::get_site_key_display_value();
		$notice        = null;

		// Refresh entitlements when a key is already saved; clear on stale/invalid
		if ( $has_site_key ) {
			$entitlements = FC_Licenses_Client::get_site_key_entitlements( FluidCheckout::FC_LICENSES_API_URL, false, FluidCheckout::FC_LICENSES_ACCOUNT_URL );

			if ( empty( $entitlements['success'] ) ) {
				FC_Licenses_Client::clear_site_key();
				$has_site_key  = false;
				$display_value = '';
				$notice        = array(
					'type'    => 'error',
					'message' => ! empty( $entitlements['error'] ) ? $entitlements['error'] : FC_Licenses_Client::get_site_key_invalid_message( FluidCheckout::FC_LICENSES_ACCOUNT_URL ),
				);
			}
		}
		?>
		<div class="fc-addons__site-key">
			<label for="fc_site_key" class="fc-addons__site-key-label"><?php echo esc_html( __( 'Site key', 'fluid-checkout' ) ); ?></label>

			<form method="post" action="" class="fc-addons__site-key-form">
				<div class="fc-addons__site-key-field-row">
					<input
						type="text"
						id="fc_site_key"
						name="fc_site_key"
						class="fc-addons__site-key-input"
						value="<?php echo esc_attr( $has_site_key ? $display_value : '' ); ?>"
						autocomplete="off"
						placeholder="<?php echo esc_attr( __( 'Enter your site key', 'fluid-checkout' ) ); ?>"
						<?php disabled( $has_site_key ); ?>
					/>
					<div class="fc-addons__site-key-field-actions">
						<?php if ( $has_site_key ) : ?>
							<button type="button" class="button fc-addons__site-key-remove"><?php echo esc_html( __( 'Remove key', 'fluid-checkout' ) ); ?></button>
						<?php else : ?>
							<button type="button" class="button button-primary fc-addons__site-key-validate"><?php echo esc_html( __( 'Validate', 'fluid-checkout' ) ); ?></button>
						<?php endif; ?>
						<a
							class="fc-addons__site-key-manage"
							href="<?php echo esc_url( $this->get_manage_licenses_url() ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						><?php echo esc_html( __( 'Manage licenses', 'fluid-checkout' ) ); ?></a>
					</div>
				</div>
			</form>

			<p class="description fc-addons__site-key-description">
				<?php if ( $has_site_key ) : ?>
					<?php echo esc_html( __( 'Install and activate below the add-ons you own, or purchase the add-ons you need.', 'fluid-checkout' ) ); ?>
				<?php else : ?>
					<?php echo esc_html( __( 'Paste your site key to install and activate products you already own.', 'fluid-checkout' ) ); ?>
					<a href="<?php echo esc_url( FluidCheckout::FC_LICENSES_ACCOUNT_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Get your site key', 'fluid-checkout' ) ); ?></a>
				<?php endif; ?>
			</p>

			<div class="fc-addons__site-key-result<?php echo ( is_array( $notice ) && ! empty( $notice['message'] ) ) ? ' fc-addons__site-key-result--' . esc_attr( ( isset( $notice['type'] ) && 'success' === $notice['type'] ) ? 'success' : 'error' ) : ''; ?>"<?php echo ( ! is_array( $notice ) || empty( $notice['message'] ) ) ? ' hidden' : ''; ?>>
				<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
					<p class="fc-addons__site-key-result-line"><?php echo wp_kses_post( $notice['message'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output action buttons for a plugin add-on card.
	 *
	 * @param array $addon Catalog item.
	 */
	private function output_plugin_addon_actions( $addon ) {
		$plugin_file = isset( $addon['plugin_file'] ) ? $addon['plugin_file'] : '';
		$plugin_slug = isset( $addon['plugin_slug'] ) ? $addon['plugin_slug'] : '';

		// Bail if plugin file is missing
		if ( empty( $plugin_file ) ) { return; }

		$is_activated = FluidCheckout::instance()->is_plugin_activated( $plugin_file );
		$is_installed = FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		$is_entitled  = false;

		if ( class_exists( 'FC_Licenses_Client' ) && ! empty( $plugin_slug ) ) {
			$is_entitled = FC_Licenses_Client::is_plugin_entitled_with_site_key( $plugin_slug, FluidCheckout::FC_LICENSES_API_URL );
		}

		if ( $is_activated ) :
			?>
			<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
			<?php
		elseif ( $is_installed ) :
			?>
			<button type="button" class="button fc-addons__item-action--activate" data-plugin="<?php echo esc_attr( $plugin_file ); ?>"><?php echo esc_html( __( 'Activate', 'fluid-checkout' ) ); ?></button>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		elseif ( $is_entitled ) :
			?>
			<button type="button" class="button button-primary button--install fc-addons__item-action--install" data-plugin="<?php echo esc_attr( $plugin_file ); ?>"><?php echo esc_html( __( 'Install', 'fluid-checkout' ) ); ?></button>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		else :
			?>
			<a href="<?php echo esc_url( $addon['purchase_url'] ); ?>" class="button button-primary" target="_blank"><?php echo wp_kses_post( $addon['purchase_label'] ); ?></a>
			<?php
		endif;
	}



	/**
	 * Output a single add-on catalog item.
	 *
	 * @param array $addon Catalog item.
	 */
	private function output_addon_item( $addon ) {
		// Bail if item should not be shown
		if ( ! $this->should_show_addon( $addon ) ) { return; }

		$item_class = 'fc-addons__item';
		if ( ! empty( $addon['item_class'] ) ) {
			$item_class .= ' ' . $addon['item_class'];
		}
		?>
		<li class="<?php echo esc_attr( $item_class ); ?>">
			<div class="fc-addons__item-header">
				<img class="fc-addons__item-image" src="<?php echo esc_url( $addon['image'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
				<div class="fc-addons__item-title-section">
					<h3 class="fc-addons__item-title"><?php echo esc_html( $addon['title'] ); ?></h3>
					<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( $addon['subtitle'] ); ?></p>
				</div>
			</div>
			<div class="fc-addons__item-description">
				<p><?php echo wp_kses_post( $addon['description'] ); ?></p>
				<?php if ( ! empty( $addon['value_note'] ) ) : ?>
					<p><strong><?php echo esc_html( $addon['value_note'] ); ?></strong></p>
				<?php endif; ?>
			</div>
			<div class="fc-addons__item-actions">
				<?php if ( 'bundle' === $addon['type'] ) : ?>
					<a href="<?php echo esc_url( $addon['purchase_url'] ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( $addon['purchase_label'] ); ?></a>
					<?php if ( ! empty( $addon['dismiss_notice'] ) ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'dismiss_notice', 'fc_notice' => $addon['dismiss_notice'], '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'I already have it – Hide this offer', 'fluid-checkout' ) ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					<?php $this->output_plugin_addon_actions( $addon ); ?>
				<?php endif; ?>
			</div>
		</li>
		<?php
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

				<h3 class="fc-dashboard-section__row-title"><?php echo esc_html( __( 'Add-ons & other products', 'fluid-checkout' ) ); ?></h3>
				<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Enhance your checkout experience with these add-ons.', 'fluid-checkout' ) ); ?></p>

				<?php $this->output_site_key_field(); ?>

				<ul class="fc-addons-list">
					<?php foreach ( $this->get_addons_catalog() as $addon ) : ?>
						<?php $this->output_addon_item( $addon ); ?>
					<?php endforeach; ?>
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

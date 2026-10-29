<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_Addons extends FluidCheckout {

	/**
	 * Option key: hide the Dashboard PRO promo section while Fluid Checkout PRO is active.
	 */
	const HIDE_DASHBOARD_PRO_PROMO_OPTION = 'fc_hide_dashboard_pro_promo_section';

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
		add_action( 'fc_admin_settings_render_field_fc_addons', array( $this, 'output_field' ), 10 );

		// Dashboard PRO promo visibility
		add_action( 'admin_init', array( $this, 'maybe_hide_dashboard_pro_promo_section' ), 5 );

		// Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Admin JS settings
		add_filter( 'fc_js_settings_admin', array( $this, 'add_js_settings_admin' ), 10 );
	}



	/**
	 * Enqueue Dashboard Add-ons AJAX script on the Lite dashboard.
	 */
	public function enqueue_scripts() {
		// Bail if not on the Dashboard tab of the settings page
		if ( ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page( 'dashboard' ) ) { return; }

		// Enqueue dashboard add-ons script
		wp_enqueue_script( 'fc-admin-dashboard-addons', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-dashboard-addons' ), array( 'fc-utils' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-dashboard-addons', 'window.addEventListener("load",function(){if(window.fcSettingsAdmin&&fcSettingsAdmin.dashboardAddons){FCAdminDashboardAddons.init(fcSettingsAdmin.dashboardAddons);}});' );
	}

	/**
	 * Add dashboard add-ons script settings to `fcSettingsAdmin`.
	 *
	 * @param   array  $settings  Admin JS settings.
	 */
	public function add_js_settings_admin( $settings ) {
		// Bail if not on the Dashboard tab of the settings page
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) || ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page( 'dashboard' ) ) { return $settings; }

		$settings[ 'dashboardAddons' ] = $this->get_dashboard_addons_script_settings();

		return $settings;
	}

	/**
	 * Get script settings for the dashboard add-ons UI.
	 *
	 * @return  array
	 */
	public function get_dashboard_addons_script_settings() {
		return array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'activateNonce' => wp_create_nonce( 'fc-activate-plugin' ),
			'i18n'          => array(
				'processing'   => __( 'Processing…', 'fluid-checkout' ),
				'genericError' => __( 'Something went wrong. Please try again.', 'fluid-checkout' ),
				'activate'     => __( 'Activate plugin', 'fluid-checkout' ),
				'activated'    => __( 'Activated', 'fluid-checkout' ),
			),
		);
	}



	/**
	 * Get the add-ons catalog for the Dashboard add-ons list.
	 */
	public function get_addons_catalog() {
		$directory_url = FluidCheckout::$directory_url;

		$catalog = array(
			array(
				'id'            => 'fluid-checkout-pro',
				'type'          => 'plugin',
				'plugin_file'   => 'fluid-checkout-pro/fluid-checkout-pro.php',
				'plugin_slug'   => 'fluid-checkout-pro',
				'title'         => __( 'Fluid Checkout PRO - The complete checkout package', 'fluid-checkout' ),
				'subtitle'      => __( 'All PRO features + all add-ons, one price. Design templates, optimized cart and order received pages, account matching, and much more.', 'fluid-checkout' ),
				'features'      => array(
					__( 'More design templates', 'fluid-checkout' ),
					__( 'Optimized cart page', 'fluid-checkout' ),
					__( 'Optimized order received / thank you page', 'fluid-checkout' ),
					__( 'Optimized order pay page', 'fluid-checkout' ),
					__( 'Express checkout buttons from supported payment methods', 'fluid-checkout' ),
					__( 'Edit cart contents at checkout page', 'fluid-checkout' ),
					__( 'Local pickup and in-store collection', 'fluid-checkout' ),
					__( 'Account matching / user matching', 'fluid-checkout' ),
					__( 'Delayed account creation', 'fluid-checkout' ),
					__( 'Address autocomplete with Google Maps APIs', 'fluid-checkout' ),
					__( 'Multiple saved addresses with the address book', 'fluid-checkout' ),
					__( 'Trust symbols on the cart and thank you pages', 'fluid-checkout' ),
					__( 'More positions for the coupon code on the checkout page', 'fluid-checkout' ),
					__( 'International phone numbers', 'fluid-checkout' ),
					__( 'Gift messages and packing slips', 'fluid-checkout' ),
					__( 'EU-VAT assistant', 'fluid-checkout' ),
				),
				'image'         => $directory_url . 'images/admin/fluid-checkout-pro-icon.svg',
				'purchase_url'  => 'https://fluidcheckout.com/pricing/?mtm_campaign=addons&mtm_kwd=fc-pro&mtm_source=lite-plugin',
				'purchase_label'=> FluidCheckout_Admin::instance()->get_pro_upgrade_button_label_without_price(),
			),
		);

		/**
		 * Filters the add-ons catalog shown on the dashboard.
		 *
		 * @since 5.0.0
		 *
		 * @param array $catalog Add-on catalog items.
		 */
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

		// Iterate catalog items except the PRO bundle promo
		foreach ( $this->get_addons_catalog() as $addon ) {
			// Skip the PRO bundle card rendered in the promo section
			if ( $this->is_pro_bundle_addon( $addon ) ) {
				continue;
			}

			$this->output_addon_item( $addon );
		}

		return (string) ob_get_clean();
	}



	/**
	 * Get the PRO bundle catalog item for the Dashboard promo section.
	 *
	 * @return array|null
	 */
	public function get_pro_bundle_addon() {
		// Bail if the Dashboard PRO promo section should not render
		if ( ! $this->should_show_dashboard_pro_promo_section() ) {
			return null;
		}

		// Iterate catalog items
		foreach ( $this->get_addons_catalog() as $addon ) {
			// Bail if this is not the PRO bundle item
			if ( ! $this->is_pro_bundle_addon( $addon ) ) {
				continue;
			}

			// Bail if the item should not be shown
			if ( ! $this->should_show_addon( $addon ) ) {
				return null;
			}

			return $addon;
		}

		return null;
	}



	/**
	 * Whether the user chose to hide the Dashboard PRO promo while PRO is active.
	 *
	 * @return bool
	 */
	public function is_dashboard_pro_promo_hidden_by_user() {
		return 'yes' === get_option( self::HIDE_DASHBOARD_PRO_PROMO_OPTION, 'no' );
	}



	/**
	 * Whether the Dashboard PRO promo section should be output.
	 *
	 * Lite always shows the section. When PRO is active, honour the hide preference.
	 *
	 * @return bool
	 */
	public function should_show_dashboard_pro_promo_section() {
		// Always show the promo while only Lite is active
		if ( ! FluidCheckout::instance()->is_pro_activated() ) {
			return true;
		}

		// Hide the promo when the user dismissed it with PRO active
		if ( $this->is_dashboard_pro_promo_hidden_by_user() ) {
			return false;
		}

		return true;
	}



	/**
	 * Persist hide preference and redirect back to the Dashboard settings tab.
	 */
	public function maybe_hide_dashboard_pro_promo_section() {
		// Bail if not a hide request
		if ( ! isset( $_GET['fc_hide_dashboard_pro_promo'] ) || '1' !== sanitize_key( wp_unslash( $_GET['fc_hide_dashboard_pro_promo'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Bail if user cannot manage WooCommerce
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

		check_admin_referer( 'fc_hide_dashboard_pro_promo' );

		// Bail if PRO is not active (preference only applies with PRO active)
		if ( ! FluidCheckout::instance()->is_pro_activated() ) { return; }

		update_option( self::HIDE_DASHBOARD_PRO_PROMO_OPTION, 'yes', false );

		wp_safe_redirect( FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' ) );
		exit;
	}



	/**
	 * Get the URL to hide the Dashboard PRO promo section.
	 *
	 * @return string
	 */
	private function get_hide_dashboard_pro_promo_section_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'fc_hide_dashboard_pro_promo' => '1',
				),
				FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' )
			),
			'fc_hide_dashboard_pro_promo'
		);
	}



	/**
	 * Get HTML for the Hide this section control on the Dashboard PRO promo.
	 *
	 * @return string
	 */
	private function get_hide_dashboard_pro_promo_section_button_html() {
		// Bail if PRO is not active
		if ( ! FluidCheckout::instance()->is_pro_activated() ) {
			return '';
		}

		return sprintf(
			'<a class="fc-settings-button fc-settings-button--primary" href="%1$s">%2$s</a>',
			esc_url( $this->get_hide_dashboard_pro_promo_section_url() ),
			esc_html( __( 'Hide this section', 'fluid-checkout' ) )
		);
	}



	/**
	 * Get action button HTML for the Dashboard PRO promo footer.
	 *
	 * @return string
	 */
	public function get_dashboard_pro_promo_actions_html() {
		$pro_addon = $this->get_pro_bundle_addon();

		// Bail if the PRO bundle item is not available
		if ( empty( $pro_addon ) ) {
			return '';
		}

		return $this->get_plugin_addon_actions_html( $pro_addon );
	}



	/**
	 * Build a product URL with Matomo tracking parameters for Dashboard add-on cards.
	 *
	 * @param  string  $product_url  Base product page URL.
	 * @param  string  $addon_id     Catalog item id used in `mtm_kwd`.
	 * @param  string  $action       Tracking action suffix (e.g. early-access, learn-more).
	 */
	private function get_tracked_product_url( $product_url, $addon_id, $action ) {
		return add_query_arg(
			array(
				'mtm_campaign' => 'addons',
				'mtm_kwd'      => $addon_id . '-' . $action,
				'mtm_source'   => 'lite-plugin',
			),
			$product_url
		);
	}



	/**
	 * Whether a catalog item is the Fluid Checkout PRO bundle card.
	 *
	 * @param array $addon Catalog item.
	 */
	private function is_pro_bundle_addon( $addon ) {
		return isset( $addon['id'] ) && 'fluid-checkout-pro' === $addon['id'];
	}



	/**
	 * Get the product page URL for a catalog item.
	 *
	 * @param array $addon Catalog item.
	 * @return string
	 */
	private function get_addon_product_url( $addon ) {
		if ( ! empty( $addon['product_url'] ) ) {
			return (string) $addon['product_url'];
		}

		if ( ! empty( $addon['purchase_url'] ) ) {
			return (string) $addon['purchase_url'];
		}

		return '';
	}



	/**
	 * Output the Learn more link for a catalog item.
	 *
	 * @param array $addon Catalog item.
	 */
	private function output_learn_more_link( $addon ) {
		$addon_id    = isset( $addon['id'] ) ? $addon['id'] : '';
		$product_url = $this->get_addon_product_url( $addon );

		// Bail if no product URL is available
		if ( '' === $product_url ) { return; }

		$learn_more_url = $this->get_tracked_product_url( $product_url, $addon_id, 'learn-more' );
		?>
		<a href="<?php echo esc_url( $learn_more_url ); ?>" class="fc-settings-button" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Learn more', 'fluid-checkout' ) ); ?></a>
		<?php
	}



	/**
	 * Output action buttons for a plugin add-on card.
	 *
	 * @param array $addon Catalog item.
	 */
	private function get_plugin_addon_actions_html( $addon ) {
		$plugin_file = isset( $addon['plugin_file'] ) ? $addon['plugin_file'] : '';

		// Bail if plugin file is missing
		if ( empty( $plugin_file ) ) {
			return '';
		}

		$is_activated   = FluidCheckout::instance()->is_plugin_activated( $plugin_file );
		$is_installed   = FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		$is_coming_soon = ! empty( $addon['coming_soon'] );
		$is_pro_bundle  = $this->is_pro_bundle_addon( $addon );
		$is_pro_active  = FluidCheckout::instance()->is_pro_activated();
		$action_type    = 'purchase';

		ob_start();

		// Dashboard PRO promo: hide control before Activated / other actions
		if ( $is_pro_bundle && $is_pro_active ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_hide_dashboard_pro_promo_section_button_html();
		}

		if ( $is_activated ) :
			$action_type = 'activated';
			?>
			<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
			<?php
		elseif ( $is_installed ) :
			$action_type = 'activate';
			// Stand-alone add-ons require PRO to be activated
			$activate_disabled = ! $is_pro_bundle && ! $is_pro_active;
			$activate_classes  = 'button button-primary fc-addons__item-action--activate';
			if ( $activate_disabled ) {
				$activate_classes .= ' disabled';
			}
			?>
			<button
				type="button"
				class="<?php echo esc_attr( $activate_classes ); ?>"
				data-action="activate"
				data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
				<?php disabled( $activate_disabled ); ?>
			><?php echo esc_html( __( 'Activate add-on', 'fluid-checkout' ) ); ?></button>
			<?php
			// Stand-alone add-ons: Learn more stays enabled even when Activate is disabled
			if ( ! $is_pro_bundle ) {
				$this->output_learn_more_link( $addon );
			}
			?>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		elseif ( $is_coming_soon ) :
			$action_type      = 'coming_soon';
			$addon_id         = isset( $addon['id'] ) ? $addon['id'] : '';
			$product_url      = $this->get_addon_product_url( $addon );
			$early_access_url = $this->get_tracked_product_url( $product_url, $addon_id, 'early-access' );
			?>
			<a href="<?php echo esc_url( $early_access_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Get early access', 'fluid-checkout' ) ); ?></a>
			<?php $this->output_learn_more_link( $addon ); ?>
			<?php
		// Lite only: Learn more for stand-alone add-ons (purchase stays on the PRO bundle card)
		elseif ( ! $is_pro_bundle && ! $is_pro_active ) :
			$action_type = 'learn_more';
			$this->output_learn_more_link( $addon );
		else :
			$action_type = 'purchase';
			?>
			<a href="<?php echo esc_url( $addon['purchase_url'] ); ?>" class="button button-primary" target="_blank"><?php echo wp_kses_post( $addon['purchase_label'] ); ?></a>
			<?php
			// Stand-alone add-ons also get Learn more beside purchase (PRO may replace with Install + Learn more)
			if ( ! $is_pro_bundle ) {
				$this->output_learn_more_link( $addon );
			}
		endif;

		// PRO bundle promo: Learn more stays on the same row as Hide / Activated / Install
		if ( $is_pro_bundle ) {
			$this->output_learn_more_link( $addon );
		}

		$html = (string) ob_get_clean();

		/**
		 * Filters the Dashboard add-on card action HTML.
		 *
		 * @since 5.0.0
		 *
		 * @param string $html        Default actions HTML.
		 * @param array  $addon       Catalog item.
		 * @param string $action_type activated|activate|purchase|coming_soon|learn_more (or custom from extensions).
		 * @param string $plugin_file Plugin basename.
		 */
		return (string) apply_filters( 'fc_dashboard_addon_actions_html', $html, $addon, $action_type, $plugin_file );
	}



	/**
	 * Output action buttons for a plugin add-on card.
	 *
	 * @param array $addon Catalog item.
	 */
	private function output_plugin_addon_actions( $addon ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->get_plugin_addon_actions_html( $addon );
	}



	/**
	 * Output the Dashboard PRO promo section (logo, copy, features, footer actions).
	 *
	 * @param array $addon PRO bundle catalog item.
	 */
	private function output_pro_promo_section( $addon ) {
		$features     = ! empty( $addon['features'] ) && is_array( $addon['features'] ) ? $addon['features'] : array();
		$actions_html = $this->get_plugin_addon_actions_html( $addon );
		$has_footer   = ! empty( $actions_html );
		?>
		<div class="fc-settings-card fc-settings-card--addons fc-settings-card--promo" id="fc-dashboard-pro-promo">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'Get more out of your checkout journey', 'fluid-checkout' ) ); ?></h3>
				<div class="fc-settings-card__promo"><?php
					echo wp_kses(
						FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'addons', 'https://fluidcheckout.com/' ),
						array(
							'a' => array(
								'class'  => true,
								'href'   => true,
								'target' => true,
								'rel'    => true,
							),
							'span' => array(
								'class' => true,
							),
						)
					);
				?></div>
			</div>

			<div class="fc-settings-card__inner">
				<div class="fc-settings-promo__intro">
					<img class="fc-settings-promo__logo" src="<?php echo esc_url( $addon['image'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
					<div class="fc-settings-promo__intro-copy">
						<p class="fc-settings-promo__product-name"><?php echo esc_html( $addon['title'] ); ?></p>
						<?php if ( ! empty( $addon['subtitle'] ) ) : ?>
							<p class="fc-settings-promo__tagline"><?php echo esc_html( $addon['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! empty( $features ) ) : ?>
					<ul class="fc-addons__item-features-list fc-addons__item-features-list--columns fc-settings-promo__features-list">
						<?php foreach ( $features as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $has_footer ) : ?>
				<div class="fc-settings-card__footer">
					<div class="fc-addons__item-actions fc-addons__item-actions--promo-inline fc-settings-promo__actions" data-fc-dashboard-pro-promo-actions>
						<?php echo $actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}



	/**
	 * Output a single add-on catalog item.
	 *
	 * @param array $addon Catalog item.
	 */
	private function output_addon_item( $addon ) {
		// Bail if item should not be shown
		if ( ! $this->should_show_addon( $addon ) ) { return; }

		$plugin_file  = isset( $addon['plugin_file'] ) ? $addon['plugin_file'] : '';
		$is_activated = ! empty( $plugin_file ) && FluidCheckout::instance()->is_plugin_activated( $plugin_file );
		$is_installed = ! empty( $plugin_file ) && FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		// Marketing layout for stand-alone add-on CTAs (Learn more, Activate/Install + Learn more)
		$is_marketing = ! $is_activated && ( ! empty( $addon['coming_soon'] ) || ! $this->is_pro_bundle_addon( $addon ) );

		$item_class = 'fc-addons__item';
		if ( ! empty( $addon['item_class'] ) ) {
			$item_class .= ' ' . $addon['item_class'];
		}

		$actions_class = 'fc-addons__item-actions';
		if ( $is_marketing ) {
			$actions_class .= ' fc-addons__item-actions--marketing';
		}
		?>
		<li class="<?php echo esc_attr( $item_class ); ?>">
			<div class="fc-addons__item-inner">
				<div class="fc-addons__item-header">
					<img class="fc-addons__item-image" src="<?php echo esc_url( $addon['image'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
					<div class="fc-addons__item-title-section">
						<?php if ( ! empty( $addon['badge'] ) ) : ?>
							<span class="fc-settings-badge"><?php echo esc_html( $addon['badge'] ); ?></span>
						<?php endif; ?>
						<h3 class="fc-addons__item-title"><?php echo esc_html( $addon['title'] ); ?></h3>
						<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( $addon['subtitle'] ); ?></p>
					</div>
				</div>
				<?php
				$has_description = ! empty( $addon['description'] );
				$has_value_note  = ! empty( $addon['value_note'] );
				$has_features    = ! empty( $addon['features'] ) && is_array( $addon['features'] );
				?>
				<?php if ( $has_description || $has_value_note || $has_features ) : ?>
					<div class="fc-addons__item-description">
						<?php if ( $has_description ) : ?>
							<p><?php echo wp_kses_post( $addon['description'] ); ?></p>
						<?php endif; ?>
						<?php if ( $has_value_note ) : ?>
							<p><strong><?php echo esc_html( $addon['value_note'] ); ?></strong></p>
						<?php endif; ?>
						<?php if ( $has_features ) : ?>
							<ul class="fc-addons__item-features-list fc-addons__item-features-list--columns">
								<?php foreach ( $addon['features'] as $feature ) : ?>
									<li><?php echo esc_html( $feature ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="fc-addons__item-footer">
				<div class="<?php echo esc_attr( $actions_class ); ?>">
					<?php if ( 'bundle' === $addon['type'] ) : ?>
						<a href="<?php echo esc_url( $addon['purchase_url'] ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( $addon['purchase_label'] ); ?></a>
						<?php if ( ! empty( $addon['dismiss_notice'] ) ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'fc_action' => 'dismiss_notice', 'fc_notice' => $addon['dismiss_notice'], '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ); ?>" class="button"><?php echo esc_html( __( 'I already have it – Hide this offer', 'fluid-checkout' ) ); ?></a>
						<?php endif; ?>
					<?php else : ?>
						<?php $this->output_plugin_addon_actions( $addon ); ?>
					<?php endif; ?>
				</div>
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
		$pro_addon      = $this->get_pro_bundle_addon();
		$has_other_addons = false;

		// Check whether any non-PRO catalog items should render
		foreach ( $this->get_addons_catalog() as $addon ) {
			// Skip the PRO bundle promo item
			if ( $this->is_pro_bundle_addon( $addon ) ) {
				continue;
			}

			// Skip items hidden by catalog rules
			if ( ! $this->should_show_addon( $addon ) ) {
				continue;
			}

			$has_other_addons = true;
			break;
		}

		// Output PRO promo section
		if ( ! empty( $pro_addon ) ) {
			$this->output_pro_promo_section( $pro_addon );
		}

		// Bail if there are no other add-on cards to list
		if ( ! $has_other_addons ) {
			return;
		}
		?>
		<div class="fc-settings-card fc-settings-card--addons">
			<div class="fc-settings-card__inner">
				<ul class="fc-addons-list">
					<?php foreach ( $this->get_addons_catalog() as $addon ) : ?>
						<?php
						// Skip the PRO bundle promo item
						if ( $this->is_pro_bundle_addon( $addon ) ) {
							continue;
						}

						$this->output_addon_item( $addon );
						?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Addons::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Promo card field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Promo extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_promo', array( $this, 'output_field' ), 10 );

		// Assets for Activate on promo cards (all settings tabs share one SPA page)
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Admin JS settings
		add_filter( 'fc_js_settings_admin', array( $this, 'add_js_settings_admin' ), 10 );
	}



	/**
	 * Enqueue Activate AJAX script on the Fluid Checkout settings page.
	 *
	 * Needed for promo cards on add-on tabs. Dashboard may replace this handle with PRO’s script.
	 */
	public function enqueue_scripts() {
		// Bail if not on the Fluid Checkout settings page
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) || ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page() ) { return; }

		// Bail if already enqueued (Dashboard tab may own enqueue there)
		if ( wp_script_is( 'fc-admin-dashboard-addons', 'enqueued' ) ) { return; }

		wp_enqueue_script( 'fc-admin-dashboard-addons', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-dashboard-addons' ), array( 'fc-utils' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-dashboard-addons', 'window.addEventListener("load",function(){if(window.fcSettingsAdmin&&fcSettingsAdmin.dashboardAddons){FCAdminDashboardAddons.init(fcSettingsAdmin.dashboardAddons);}});' );
	}

	/**
	 * Add dashboard add-ons script settings to `fcSettingsAdmin` when promo cards need Activate.
	 *
	 * @param   array  $settings  Admin JS settings.
	 */
	public function add_js_settings_admin( $settings ) {
		// Bail if not on the Fluid Checkout settings page
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) || ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page() ) { return $settings; }

		// Bail if dashboard add-ons settings already set (Dashboard tab owns that screen)
		if ( array_key_exists( 'dashboardAddons', $settings ) ) { return $settings; }

		$settings[ 'dashboardAddons' ] = array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'activateNonce' => wp_create_nonce( 'fc-activate-plugin' ),
			'i18n'          => array(
				'processing'   => __( 'Processing…', 'fluid-checkout' ),
				'genericError' => __( 'Something went wrong. Please try again.', 'fluid-checkout' ),
				'activate'     => __( 'Activate plugin', 'fluid-checkout' ),
				'activated'    => __( 'Activated', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$title = isset( $value[ 'title' ] ) ? $value[ 'title' ] : '';
		$tagline = isset( $value[ 'tagline' ] ) ? $value[ 'tagline' ] : '';
		$features = isset( $value[ 'features' ] ) && is_array( $value[ 'features' ] ) ? $value[ 'features' ] : array();
		$promo_html = isset( $value[ 'promo' ] ) ? $value[ 'promo' ] : FluidCheckout_Admin::instance()->get_pro_feature_badge_html( isset( $value[ 'id' ] ) ? $value[ 'id' ] : '' );
		$learn_more_url = isset( $value[ 'learn_more_url' ] ) ? $value[ 'learn_more_url' ] : '';
		$learn_more_label = isset( $value[ 'learn_more_label' ] ) ? $value[ 'learn_more_label' ] : __( 'Learn more', 'fluid-checkout' );
		$card_id = ! empty( $value[ 'id' ] ) ? sanitize_title( $value[ 'id' ] ) . '-card' : '';
		$actions_html = $this->get_actions_html( $value );
		$has_footer = ! empty( $actions_html ) || ! empty( $learn_more_url );
		?>
		<div class="fc-settings-card fc-settings-card--promo" <?php echo ! empty( $card_id ) ? 'id="' . esc_attr( $card_id ) . '"' : ''; ?>>
			<div class="fc-settings-card__header">
				<?php if ( ! empty( $title ) ) : ?>
					<h3 class="fc-settings-card__title"><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>
				<?php if ( ! empty( $promo_html ) ) : ?>
					<div class="fc-settings-card__promo"><?php
						echo wp_kses(
							$promo_html,
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
				<?php endif; ?>
			</div>

			<div class="fc-settings-card__inner">
				<?php if ( ! empty( $tagline ) ) : ?>
					<p class="fc-settings-promo__tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $features ) ) : ?>
					<ul class="fc-settings-promo__features">
						<?php foreach ( $features as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $has_footer ) : ?>
				<div class="fc-settings-card__footer">
					<?php if ( ! empty( $actions_html ) ) : ?>
						<div class="fc-addons__item-actions fc-settings-promo__actions">
							<?php echo $actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $learn_more_url ) ) : ?>
						<a class="fc-settings-button" href="<?php echo esc_url( $learn_more_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $learn_more_label ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}



	/**
	 * Build Activate / Install action HTML for a promo card.
	 *
	 * @param  array  $value  Admin settings args values.
	 * @return string
	 */
	public function get_actions_html( $value ) {
		$plugin_file = isset( $value[ 'plugin_file' ] ) ? (string) $value[ 'plugin_file' ] : '';

		// Bail if this promo is not tied to a plugin
		if ( '' === $plugin_file ) {
			return '';
		}

		$is_pro_promo     = false !== strpos( $plugin_file, 'fluid-checkout-pro/' );
		$pro_plugin_file  = 'fluid-checkout-pro/fluid-checkout-pro.php';
		$is_pro_installed = FluidCheckout::instance()->is_plugin_installed( $pro_plugin_file );
		$is_installed     = FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		$is_activated     = FluidCheckout::instance()->is_plugin_activated( $plugin_file );

		// Match dashboard add-on cards so PRO can leave Activated HTML unchanged.
		if ( $is_activated ) {
			$action_type = 'activated';
		} elseif ( $is_installed ) {
			$action_type = 'activate';
		} else {
			$action_type = 'purchase';
		}

		$admin = FluidCheckout_Admin::instance();

		ob_start();

		// Show the activated state when the plugin is already active
		if ( $is_activated ) :
			?>
			<span class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></span>
			<?php
		elseif ( $is_installed ) :
			// Add-on activate requires PRO; PRO itself can still be activated without that gate
			$activate_disabled = ! $is_pro_promo && ! FluidCheckout::instance()->is_pro_activated();
			$activate_classes  = 'button button-primary fc-addons__item-action--activate';
			if ( $activate_disabled ) {
				$activate_classes .= ' disabled';
			}
			$activate_label = __( 'Activate plugin', 'fluid-checkout' );
			?>
			<button
				type="button"
				class="<?php echo esc_attr( $activate_classes ); ?>"
				data-action="activate"
				data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
				<?php disabled( $activate_disabled ); ?>
			><?php echo esc_html( $activate_label ); ?></button>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		elseif ( $is_pro_promo ) :
			// PRO feature promos (Cart, Thank You, Order Pay): upgrade or activate PRO
			$purchase_url   = isset( $value[ 'purchase_url' ] ) ? (string) $value[ 'purchase_url' ] : '';
			$purchase_label = isset( $value[ 'purchase_label' ] ) ? (string) $value[ 'purchase_label' ] : '';

			if ( '' === $purchase_label && ! empty( $value[ 'purchase_price' ] ) ) {
				$purchase_label = $admin->get_pro_upgrade_button_label( (string) $value[ 'purchase_price' ] );
			}

			if ( '' !== $purchase_url && '' !== $purchase_label ) :
				?>
				<a href="<?php echo esc_url( $purchase_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post( $purchase_label ); ?></a>
				<?php
			endif;
		else :
			// Add-on promos link out to upgrade or purchase.
			$purchase_url   = isset( $value[ 'purchase_url' ] ) ? (string) $value[ 'purchase_url' ] : '';
			$purchase_label = isset( $value[ 'purchase_label' ] ) ? (string) $value[ 'purchase_label' ] : '';

			if ( '' === $purchase_label && ! empty( $value[ 'purchase_price' ] ) ) {
				$purchase_label = $admin->get_addon_purchase_button_label( (string) $value[ 'purchase_price' ] );
			}

			if ( ! $is_pro_installed ) :
				// PRO not installed: Upgrade to PRO + optional “Get only this add-on”
				$pro_price        = ! empty( $value[ 'pro_purchase_price' ] ) ? (string) $value[ 'pro_purchase_price' ] : '129 EUR';
				$pro_purchase_url = ! empty( $value[ 'pro_purchase_url' ] )
					? (string) $value[ 'pro_purchase_url' ]
					: add_query_arg(
						array(
							'mtm_campaign' => 'upgrade-pro',
							'mtm_kwd'      => sanitize_title( ! empty( $value[ 'id' ] ) ? $value[ 'id' ] : 'addon-promo' ) . '-upgrade-pro',
							'mtm_source'   => 'lite-plugin',
						),
						'https://fluidcheckout.com/pricing/'
					);
				?>
				<a href="<?php echo esc_url( $pro_purchase_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post( $admin->get_pro_upgrade_button_label( $pro_price ) ); ?></a>
				<?php if ( '' !== $purchase_url && '' !== $purchase_label ) : ?>
					<a href="<?php echo esc_url( $purchase_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post( $purchase_label ); ?></a>
				<?php endif; ?>
			<?php elseif ( '' !== $purchase_url ) : ?>
				<?php // PRO installed: price-less purchase CTA so customers are not nudged to pay again. ?>
				<a href="<?php echo esc_url( $purchase_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $admin->get_addon_purchase_button_label_without_price() ); ?></a>
			<?php endif;
		endif;

		$html = (string) ob_get_clean();

		/**
		 * Filters settings promo card action HTML.
		 *
		 * @since 5.0.0
		 *
		 * @param  array   $value        Promo field args.
		 * @param  string  $action_type  activated|activate|purchase (or custom from extensions).
		 * @param  string  $plugin_file  Plugin basename.
		 */
		return (string) apply_filters( 'fc_settings_promo_actions_html', $html, $value, $action_type, $plugin_file );
	}

}

FluidCheckout_Admin_SettingType_Promo::instance();

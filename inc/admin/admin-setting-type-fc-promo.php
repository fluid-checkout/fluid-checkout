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
	}



	/**
	 * Enqueue Activate AJAX script on the Fluid Checkout settings page.
	 *
	 * Needed for promo cards on add-on tabs. Dashboard may replace this handle with PRO’s script.
	 */
	public function enqueue_scripts() {
		// Bail if not on the Fluid Checkout settings page
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) || ! FluidCheckout_Admin_Settings_Page::instance()->is_settings_page() ) { return; }

		// Bail if the dashboard script is already registered (Dashboard tab owns enqueue there)
		if ( wp_script_is( 'fc-admin-dashboard-addons', 'enqueued' ) ) { return; }

		wp_enqueue_script( 'fc-admin-dashboard-addons', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-dashboard-addons' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// EXCEPTION: Runtime values — AJAX URL and nonce for local Activate.
		wp_localize_script(
			'fc-admin-dashboard-addons',
			'fcAdminDashboardAddonsSettings',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'activateNonce' => wp_create_nonce( 'fc-activate-plugin' ),
				'i18n'          => array(
					'processing'   => __( 'Processing…', 'fluid-checkout' ),
					'genericError' => __( 'Something went wrong. Please try again.', 'fluid-checkout' ),
					'activate'     => __( 'Activate plugin', 'fluid-checkout' ),
				),
			)
		);
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

		$is_installed = FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		$action_type  = $is_installed ? 'activate' : 'purchase';

		ob_start();

		if ( $is_installed ) :
			?>
			<button
				type="button"
				class="button button-primary fc-addons__item-action--activate"
				data-action="activate"
				data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
			><?php echo esc_html( __( 'Activate plugin', 'fluid-checkout' ) ); ?></button>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		else :
			$purchase_url   = isset( $value[ 'purchase_url' ] ) ? (string) $value[ 'purchase_url' ] : '';
			$purchase_label = isset( $value[ 'purchase_label' ] ) ? (string) $value[ 'purchase_label' ] : '';

			// Maybe build the purchase label from a price using the centralized helpers
			if ( '' === $purchase_label && ! empty( $value[ 'purchase_price' ] ) ) {
				$purchase_price = (string) $value[ 'purchase_price' ];
				if ( false !== strpos( $plugin_file, 'fluid-checkout-pro/' ) ) {
					$purchase_label = FluidCheckout_Admin::instance()->get_pro_upgrade_button_label( $purchase_price );
				} else {
					$purchase_label = FluidCheckout_Admin::instance()->get_addon_purchase_button_label( $purchase_price );
				}
			}

			if ( '' !== $purchase_url && '' !== $purchase_label ) :
				?>
				<a href="<?php echo esc_url( $purchase_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post( $purchase_label ); ?></a>
				<?php
			endif;
		endif;

		$html = (string) ob_get_clean();

		/**
		 * Filter settings promo card action HTML.
		 *
		 * @param string $html        Default actions HTML.
		 * @param array  $value       Promo field args.
		 * @param string $action_type activate|purchase (or custom from extensions).
		 * @param string $plugin_file Plugin basename.
		 */
		return (string) apply_filters( 'fc_settings_promo_actions_html', $html, $value, $action_type, $plugin_file );
	}

}

FluidCheckout_Admin_SettingType_Promo::instance();

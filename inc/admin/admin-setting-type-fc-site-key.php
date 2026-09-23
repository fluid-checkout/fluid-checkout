<?php
defined( 'ABSPATH' ) || exit;

/**
 * Placeholder Site key card for the Dashboard tab of the Fluid Checkout settings page.
 * This Lite placeholder is independent of the working site key UI provided by Fluid Checkout PRO.
 */
class FluidCheckout_Admin_SettingType_SiteKey extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_site_key', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Whether the Lite Site key placeholder should be hidden.
	 * Hidden when PRO or any catalog add-on is activated, since those provide the working site key UI.
	 */
	private function should_hide_placeholder() {
		// Bail early when PRO is activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return true; }

		// Maybe hide when any catalog add-on plugin is activated
		if ( class_exists( 'FluidCheckout_Admin_SettingType_Addons' ) ) {
			foreach ( FluidCheckout_Admin_SettingType_Addons::instance()->get_addons_catalog() as $addon ) {
				// Skip items without a plugin file
				if ( empty( $addon[ 'plugin_file' ] ) ) { continue; }

				if ( FluidCheckout::instance()->is_plugin_activated( $addon[ 'plugin_file' ] ) ) {
					return true;
				}
			}
		}

		return false;
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Bail if PRO or any add-on is activated
		if ( $this->should_hide_placeholder() ) { return; }

		$get_site_key_url = 'https://fluidcheckout.com/account/sites/?mtm_campaign=site-key&mtm_kwd=get-site-key&mtm_source=lite-plugin';
		$learn_more_url = 'https://fluidcheckout.com/docs/site-keys/?mtm_campaign=site-key&mtm_kwd=learn-more&mtm_source=lite-plugin';
		$promo_html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'site-key' );
		?>
		<div class="fc-settings-card fc-settings-card--site-key-placeholder">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'Site key', 'fluid-checkout' ) ); ?></h3>
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
			</div>

			<div class="fc-settings-card__inner">
				<p><?php echo wp_kses_post( __( 'You can use Fluid Checkout Lite for free. For advanced functionality and priority support, Fluid Checkout PRO and/or add-ons are required.', 'fluid-checkout' ) ); ?></p>
				<p><?php echo wp_kses_post( __( 'If you own valid <em>license keys</em> for Fluid Checkout PRO and other add-ons, you can use a single <em>site key</em> to install and activate add-ons as long as you have at least one of our premium plugins installed. You can find your site key in your account on our website.', 'fluid-checkout' ) ); ?></p>
			</div>

			<div class="fc-settings-card__footer">
				<a class="fc-settings-button fc-settings-button--primary" href="<?php echo esc_url( $get_site_key_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Get my site key', 'fluid-checkout' ) ); ?></a>
				<a class="fc-settings-button" href="<?php echo esc_url( $learn_more_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Learn more', 'fluid-checkout' ) ); ?></a>
			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_SiteKey::instance();

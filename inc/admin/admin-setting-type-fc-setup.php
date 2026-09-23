<?php
defined( 'ABSPATH' ) || exit;

/**
 * Setup and documentation card for the Dashboard tab of the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Setup extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_setup', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();
		?>
		<div class="fc-settings-card fc-settings-card--setup">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'Getting started', 'fluid-checkout' ) ); ?></h3>
			</div>

			<div class="fc-settings-card__inner">
				<p><?php echo wp_kses_post( __( '<strong>Great! Your checkout page is now running on Fluid Checkout.</strong>', 'fluid-checkout' ) . ' ' . __( 'Here are a few resources for you to get started:', 'fluid-checkout' ) ); ?></p>

				<div class="fc-dashboard-docs">
					<ul>
						<?php // translators: %s: Documentation link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'Read the installation guide <a href="%s" target="_blank">Getting stated with Fluid Checkout</a>.', 'fluid-checkout' ), 'https://fluidcheckout.com/docs/getting-started-fluid-checkout/' ) ); ?></li>
						<?php // translators: %s: Checkout options link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'Setup layout and design on the <a href="%s">checkout options</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'checkout' ) ) ) ); ?></li>
						<?php // translators: %s: Integrations link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'Check if there are any <a href="%s">integration options</a> available for other plugins you have installed.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'integrations' ) ) ) ); ?></li>
						<?php // translators: %s: Tools settings link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'Help us improve compatibility and measure impact. <a href="%s">Enable site environment reports</a> from the tools settings.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'tools' ) ) ) ); ?></li>
						<?php // translators: %s: Documentation link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'Visit <a href="%s" target="_blank">our documentation</a> for more information about Fluid Checkout features.', 'fluid-checkout' ), 'https://fluidcheckout.com/docs/' ) ); ?></li>
						<?php // translators: %s: Support link. ?>
						<li><?php echo wp_kses_post( sprintf( __( 'If you ever need help, <a href="%s" target="_blank">open a support ticket</a> on our official support channel.', 'fluid-checkout' ), 'https://fluidcheckout.com/support/' ) ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Setup::instance();

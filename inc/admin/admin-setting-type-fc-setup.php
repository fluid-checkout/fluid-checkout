<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
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
		add_action( 'woocommerce_admin_field_fc_setup', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		?>

		<tr valign="top" class="fc-dashboard-section__row fc-dashboard-section--docs">

			<td colspan="2" class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
			
				<img class="fc-dashboard__logo" src="<?php echo esc_url( FluidCheckout::$directory_url . 'images/admin/fluid-checkout-logo.png' ); ?>" alt="<?php echo esc_attr( __( 'Fluid Checkout PRO', 'fluid-checkout' ) ); ?>">
				<h3 class="fc-dashboard-section-title"><?php echo esc_html( __( 'Setup & Documentation', 'fluid-checkout' ) ); ?></h3>
				<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( __( 'Great! Your checkout page is now running on Fluid Checkout.', 'fluid-checkout' ) ); ?></p>
				
				<p><?php echo wp_kses_post( __( 'Here are a few resources for you to get started:', 'fluid-checkout' ) ); ?></p>

				<div class="fc-dashboard-docs">
					<ul>
						<li><?php echo wp_kses_post( sprintf( __( 'Read the installation guide <a href="%s" target="_blank">Getting stated with Fluid Checkout</a>.', 'fluid-checkout' ), 'https://fluidcheckout.com/docs/getting-started-fluid-checkout/' ) ); ?></li>
						<li><?php echo wp_kses_post( sprintf( __( 'Setup layout and design on the <a href="%s">checkout options</a>.', 'fluid-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=fc_checkout&section=checkout' ) ) ); ?></li>
						<li><?php echo wp_kses_post( sprintf( __( 'Check if there are any <a href="%s">integration options</a> available for other plugins you have installed.', 'fluid-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=fc_checkout&section=integrations' ) ) ); ?></li>
						<li><?php echo wp_kses_post( sprintf( __( 'Visit <a href="%s" target="_blank">our documentation</a> for more information about Fluid Checkout features.', 'fluid-checkout' ), 'https://fluidcheckout.com/docs/' ) ); ?></li>
						<li><?php echo wp_kses_post( sprintf( __( 'If you ever need help, <a href="%s" target="_blank">open a support ticket</a> on our official support channel.', 'fluid-checkout' ), 'https://fluidcheckout.com/support/' ) ); ?></li>
					</ul>
				</div>

			</td>

		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Setup::instance();

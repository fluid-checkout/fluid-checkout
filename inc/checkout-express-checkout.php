<?php
defined( 'ABSPATH' ) || exit;

/**
 * Add customizations of the checkout page for Express Checkout section.
 */
class FluidCheckout_ExpressCheckout extends FluidCheckout {

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
		// Express checkout
		add_action( 'fc_checkout_before_steps', array( $this, 'maybe_output_express_checkout_section' ), 10 );
	}



	/**
	 * Output the express checkout section.
	 */
	public function maybe_output_express_checkout_section() {
		if ( 'yes' !== get_option( 'fc_enable_checkout_express_checkout', 'yes' ) || ! has_action( 'fc_checkout_express_checkout' ) ) { return; }

		$express_checkout_section_title = apply_filters( 'fc_checkout_express_checkout_section_title', __( 'Express checkout', 'fluid-checkout' ) );
		?>
		<section class="fc-express-checkout" aria-labelledby="fc-express-checkout__title">
			<div class="fc-express-checkout__inner">
				<h2 id="fc-express-checkout__title" class="fc-express-checkout__title"><?php echo esc_html( $express_checkout_section_title ); ?></h2>
				<?php do_action( 'fc_checkout_express_checkout' ); ?>
			</div>
			
			<div class="fc-express-checkout__separator">
				<span class="fc-express-checkout__separator-text"><?php echo esc_html( apply_filters( 'fc_checkout_login_separator_text', _x( 'Or', 'Separator label for the express checkout section', 'fluid-checkout' ) ) ); ?></span>
			</div>
		</section>
		<?php
	}
	
}

FluidCheckout_ExpressCheckout::instance();

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: BeTheme (by Muffin Group).
 */
class FluidCheckout_ThemeCompat_BeTheme extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Checkout template hooks
		$this->checkout_template_hooks();

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Ensure header cart updates when products are added via AJAX from checkout order summary
		add_filter('woocommerce_update_order_review_fragments', 'woocommerce_header_add_to_cart_fragment');

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Dequeue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts' ), 100 );

		// Remove redundant theme elements
		remove_action( 'woocommerce_review_order_after_submit', 'mfn_return_cart_link', 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Checkout steps
		add_action( 'the_content', array( $this, 'maybe_output_betheme_checkout_steps_section' ), 5 );
	}



	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add opening tags for inner container from the theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="section_wrapper">
			<div class="the_content_wrapper">
			<?php
	}

	/**
	 * Add closing tags for inner container from the theme.
	 */
	public function add_inner_container_closing_tags() {
			?>
			</div>
		</div>
		<?php
	}



	/**
	 * Dequeue theme scripts unnecessary on checkout page and that interfere with Fluid Checkout scripts.
	 */
	public function maybe_dequeue_scripts() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		wp_dequeue_script( 'mfn-woojs' );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' content_wrapper';
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if theme functions isn't available
		if ( ! function_exists( 'mfn_layout_ID' ) || ! function_exists( 'mfn_opts_get' ) ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$layout_id = get_post_meta( get_the_ID(), 'mfn-post-custom-layout', true );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $layout_id && ! get_post_meta( $layout_id, 'mfn-post-sticky-header', true ) && ! mfn_opts_get( 'sticky-header' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial": 1240, "breakpointFinal": 100000, "selector": "#Top_bar.is-sticky" } }';

		return $attributes;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Betheme', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_betheme_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from Betheme on the checkout, cart and order received pages.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_betheme_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_betheme_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_betheme_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output the checkout steps section from the Woodmart theme.
	 */
	public function maybe_output_betheme_checkout_steps_section( $content ) {

		// Bail when Betheme section output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_betheme_output_checkout_steps_section' ) ) { return $content; }

		// Bail if Betheme checkout steps function isn't available
		if ( ! function_exists( 'mfn_carts_page_before' ) ) { return $content; }

		ob_start();
		mfn_carts_page_before();
		$checkout_steps = ob_get_clean();

		// Append theme's checkout steps to page content
		$content = wp_kses_post( $checkout_steps ) . $content;

		return $content;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function isn't available
		if ( ! function_exists( 'mfn_opts_get' ) ) { return; }

		// Get alpha value for theme's field backround color
		$background_alpha = mfn_opts_get( 'form-transparent', 100 );
		$background_alpha = str_replace( ',', '.', ( $background_alpha / 100 ) );

		// Get theme's colors
		$field_background_color_focus = esc_attr( mfn_opts_get( 'background-form-focus', '#E9F5FC' ), $background_alpha );
		$field_text_color_focus = esc_attr( mfn_opts_get( 'color-form-focus', '#0089F7' ) );
		$field_border_color = esc_attr( mfn_opts_get( 'border-form', '#EBEBEB' ) );
		$field_border_color_focus = esc_attr( mfn_opts_get( 'border-form-focus', '#D5E5EE' ) );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height'                                     => '45px',
				'--fluidcheckout--field--padding-left'                               => '10px',
				'--fluidcheckout--field--box-shadow'                                 => 'inset 0 0 2px 2px rgba( 0, 0, 0, .02 )',
				'--fluidcheckout--field--border-color'                               => $field_border_color,
				'--fluidcheckout--field--background-color--focus'                    => $field_background_color_focus,
				'--fluidcheckout--field--background-color--accent'                   => $field_text_color_focus,

				// Custom theme variables
				'--fluidcheckout--betheme--form-field--background-color--focus'      => $field_background_color_focus,
				'--fluidcheckout--betheme--form-field--text-color--focus'            => $field_text_color_focus,
				'--fluidcheckout--betheme--form-field--border-color--focus'          => $field_border_color_focus,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_BeTheme::instance();

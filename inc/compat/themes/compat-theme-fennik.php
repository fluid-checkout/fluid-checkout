<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Fennik (by LA Studio).
 */
class FluidCheckout_ThemeCompat_Fennik extends FluidCheckout {

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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Checkout template hooks
		$this->checkout_template_hooks();
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

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Quantity fields
		remove_action( 'woocommerce_after_quantity_input_field', 'fennik_wc_add_qty_control_plus', 10 );
		remove_action( 'woocommerce_before_quantity_input_field', 'fennik_wc_add_qty_control_minus', 10 );

		// Order review heading
		remove_action( 'woocommerce_checkout_order_review', 'fennik_add_custom_heading_to_checkout_order_review', 0 );

		// Distraction free header and footer
		$this->distraction_free_hooks();
	}

	/**
	 * Add or remove hooks when using distraction free header and footer.
	 */
	public function distraction_free_hooks() {
		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Breadcrumbs
		add_action( 'fc_checkout_before_main_section', array( $this, 'maybe_output_fennik_breadcrumbs_section' ), 20 );
	}

	/**
	 * Add or remove checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_option' ) ) { return; }

		// Get container option from the theme
		$body_boxed = fennik_get_option( 'body_boxed', 'no' );

		// Add container class or inner containers from the theme
		if ( 'yes' !== $body_boxed ) {
			// Container class
			add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
		}
		else {
			// Theme's inner containers
			add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
			add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
		}
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Add opening tags for inner container from the theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div id="content-wrap">
			<div class="container">
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
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-fennik-image-lazy-load', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/fennik/lazy-load' ), array( 'jquery', 'fennik-theme' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-fennik-image-lazy-load', 'window.addEventListener("load",function(){FennikLazyLoad.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Bail if lazy load is not enabled in the theme
		if ( ! fennik_get_option( 'activate_lazyload' ) ) { return; }

		// Scripts
		// Lazy load
		wp_enqueue_script( 'fc-compat-fennik-image-lazy-load' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_option' ) ) { return; }
	
		$this->enqueue_assets();
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_theme_option_by_context' ) ) { return $attributes; }

		$is_sticky = fennik_get_theme_option_by_context( 'header_sticky', 'no' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#lastudio-header-builder.is-sticky .lahbhinner';

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
				'title' => __( 'Theme Fennik', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_fennik_options',
			),

			array(
				'title'           => __( 'Breadcrumbs', 'fluid-checkout' ),
				'desc'            => __( 'Output the breadcrumbs section from the Fennik theme when using Fluid Checkout header and footer.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_fennik_output_breadcrumbs_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_fennik_output_breadcrumbs_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_fennik_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output the breadcrumbs section from the Fennik theme.
	 */
	public function maybe_output_fennik_breadcrumbs_section() {
		// Bail if Fennik section output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_fennik_output_breadcrumbs_section' ) ) { return; }

		// Bail if theme functions are not available
		if ( ! function_exists( 'fennik_get_post_meta' ) || ! function_exists( 'fennik_get_option' ) || ! function_exists( 'fennik_get_option' ) || ! function_exists( 'fennik_title' ) || ! function_exists( 'fennik_get_schema_markup' ) || ! function_exists( 'fennik_breadcrumb_trail' ) ) { return; }

		// Global theme settings for page title and breadcrumbs
		$show_page_title = apply_filters( 'fennik/filter/show_page_title', true );
		$show_breadcrumbs = apply_filters( 'fennik/filter/show_breadcrumbs', true );

		// Current page settings for page title and breadcrumbs
		$hide_breadcrumbs = fennik_get_post_meta( get_the_ID(), 'hide_breadcrumb' );
		$hide_page_title = fennik_get_post_meta( get_the_ID(), 'hide_page_title' );

		// Change global settings if page has custom settings
		if ( $hide_breadcrumbs == 'yes' ){
			$show_breadcrumbs = false;
		}
		if ( $hide_page_title == 'yes' ){
			$show_page_title = false;
		}

		// Custom text from the theme
		$enable_custom_text = fennik_get_theme_option_by_context( 'enable_page_title_subtext', 'no' );
		$custom_text = fennik_get_theme_option_by_context( 'page_title_custom_subtext', '' );

		// HTML tag for the title
		$title_tag = fennik_get_option( 'page_title_bar_heading_tag', 'h1' );

		// Bail if both page title and breadcrumbs are disabled
		if ( ! $show_page_title && ! $show_breadcrumbs ) { return; }
		?>
		<div id="section_page_header" class="section-page-header">
			<div class="page-header-inner">
				<?php
				if ( $show_page_title ){
					printf( '<%1$s class="page-title" %3$s>%2$s</%1$s>', esc_attr( $title_tag ), fennik_title(), fennik_get_schema_markup( 'headline' ) );
				}
				if ( $enable_custom_text == 'yes' && ! empty( $custom_text ) ){
					printf( '<div class="site-breadcrumbs use-custom-text">%s</div>', esc_html( $custom_text ) );
				}
				else {
					if ( $show_breadcrumbs ){
						fennik_breadcrumb_trail();
					}
				}
				?>
			</div>
		</div>
		<?php
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--border-color' => 'var(--theme-border-color)',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => 'var(--theme-secondary-color)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Fennik::instance();

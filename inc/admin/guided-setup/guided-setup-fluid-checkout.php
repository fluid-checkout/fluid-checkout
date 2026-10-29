<?php
defined( 'ABSPATH' ) || exit;

/**
 * Fluid Checkout guided setup admin page (placeholder smoke wizard).
 */
class FluidCheckout_Admin_GuidedSetup_FluidCheckout extends FluidCheckout_Admin_GuidedSetup {

	/**
	 * Hidden admin page slug.
	 *
	 * @var string
	 */
	public $page_slug = 'fc-guided-setup-fluid-checkout';



	/**
	 * Get guided setup configuration values.
	 *
	 * @return  array
	 */
	protected function get_config() {
		return array(
			'guided_setup_id'               => 'fc_lite_checkout',

			'admin_action_param'            => 'fc_action',
			'admin_action_hook_prefix'      => 'fc_admin_action_',
			'ajax_step_action_prefix'       => 'fc_process_guided_setup_step_',
			'ajax_sub_step_action'          => 'fc_save_guided_setup_sub_step_progress',

			'nonce_param'                   => 'fc_guided_setup_token',
			'ajax_nonce_action'             => 'fc_guided_setup',
			'skip_nonce_action'             => 'fc_skip_guided_setup',
			'finish_nonce_action'           => 'fc_finish_guided_setup',
			'start_over_nonce_action'       => 'fc_start_over_guided_setup',
			'capability'                    => 'manage_woocommerce',

			'body_class'                    => 'fc-guided-setup',
			'script_handle'                 => 'fc-guided-setup',
			'style_handle'                  => 'fc-admin-guided-setup',

			'js_settings_filter'            => 'fc_js_settings_admin',
			'script_init_settings_key'      => 'guidedSetup',
		);
	}



	/**
	 * Get the guided setup admin page title.
	 *
	 * @return  string
	 */
	public function get_page_title() {
		return __( 'Fluid Checkout Guided Setup', 'fluid-checkout' );
	}



	/**
	 * Get the guided setup page heading title.
	 *
	 * @return  string
	 */
	public function get_guided_setup_title() {
		return __( 'Fluid Checkout', 'fluid-checkout' );
	}



	/**
	 * Get step definitions for the guided setup.
	 *
	 * @return  array
	 */
	protected function get_step_definitions() {
		return array(
			'welcome'          => array(
				'label' => __( 'Welcome', 'fluid-checkout' ),
			),
			'explore_checkout' => array(
				'label' => __( 'Explore your checkout', 'fluid-checkout' ),
			),
			'wrap_up'          => array(
				'label' => __( 'All set', 'fluid-checkout' ),
			),
		);
	}



	/**
	 * Get sub-step definitions for the guided setup.
	 *
	 * @return  array
	 */
	protected function get_sub_step_definitions() {
		return array();
	}



	/**
	 * Get the plugin settings URL.
	 *
	 * @return  string
	 */
	public function get_settings_url() {
		return FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' );
	}



	/**
	 * Get the continue button label for a step.
	 *
	 * @param   string  $step_slug  Current step slug.
	 *
	 * @return  string
	 */
	public function get_proceed_to_next_step_button_label( $step_slug ) {
		$steps     = $this->get_steps();
		$step_keys = array_keys( $steps );
		$index     = array_search( $step_slug, $step_keys, true );

		// Bail if current step is the last step
		if ( false === $index || $index >= count( $step_keys ) - 1 ) {
			return __( 'Continue', 'fluid-checkout' );
		}

		$next_step_label = $steps[ $step_keys[ $index + 1 ] ];

		return sprintf(
			/* translators: %s: next guided setup step label */
			__( 'Proceed to %s', 'fluid-checkout' ),
			$next_step_label
		);
	}



	/**
	 * Whether the continue button is enabled for a step.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public function is_guided_setup_step_continue_enabled( $step_slug ) {
		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Register admin page
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 99 );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

		// Process admin actions
		add_action( 'admin_init', array( $this, 'maybe_process_admin_action' ), 10 );

		// Process guided setup actions
		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'skip_guided_setup', array( $this, 'process_skip_guided_setup' ), 10 );
		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'finish_guided_setup', array( $this, 'process_finish_guided_setup' ), 10 );
		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'start_over_guided_setup', array( $this, 'process_start_over_guided_setup' ), 10 );

		// Process AJAX actions
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_step_action_prefix' ) . 'welcome', array( $this, 'ajax_process_step_welcome' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_step_action_prefix' ) . 'explore_checkout', array( $this, 'ajax_process_step_explore_checkout' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_sub_step_action' ), array( $this, 'ajax_save_sub_step_progress' ), 10 );

		// JS settings
		add_filter( $this->get_config_value( 'js_settings_filter' ), array( $this, 'add_js_settings' ), 10 );

		// Output fcSettings on the guided setup page
		add_action( 'admin_head', array( $this, 'maybe_output_settings_inline_script' ), 5 );

		// Hide the WordPress admin footer. Priority 11 runs after core's version text at 10.
		add_filter( 'admin_footer_text', array( $this, 'clear_guided_setup_admin_footer' ), 11 );
		add_filter( 'update_footer', array( $this, 'clear_guided_setup_admin_footer' ), 11 );
	}



	/**
	 * Enqueue guided setup assets.
	 */
	public function enqueue_assets() {
		// Ensure shared library scripts are registered for the guided setup dependency chain
		if ( class_exists( 'FluidCheckout_Enqueue' ) && ! wp_script_is( 'fc-collapsible-block', 'registered' ) ) {
			FluidCheckout_Enqueue::instance()->register_assets();
		}

		// Bail if shared guided setup handle is not registered yet
		if ( ! wp_script_is( 'fc-guided-setup', 'registered' ) ) { return; }

		wp_enqueue_script( 'fc-collapsible-block' );
		wp_enqueue_script( 'fc-guided-setup' );
		wp_enqueue_style( 'fc-admin-guided-setup' );

		// Init after enqueue so the handle is registered (FluidCheckout_Admin::register_assets at priority 5).
		wp_add_inline_script( 'fc-guided-setup', 'window.addEventListener("load",function(){if(window.FCGuidedSetup&&window.fcSettingsAdmin&&fcSettingsAdmin.guidedSetup){FCGuidedSetup.init(fcSettingsAdmin.guidedSetup);}});' );
	}



	/**
	 * Maybe enqueue guided setup assets.
	 *
	 * @param   string  $hook_suffix  Current admin page hook.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		// Bail if not on admin pages
		if ( ! is_admin() ) { return; }

		// Bail if not on the guided setup page
		if ( $hook_suffix !== $this->page_hook_suffix ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Clear the WordPress admin footer on the guided setup page.
	 *
	 * @param   string  $text  Footer text.
	 *
	 * @return  string
	 */
	public function clear_guided_setup_admin_footer( $text ) {
		// Bail if not on the guided setup page
		if ( ! $this->is_guided_setup_page() ) { return $text; }

		return '';
	}



	/**
	 * Maybe output the fcSettingsAdmin inline script on the guided setup page.
	 */
	public function maybe_output_settings_inline_script() {
		// Bail if not on the guided setup page
		if ( ! $this->is_guided_setup_page() ) { return; }

		// Bail if enqueue class is not available
		if ( ! class_exists( 'FluidCheckout_Enqueue' ) ) { return; }

		FluidCheckout_Enqueue::instance()->output_settings_inline_script_admin();
	}



	/**
	 * Add guided setup script settings to the JS settings object.
	 *
	 * @param   array  $settings  JS settings.
	 *
	 * @return  array
	 */
	public function add_js_settings( $settings ) {
		// Bail if not on the guided setup page
		if ( ! $this->is_guided_setup_page() ) { return $settings; }

		$settings[ $this->get_config_value( 'script_init_settings_key' ) ] = $this->get_guided_setup_script_settings();

		return $settings;
	}



	/**
	 * AJAX handler for the welcome step.
	 */
	public function ajax_process_step_welcome() {
		// Bail if AJAX request is not valid
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$this->advance_step( 'explore_checkout' );

		wp_send_json_success();
	}



	/**
	 * AJAX handler for the explore checkout step.
	 */
	public function ajax_process_step_explore_checkout() {
		// Bail if AJAX request is not valid
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$this->advance_step( 'wrap_up' );

		wp_send_json_success();
	}



	/**
	 * Output the guided setup page markup.
	 */
	public function output_guided_setup() {
		// Bail if user does not have permission
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'fluid-checkout' ) );
		}

		// Initialize guided setup state for template output
		$this->steps              = $this->get_steps();
		$this->current_step       = $this->get_current_step();
		$this->current_step_index = $this->get_current_step_index( $this->current_step );

		$max_reached_position = $this->get_max_reached_step_index();
		$checkout_page_url    = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
		?>
		<div class="wrap fc-wrap fc-guided-setup">

			<header class="fc-setup-header">
				<span class="fc-setup-header__badge"><?php echo esc_html__( 'Guided setup', 'fluid-checkout' ); ?></span>
				<h1 class="fc-setup-header__title"><?php echo esc_html( $this->get_guided_setup_title() ); ?></h1>
			</header>

			<div class="fc-setup-steps-wrapper">

				<ul class="fc-setup-steps">

					<?php
					$index_step = 0;
					foreach ( $this->steps as $step_slug => $step_name ) :
						$is_current             = $index_step === $this->current_step_index;
						$is_done                = $index_step <= $max_reached_position && $index_step !== $this->current_step_index;
						$is_reachable           = $index_step <= $max_reached_position;
						$is_current_next_reached = $is_current && $index_step < $max_reached_position;
						$is_done_next_reached   = $is_done && $index_step < $max_reached_position;
						?>
						<li class="fc-setup-step <?php echo $is_done ? 'fc-setup-step--done' : ''; ?> <?php echo $is_done_next_reached ? 'fc-setup-step--done-next-reached' : ''; ?> <?php echo $is_current ? 'fc-setup-step--current' : ''; ?> <?php echo $is_current_next_reached ? 'fc-setup-step--current-next-reached' : ''; ?>" data-step-slug="<?php echo esc_attr( $step_slug ); ?>" data-step-label="<?php echo esc_attr( $step_name ); ?>">

							<span class="fc-setup-step__name">

								<?php if ( $is_reachable ) : ?>
									<a href="<?php echo esc_url( $this->get_guided_setup_step_url( $step_slug ) ); ?>" data-step-nav="<?php echo esc_attr( $step_slug ); ?>"><?php echo esc_html( $step_name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $step_name ); ?>
								<?php endif; ?>

							</span>

							<?php if ( $is_reachable ) : ?>
								<a
									href="<?php echo esc_url( $this->get_guided_setup_step_url( $step_slug ) ); ?>"
									class="fc-setup-step__index"
									data-step-nav="<?php echo esc_attr( $step_slug ); ?>"
									aria-label="<?php
									/* translators: %1$d: step number, %2$s: step name */
									echo esc_attr( sprintf( __( 'Go to step %1$d: %2$s', 'fluid-checkout' ), $index_step + 1, $step_name ) );
									?>"
								><?php echo esc_html( (string) ( $index_step + 1 ) ); ?></a>
							<?php else : ?>
								<span class="fc-setup-step__index" aria-hidden="true"><?php echo esc_html( (string) ( $index_step + 1 ) ); ?></span>
							<?php endif; ?>

						</li>
						<?php
						$index_step++;
					endforeach;
					?>

				</ul>

			</div>

			<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'welcome' ) ); ?>" data-fc-setup-step="welcome">

				<div class="fc-setup-card__inner">

					<form>
						<h2><?php echo esc_html__( 'Welcome', 'fluid-checkout' ); ?></h2>
						<p class="fc-setup-intro"><?php echo esc_html__( 'This guided setup helps you get familiar with Fluid Checkout. The steps below are placeholders for now.', 'fluid-checkout' ); ?></p>
						<p><?php echo esc_html__( 'Continue to the next step when you are ready.', 'fluid-checkout' ); ?></p>
					</form>

				</div>

				<div class="fc-setup-card__footer">
					<div class="fc-setup-step-actions">
						<?php $this->output_step_actions_start( 'welcome' ); ?>
						<div class="fc-setup-submit">
							<a href="#" class="button button-primary<?php echo $this->is_guided_setup_step_continue_enabled( 'welcome' ) ? '' : ' fc-setup-disabled'; ?>" data-step="welcome"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html( $this->get_proceed_to_next_step_button_label( 'welcome' ) ); ?></span></a>
						</div>
					</div>
				</div>

			</div>

			<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'explore_checkout' ) ); ?>" data-fc-setup-step="explore_checkout">

				<div class="fc-setup-card__inner">

					<form>
						<h2><?php echo esc_html__( 'Explore your checkout', 'fluid-checkout' ); ?></h2>
						<p class="fc-setup-intro"><?php echo esc_html__( 'Open your store checkout to see Fluid Checkout in action.', 'fluid-checkout' ); ?></p>
						<?php if ( ! empty( $checkout_page_url ) ) : ?>
							<p><a class="button button-secondary" href="<?php echo esc_url( $checkout_page_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'View checkout page', 'fluid-checkout' ); ?></a></p>
						<?php endif; ?>
					</form>

				</div>

				<div class="fc-setup-card__footer">
					<div class="fc-setup-step-actions">
						<?php $this->output_step_actions_start( 'explore_checkout' ); ?>
						<div class="fc-setup-submit">
							<a href="#" class="button button-primary<?php echo $this->is_guided_setup_step_continue_enabled( 'explore_checkout' ) ? '' : ' fc-setup-disabled'; ?>" data-step="explore_checkout"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html( $this->get_proceed_to_next_step_button_label( 'explore_checkout' ) ); ?></span></a>
						</div>
					</div>
				</div>

			</div>

			<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'wrap_up' ) ); ?>" data-fc-setup-step="wrap_up">

				<div class="fc-setup-card__inner">

					<form>
						<h2><?php echo esc_html__( 'All set', 'fluid-checkout' ); ?></h2>
						<p class="fc-setup-intro"><?php echo esc_html__( 'You can return to Fluid Checkout settings to continue configuring your store.', 'fluid-checkout' ); ?></p>
						<ul class="fc-setup-list">
							<li><?php echo esc_html__( 'Review checkout and cart settings', 'fluid-checkout' ); ?></li>
							<li><?php echo esc_html__( 'Explore add-ons from the dashboard', 'fluid-checkout' ); ?></li>
						</ul>
					</form>

				</div>

				<div class="fc-setup-card__footer">
					<div class="fc-setup-step-actions">
						<?php $this->output_step_actions_start( 'wrap_up' ); ?>
						<div class="fc-setup-submit">
							<a href="<?php echo esc_url( $this->get_finish_url() ); ?>" class="button button-primary"><?php echo esc_html__( 'Finish & go to settings', 'fluid-checkout' ); ?></a>
						</div>
					</div>
				</div>

			</div>

			<?php $this->output_image_modal(); ?>

			<?php wp_nonce_field( $this->get_config_value( 'ajax_nonce_action' ), $this->get_config_value( 'nonce_param' ), false ); ?>

		</div>
		<?php
	}

}

FluidCheckout_Admin_GuidedSetup_FluidCheckout::instance();

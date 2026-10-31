<?php
defined( 'ABSPATH' ) || exit;

/**
 * Fluid Checkout checkout guided setup wizard (Phase 1 shell).
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
	 * @return array
	 */
	protected function get_config() {
		return array(
			'guided_setup_id'                  => 'fc_checkout',

			'admin_action_param'               => 'fc_action',
			'admin_action_hook_prefix'         => 'fc_admin_action_',
			'ajax_step_action_prefix'          => 'fc_process_guided_setup_step_',
			'ajax_sub_step_action'             => 'fc_save_guided_setup_sub_step_progress',
			'ajax_save_answers_action'         => 'fc_save_guided_setup_checkout_answers',

			'activation_redirect_transient'    => 'fc_lite_guided_setup_activation_redirect',

			'nonce_param'                      => 'fc_guided_setup_token',
			'ajax_nonce_action'                => 'fc_guided_setup',
			'skip_nonce_action'                => 'fc_skip_guided_setup',
			'finish_nonce_action'              => 'fc_finish_guided_setup',
			'start_over_nonce_action'          => 'fc_start_over_guided_setup',
			'capability'                       => 'manage_woocommerce',

			'body_class'                       => 'fc-guided-setup',
			'content_layout'                   => 'narrow',
			'script_handle'                    => 'fc-guided-setup-fluid-checkout',
			'base_script_handle'               => 'fc-guided-setup',
			'style_handle'                     => 'fc-admin-guided-setup',

			'js_settings_filter'               => 'fc_js_settings_admin',
			'script_init_settings_key'         => 'guidedSetup',
		);
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 99 );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

		add_action( 'admin_init', array( $this, 'maybe_process_admin_action' ), 10 );
		add_action( 'admin_init', array( $this, 'maybe_redirect_after_plugin_activation' ), 20 );

		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'skip_guided_setup', array( $this, 'process_skip_guided_setup' ), 10 );
		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'finish_guided_setup', array( $this, 'process_finish_guided_setup' ), 10 );
		add_action( $this->get_config_value( 'admin_action_hook_prefix' ) . 'start_over_guided_setup', array( $this, 'process_start_over_guided_setup' ), 10 );

		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_step_action_prefix' ) . 'about_store', array( $this, 'ajax_process_step_about_store' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_step_action_prefix' ) . 'product_types', array( $this, 'ajax_process_step_product_types' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_step_action_prefix' ) . 'recommended_setup', array( $this, 'ajax_process_step_recommended_setup' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_sub_step_action' ), array( $this, 'ajax_save_sub_step_progress' ), 10 );
		add_action( 'wp_ajax_' . $this->get_config_value( 'ajax_save_answers_action' ), array( $this, 'ajax_save_checkout_answers' ), 10 );

		add_filter( $this->get_config_value( 'js_settings_filter' ), array( $this, 'add_js_settings' ), 10 );

		add_action( 'admin_head', array( $this, 'maybe_output_settings_inline_script' ), 5 );

		add_filter( 'admin_footer_text', array( $this, 'clear_guided_setup_admin_footer' ), 11 );
		add_filter( 'update_footer', array( $this, 'clear_guided_setup_admin_footer' ), 11 );

		add_filter( 'fc_telemetry_guided_setup_progress', array( $this, 'sanitize_guided_setup_progress_for_telemetry' ), 10, 2 );
	}



	/**
	 * Get the guided setup admin page title.
	 *
	 * @return string
	 */
	public function get_page_title() {
		return __( 'Fluid Checkout Guided Setup', 'fluid-checkout' );
	}



	/**
	 * Get the guided setup page heading title.
	 *
	 * @return string
	 */
	public function get_guided_setup_title() {
		return __( 'Fluid Checkout', 'fluid-checkout' );
	}



	/**
	 * Guided setup URL for the first incomplete step, or recommended_setup when answers are ready.
	 *
	 * @return string
	 */
	public function get_guided_setup_resume_url() {
		$answers = $this->get_stored_answers();
		$step    = FluidCheckout_Guided_Setup_Checkout_Data::get_resume_step_slug( $answers );

		return $this->get_guided_setup_step_url( $step );
	}



	/**
	 * Clear completion and ensure the resume step is reachable.
	 *
	 * Used when an extension (e.g. PRO) re-opens the wizard after activation.
	 */
	public function prepare_progress_for_reactivation() {
		$answers     = $this->get_stored_answers();
		$resume_step = FluidCheckout_Guided_Setup_Checkout_Data::get_resume_step_slug( $answers );

		self::set_progress_for_slug(
			$this->get_guided_setup_id(),
			array(
				'completed_at' => '',
			)
		);

		// Move max-reached forward when needed so the resume step is allowed by the URL guard
		$this->advance_step( $resume_step );
	}



	/**
	 * Badge label beside the admin header logo on the guided setup screen.
	 *
	 * @return string
	 */
	protected function get_guided_setup_header_badge_label() {
		return __( 'Guided setup', 'fluid-checkout' );
	}



	/**
	 * Get step definitions for the guided setup.
	 *
	 * @return array
	 */
	protected function get_step_definitions() {
		return array(
			'about_store'        => array(
				'label' => __( 'About your store', 'fluid-checkout' ),
			),
			'product_types'      => array(
				'label' => __( 'Products & delivery', 'fluid-checkout' ),
			),
			'recommended_setup'  => array(
				'label' => __( 'Recommended setup', 'fluid-checkout' ),
			),
			'wrap_up'            => array(
				'label' => __( 'All set', 'fluid-checkout' ),
			),
		);
	}



	/**
	 * Get sub-step definitions for the guided setup.
	 *
	 * @return array
	 */
	protected function get_sub_step_definitions() {
		return array();
	}



	/**
	 * Get the plugin settings URL.
	 *
	 * @return string
	 */
	public function get_settings_url() {
		return FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' );
	}



	/**
	 * Maybe enqueue guided setup assets on the wizard admin screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( $hook_suffix !== $this->page_hook_suffix ) {
			return;
		}

		$this->enqueue_assets();
	}



	/**
	 * Enqueue guided setup assets.
	 */
	public function enqueue_assets() {
		if ( class_exists( 'FluidCheckout_Enqueue' ) && ! wp_script_is( 'fc-collapsible-block', 'registered' ) ) {
			FluidCheckout_Enqueue::instance()->register_assets();
		}

		if ( ! wp_script_is( 'fc-guided-setup', 'registered' ) ) {
			return;
		}

		wp_enqueue_script( 'fc-collapsible-block' );
		wp_enqueue_script( 'fc-guided-setup' );
		wp_enqueue_script( 'fc-guided-setup-fluid-checkout' );
		wp_enqueue_style( 'fc-admin-guided-setup' );

		wp_add_inline_script(
			'fc-guided-setup-fluid-checkout',
			'window.addEventListener("load",function(){if(window.FCGuidedSetupFluidCheckout&&window.FCGuidedSetupFluidCheckout.init){window.FCGuidedSetupFluidCheckout.init();}});'
		);
	}



	/**
	 * Skip guided setup without clearing saved progress.
	 */
	public function process_skip_guided_setup() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'skip_nonce_action' ) ) ) {
			return;
		}

		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

		wp_safe_redirect( $this->get_settings_url() );
		exit;
	}



	/**
	 * Finish guided setup: apply recommendations, newsletter, completed_at, telemetry.
	 */
	public function process_finish_guided_setup() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'finish_nonce_action' ) ) ) {
			return;
		}

		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

		$this->maybe_apply_recommendations_on_finish();
		$this->maybe_subscribe_newsletter();
		$this->set_completed_at();
		$this->maybe_send_guided_setup_telemetry( true );

		wp_safe_redirect( $this->get_settings_url() );
		exit;
	}



	/**
	 * AJAX: about_store step.
	 */
	public function ajax_process_step_about_store() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$form_data = $this->get_form_data_from_request();
		$result    = $this->save_step_answers( 'about_store', $form_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$this->maybe_save_telemetry_opt_in( $result );
		$this->advance_step( 'product_types' );
		$this->maybe_send_guided_setup_telemetry( false );

		$this->send_step_ajax_success();
	}



	/**
	 * AJAX: product_types step.
	 */
	public function ajax_process_step_product_types() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$form_data = $this->get_form_data_from_request();
		$result    = $this->save_step_answers( 'product_types', $form_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$this->advance_step( 'recommended_setup' );
		$this->maybe_send_guided_setup_telemetry( false );

		$this->send_step_ajax_success();
	}



	/**
	 * AJAX: recommended_setup step (persist overrides, apply settings, then continue).
	 */
	public function ajax_process_step_recommended_setup() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$form_data = $this->get_form_data_from_request();
		$this->save_recommended_setup_user_overrides( $form_data );
		$this->maybe_apply_recommendations_on_finish();
		$this->advance_step( 'wrap_up' );
		$this->maybe_send_guided_setup_telemetry( false );

		$this->send_step_ajax_success();
	}



	/**
	 * AJAX: debounced partial save for recommendation-relevant answers.
	 */
	public function ajax_save_checkout_answers() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$form_data = $this->get_form_data_from_request();
		$existing  = $this->get_stored_answers();
		$answers   = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_debounced_form_data( $form_data, $existing );

		$this->persist_answers( $answers );
		$this->recompute_and_store_recommendations( $answers );

		wp_send_json_success(
			array(
				'recommendationsHtml' => $this->get_recommended_setup_settings_html(),
			)
		);
	}



	/**
	 * Add checkout-specific JS settings.
	 *
	 * @param array $settings JS settings.
	 *
	 * @return array
	 */
	public function add_js_settings( $settings ) {
		if ( ! $this->is_guided_setup_page() ) {
			return $settings;
		}

		$settings[ $this->get_config_value( 'script_init_settings_key' ) ] = $this->get_guided_setup_script_settings();

		return $settings;
	}



	/**
	 * Guided setup script settings for the checkout wizard.
	 *
	 * @return array
	 */
	public function get_guided_setup_script_settings() {
		$settings = parent::get_guided_setup_script_settings();

		$settings['ajaxSaveAnswersAction'] = $this->get_config_value( 'ajax_save_answers_action' );
		$settings['answers']               = $this->get_stored_answers();
		$settings['i18n']                    = array_merge(
			isset( $settings['i18n'] ) ? $settings['i18n'] : array(),
			array(
				'industryRequired'           => __( 'Please select what type of products or services you sell.', 'fluid-checkout' ),
				'customerAudienceRequired'   => __( 'Please select who you sell to.', 'fluid-checkout' ),
				'customerLocationRequired'   => __( 'Please select where your customers are located.', 'fluid-checkout' ),
				'emailRequired'              => __( 'Please enter a valid email address for the newsletter.', 'fluid-checkout' ),
				'productTypesRequired'       => __( 'Please select at least one product type.', 'fluid-checkout' ),
				'deliveryMethodsRequired'    => __( 'Please select at least one delivery method for physical products.', 'fluid-checkout' ),
				'showAlreadyApplied'         => __( 'Show already applied', 'fluid-checkout' ),
				'hideAlreadyApplied'         => __( 'Hide already applied', 'fluid-checkout' ),
			)
		);

		return $settings;
	}



	/**
	 * Output the guided setup page markup.
	 */
	public function output_guided_setup() {
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'fluid-checkout' ) );
		}

		$this->ensure_started_at();
		$this->steps              = $this->get_steps();
		$this->current_step       = $this->get_current_step();
		$this->current_step_index = $this->get_current_step_index( $this->current_step );

		$max_reached_position = $this->get_max_reached_step_index();
		$answers              = $this->get_stored_answers();
		?>
		<div class="wrap fc-wrap fc-guided-setup">

			<div class="fc-setup-steps-wrapper">
				<ul class="fc-setup-steps">
					<?php
					$index_step = 0;
					foreach ( $this->steps as $step_slug => $step_name ) :
						$is_current              = $index_step === $this->current_step_index;
						$is_done                 = $index_step <= $max_reached_position && $index_step !== $this->current_step_index;
						$is_reachable            = $index_step <= $max_reached_position;
						$is_current_next_reached = $is_current && $index_step < $max_reached_position;
						$is_done_next_reached    = $is_done && $index_step < $max_reached_position;
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
								<a href="<?php echo esc_url( $this->get_guided_setup_step_url( $step_slug ) ); ?>" class="fc-setup-step__index" data-step-nav="<?php echo esc_attr( $step_slug ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to step %1$d: %2$s', 'fluid-checkout' ), $index_step + 1, $step_name ) ); ?>"><?php echo esc_html( (string) ( $index_step + 1 ) ); ?></a>
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

			<?php
			$this->output_step_about_store( $answers );
			$this->output_step_product_types( $answers );
			$this->output_step_recommended_setup();
			$this->output_step_wrap_up();
			?>

			<?php $this->output_image_modal(); ?>

			<?php wp_nonce_field( $this->get_config_value( 'ajax_nonce_action' ), $this->get_config_value( 'nonce_param' ), false ); ?>

		</div>
		<?php
	}



	/**
	 * Output about_store step card.
	 *
	 * @param array $answers Stored answers.
	 */
	protected function output_step_about_store( $answers ) {
		$industries                 = FluidCheckout_Guided_Setup_Checkout_Data::get_industries();
		$selected_industry          = ! empty( $answers['business_industry'] ) ? $answers['business_industry'] : FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_industry();
		$customer_audience_opts     = FluidCheckout_Guided_Setup_Checkout_Data::get_customer_audience_options();
		$selected_customer_audience = ! empty( $answers['customer_audience'] ) ? $answers['customer_audience'] : 'dtc';
		$customer_location_opts     = FluidCheckout_Guided_Setup_Checkout_Data::get_customer_location_options();
		$selected_customer_location = ! empty( $answers['customer_location'] ) ? $answers['customer_location'] : FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_customer_location();
		$user_email                 = ! empty( $answers['email'] ) ? $answers['email'] : wp_get_current_user()->user_email;
		$newsletter_checked   = ! empty( $answers['newsletter_opt_in'] );
		$telemetry_checked    = class_exists( 'FC_Telemetry_Client' ) && FC_Telemetry_Client::is_telemetry_enabled();
		if ( ! $telemetry_checked && ! empty( $answers['telemetry_opt_in'] ) ) {
			$telemetry_checked = true;
		}
		$telemetry_hidden     = class_exists( 'FC_Telemetry_Client' ) && FC_Telemetry_Client::is_disabled_by_constant();
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'about_store' ) ); ?>" data-fc-setup-step="about_store">
			<div class="fc-setup-card__inner">
				<form data-fc-setup-autosave="1">
					<h2><?php echo esc_html__( 'Welcome to Fluid Checkout!', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'It is great to have you here with us! We will be guiding you through the setup process – your answers to a few questions will help us recommend checkout settings for your store.', 'fluid-checkout' ); ?></p>

					<h2 class="fc-setup-section-title"><?php echo esc_html__( 'About your store', 'fluid-checkout' ); ?></h2>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--radio">
						<div class="fc-settings-field__label">
							<span class="fc-settings-field__title"><?php echo esc_html__( 'What type of products or services do you sell?', 'fluid-checkout' ); ?></span>
						</div>
						<div class="fc-settings-field__control">
							<div class="fc-settings-radio-options" role="radiogroup" aria-label="<?php echo esc_attr__( 'What type of products or services do you sell?', 'fluid-checkout' ); ?>">
								<?php foreach ( $industries as $slug => $industry ) : ?>
									<label class="fc-settings-radio-option">
										<input type="radio" name="business_industry" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $selected_industry, $slug ); ?> required />
										<span class="fc-settings-radio-option__label"><?php echo esc_html( $industry['label'] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--radio">
						<div class="fc-settings-field__label">
							<span class="fc-settings-field__title"><?php echo esc_html__( 'Who do you predominantly sell to?', 'fluid-checkout' ); ?></span>
						</div>
						<div class="fc-settings-field__control">
							<div class="fc-settings-radio-options" role="radiogroup" aria-label="<?php echo esc_attr__( 'Who do you predominantly sell to?', 'fluid-checkout' ); ?>">
								<?php foreach ( $customer_audience_opts as $slug => $label ) : ?>
									<label class="fc-settings-radio-option">
										<input type="radio" name="customer_audience" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $selected_customer_audience, $slug ); ?> required />
										<span class="fc-settings-radio-option__label"><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--radio">
						<div class="fc-settings-field__label">
							<span class="fc-settings-field__title"><?php echo esc_html__( 'Where are your customers located?', 'fluid-checkout' ); ?></span>
						</div>
						<div class="fc-settings-field__control">
							<div class="fc-settings-radio-options" role="radiogroup" aria-label="<?php echo esc_attr__( 'Where are your customers located?', 'fluid-checkout' ); ?>">
								<?php foreach ( $customer_location_opts as $slug => $label ) : ?>
									<label class="fc-settings-radio-option">
										<input type="radio" name="customer_location" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $selected_customer_location, $slug ); ?> required />
										<span class="fc-settings-radio-option__label"><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<?php if ( ! $telemetry_hidden ) : ?>
						<div class="fc-settings-field fc-settings-field--inline fc-settings-field--checkbox">
							<div class="fc-settings-field__label">
								<span class="fc-settings-field__title"><?php echo esc_html__( 'Usage tracking', 'fluid-checkout' ); ?></span>
							</div>
							<div class="fc-settings-field__control">
								<fieldset>
									<legend class="screen-reader-text"><span><?php echo esc_html__( 'Usage tracking', 'fluid-checkout' ); ?></span></legend>
									<span class="fc-settings-switch">
										<input type="checkbox" class="fc-settings-toggle fc-settings-toggle--round" id="fc_setup_telemetry_opt_in" name="telemetry_opt_in" value="1" <?php checked( $telemetry_checked ); ?> />
										<label for="fc_setup_telemetry_opt_in"></label>
									</span>
									<label class="fc-settings-switch__text" for="fc_setup_telemetry_opt_in"><?php echo esc_html__( 'Help improve Fluid Checkout with optional usage tracking (no customer data).', 'fluid-checkout' ); ?></label>
									<p class="description"><?php echo esc_html__( 'You can review and change this anytime under Tools → Usage tracking.', 'fluid-checkout' ); ?></p>
								</fieldset>
							</div>
						</div>
					<?php endif; ?>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--text">
						<div class="fc-settings-field__label">
							<label for="fc_setup_newsletter_email"><?php echo esc_html__( 'Email address', 'fluid-checkout' ); ?></label>
						</div>
						<div class="fc-settings-field__control">
							<input type="email" class="regular-text" id="fc_setup_newsletter_email" name="email" value="<?php echo esc_attr( $user_email ); ?>" <?php disabled( ! $newsletter_checked ); ?> data-fc-setup-newsletter-email />
						</div>
					</div>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--checkbox fc-settings-field--control-only">
						<div class="fc-settings-field__control">
							<fieldset>
								<legend class="screen-reader-text"><span><?php echo esc_html__( 'Newsletter opt-in', 'fluid-checkout' ); ?></span></legend>
								<span class="fc-settings-switch">
									<input type="checkbox" class="fc-settings-toggle fc-settings-toggle--round" id="fc_setup_newsletter_opt_in" name="newsletter_opt_in" value="1" <?php checked( $newsletter_checked ); ?> data-fc-setup-newsletter-opt-in />
									<label for="fc_setup_newsletter_opt_in"></label>
								</span>
								<label class="fc-settings-switch__text" for="fc_setup_newsletter_opt_in"><?php echo esc_html__( 'Opt-in to receive news about new features, tips and recommendations from the Fluid Checkout team directly in your inbox.', 'fluid-checkout' ); ?></label>
							</fieldset>
						</div>
					</div>
				</form>
			</div>
			<div class="fc-setup-card__footer">
				<div class="fc-setup-step-actions">
					<?php $this->output_step_actions_start( 'about_store' ); ?>
					<div class="fc-setup-submit">
						<a href="#" class="button button-primary" data-step="about_store"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html( $this->get_proceed_to_next_step_button_label( 'about_store' ) ); ?></span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Output product_types step card.
	 *
	 * @param array $answers Stored answers.
	 */
	protected function output_step_product_types( $answers ) {
		$product_types    = FluidCheckout_Guided_Setup_Checkout_Data::get_product_types();
		$delivery_methods = FluidCheckout_Guided_Setup_Checkout_Data::get_delivery_methods();
		$selected_types   = isset( $answers['product_types'] ) ? $answers['product_types'] : array();
		$selected_delivery = isset( $answers['delivery_methods'] ) ? $answers['delivery_methods'] : array();
		$physical_on      = in_array( 'physical', $selected_types, true );
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'product_types' ) ); ?>" data-fc-setup-step="product_types">
			<div class="fc-setup-card__inner">
				<form data-fc-setup-autosave="1">
					<h2><?php echo esc_html__( 'Products & delivery', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'What do you sell, and how do you deliver physical products?', 'fluid-checkout' ); ?></p>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--checkbox">
						<div class="fc-settings-field__label">
							<span class="fc-settings-field__title"><?php echo esc_html__( 'What do you sell?', 'fluid-checkout' ); ?></span>
						</div>
						<div class="fc-settings-field__control">
							<fieldset>
								<legend class="screen-reader-text"><span><?php echo esc_html__( 'What do you sell?', 'fluid-checkout' ); ?></span></legend>
								<div class="fc-settings-radio-options" role="group" aria-label="<?php echo esc_attr__( 'What do you sell?', 'fluid-checkout' ); ?>">
									<?php foreach ( $product_types as $slug => $label ) : ?>
										<?php $input_id = 'fc_setup_product_type_' . $slug; ?>
										<label class="fc-settings-radio-option" for="<?php echo esc_attr( $input_id ); ?>">
											<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" name="product_types[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected_types, true ) ); ?> data-fc-setup-product-type />
											<span class="fc-settings-radio-option__label"><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>
						</div>
					</div>

					<div class="fc-settings-field fc-settings-field--inline fc-settings-field--checkbox" data-fc-setup-delivery-section <?php echo $physical_on ? '' : 'hidden'; ?>>
						<div class="fc-settings-field__label">
							<span class="fc-settings-field__title"><?php echo esc_html__( 'How do you handle delivery of your products?', 'fluid-checkout' ); ?></span>
						</div>
						<div class="fc-settings-field__control">
							<fieldset>
								<legend class="screen-reader-text"><span><?php echo esc_html__( 'How do you handle delivery of your products?', 'fluid-checkout' ); ?></span></legend>
								<div class="fc-settings-radio-options" role="group" aria-label="<?php echo esc_attr__( 'How do you handle delivery of your products?', 'fluid-checkout' ); ?>">
									<?php foreach ( $delivery_methods as $slug => $label ) : ?>
										<?php
										$input_id        = 'fc_setup_delivery_method_' . $slug;
										$option_disabled = ! $physical_on;
										$option_classes  = 'fc-settings-radio-option';
										if ( $option_disabled ) {
											$option_classes .= ' is-disabled';
										}
										?>
										<label class="<?php echo esc_attr( $option_classes ); ?>" for="<?php echo esc_attr( $input_id ); ?>">
											<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" name="delivery_methods[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected_delivery, true ) ); ?> <?php disabled( $option_disabled ); ?> data-fc-setup-delivery-method />
											<span class="fc-settings-radio-option__label"><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>
						</div>
					</div>
				</form>
			</div>
			<div class="fc-setup-card__footer">
				<div class="fc-setup-step-actions">
					<?php $this->output_step_actions_start( 'product_types' ); ?>
					<div class="fc-setup-submit">
						<a href="#" class="button button-primary" data-step="product_types"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html( $this->get_proceed_to_next_step_button_label( 'product_types' ) ); ?></span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Get recommended rows for the recommended_setup step (from progress cache).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_recommended_setup_settings() {
		$recommendations = $this->get_recommendations_progress();
		$rows            = isset( $recommendations['recommended'] ) && is_array( $recommendations['recommended'] ) ? $recommendations['recommended'] : array();
		$excluded_keys   = array();

		if ( class_exists( 'FluidCheckout_Guided_Setup_Recommendations' ) ) {
			$excluded_keys = FluidCheckout_Guided_Setup_Recommendations::instance()->normalize_excluded_option_keys(
				isset( $recommendations['user_overrides'] ) ? $recommendations['user_overrides'] : array()
			);
		}

		$pro_recommendations_checked = $this->is_pro_recommendations_checked_by_default();

		// Iterate rows to apply checked state from overrides and Lite/PRO context
		foreach ( $rows as $index => $row ) {
			// PRO rows are unchecked on Lite, checked when PRO guided setup can apply them
			if ( ! empty( $row['is_pro'] ) ) {
				$rows[ $index ]['checked'] = $pro_recommendations_checked;
				continue;
			}

			$option_key = isset( $row['option_key'] ) ? (string) $row['option_key'] : '';
			$checked    = '' === $option_key || ! in_array( $option_key, $excluded_keys, true );

			$rows[ $index ]['checked'] = $checked;
		}

		return $rows;
	}



	/**
	 * Whether PRO recommendation toggles start checked.
	 *
	 * Lite default: unchecked (promo only). PRO enables checked via
	 * `fc_guided_setup_pro_recommendations_checked_by_default`.
	 *
	 * @return bool
	 */
	protected function is_pro_recommendations_checked_by_default() {
		/**
		 * Filters whether PRO recommendation toggles start checked.
		 *
		 * @since 5.0.0
		 *
		 * @param bool $checked Default false on Lite.
		 */
		return (bool) apply_filters( 'fc_guided_setup_pro_recommendations_checked_by_default', false );
	}



	/**
	 * Whether already-applied recommendations are hidden behind an expand link.
	 *
	 * Lite default: show every recommendation. PRO can enable hiding via
	 * `fc_guided_setup_hide_already_applied_recommendations`.
	 *
	 * @return bool
	 */
	protected function should_hide_already_applied_recommendations() {
		/**
		 * Filters whether already-applied recommendations are hidden until expanded.
		 *
		 * @since 5.0.0
		 *
		 * @param bool $hide Default false on Lite.
		 */
		return (bool) apply_filters( 'fc_guided_setup_hide_already_applied_recommendations', false );
	}



	/**
	 * Split recommendation rows into pending and already-applied groups.
	 *
	 * @param array $settings Recommendation rows.
	 *
	 * @return array{pending:array,applied:array}
	 */
	protected function partition_recommended_setup_settings( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$pending  = array();
		$applied  = array();
		$engine   = class_exists( 'FluidCheckout_Guided_Setup_Recommendations' )
			? FluidCheckout_Guided_Setup_Recommendations::instance()
			: null;

		// Lite (and missing engine): treat every row as pending
		if ( ! $this->should_hide_already_applied_recommendations() || ! $engine ) {
			return array(
				'pending' => $settings,
				'applied' => array(),
			);
		}

		// Iterate rows and compare each recommended value to the active profile
		foreach ( $settings as $setting ) {
			if ( $engine->is_recommendation_already_applied( $setting ) ) {
				$applied[] = $setting;
				continue;
			}

			$pending[] = $setting;
		}

		return array(
			'pending' => $pending,
			'applied' => $applied,
		);
	}



	/**
	 * Output one recommendation toggle row.
	 *
	 * @param array $setting     Recommendation row.
	 * @param bool  $interactive Whether the toggle posts with the form.
	 */
	protected function output_recommended_setup_setting_row( $setting, $interactive = true ) {
		$setting         = is_array( $setting ) ? $setting : array();
		$option_key      = isset( $setting['option_key'] ) ? (string) $setting['option_key'] : ( isset( $setting['id'] ) ? (string) $setting['id'] : '' );
		$input_id        = 'fc_setup_recommendation_' . sanitize_html_class( $option_key );
		$is_pro          = ! empty( $setting['is_pro'] );
		$show_pro_badges = class_exists( 'FluidCheckout' ) && ! FluidCheckout::instance()->is_pro_activated();
		$is_disabled     = ! $interactive || ( $is_pro && $show_pro_badges );
		$option_class    = 'fc-setup-recommendation-option';
		$pro_badge_html  = ( $is_pro && $show_pro_badges ) ? $this->get_recommendation_pro_badge_html( $setting ) : '';

		if ( $is_disabled ) {
			$option_class .= ' is-disabled';
		}

		$switch_class = 'fc-settings-switch fc-setup-recommendation-option__switch';

		if ( $is_disabled ) {
			$switch_class .= ' fc-settings-switch--disabled';
		}
		?>
		<div class="<?php echo esc_attr( $option_class ); ?>">
			<span class="<?php echo esc_attr( $switch_class ); ?>">
				<input
					type="checkbox"
					class="fc-settings-toggle fc-settings-toggle--round"
					id="<?php echo esc_attr( $input_id ); ?>"
					<?php if ( $interactive && ! ( $is_pro && $show_pro_badges ) ) : ?>
						name="recommendation_toggles[]"
					<?php endif; ?>
					value="<?php echo esc_attr( $option_key ); ?>"
					<?php checked( ! empty( $setting['checked'] ) || ! $interactive ); ?>
					<?php disabled( $is_disabled ); ?>
					aria-labelledby="<?php echo esc_attr( $input_id ); ?>-label"
				/>
				<label for="<?php echo esc_attr( $input_id ); ?>"><span class="screen-reader-text"><?php echo esc_html( $setting['title'] ); ?></span></label>
			</span>
			<div class="fc-setup-recommendation-option__content">
				<span class="fc-setup-recommendation-option__title" id="<?php echo esc_attr( $input_id ); ?>-label"><?php echo esc_html( $setting['title'] ); ?></span>
				<p class="fc-setup-recommendation-option__description"><?php echo esc_html( $setting['description'] ); ?></p>
			</div>
			<div class="fc-setup-recommendation-option__badge">
				<?php if ( ! empty( $pro_badge_html ) ) : ?>
					<?php echo $pro_badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output recommendation toggles for the recommended_setup step.
	 */
	protected function output_recommended_setup_settings() {
		$settings   = $this->get_recommended_setup_settings();
		$partition  = $this->partition_recommended_setup_settings( $settings );
		$pending    = $partition['pending'];
		$applied    = $partition['applied'];
		$hide_applied = $this->should_hide_already_applied_recommendations();
		?>
		<div class="fc-setup-recommendation-options" role="group" aria-label="<?php echo esc_attr__( 'Recommended checkout settings', 'fluid-checkout' ); ?>">
			<?php if ( empty( $pending ) && $hide_applied ) : ?>
				<p class="fc-setup-recommendation-empty"><?php echo esc_html__( 'No more recommendations to apply', 'fluid-checkout' ); ?></p>
			<?php else : ?>
				<?php foreach ( $pending as $setting ) : ?>
					<?php $this->output_recommended_setup_setting_row( $setting, true ); ?>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( $hide_applied && ! empty( $applied ) ) : ?>
				<p class="fc-setup-recommendation-applied-toggle">
					<a href="#" class="button button-link" data-fc-setup-show-applied-recommendations aria-expanded="false"><?php echo esc_html__( 'Show already applied', 'fluid-checkout' ); ?></a>
				</p>
				<div class="fc-setup-recommendation-applied is-collapsed" data-fc-setup-applied-recommendations hidden>
					<?php foreach ( $applied as $setting ) : ?>
						<?php $this->output_recommended_setup_setting_row( $setting, false ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}



	/**
	 * Get the PRO promo badge HTML for one recommendation row.
	 *
	 * Uses `mtm_kwd=guided-setup-{feature_slug}` so upgrades can be attributed per feature.
	 *
	 * @param array $setting Recommendation row.
	 *
	 * @return string
	 */
	protected function get_recommendation_pro_badge_html( $setting ) {
		// Bail if the admin badge helper is unavailable
		if ( ! class_exists( 'FluidCheckout_Admin' ) ) {
			return '';
		}

		$setting      = is_array( $setting ) ? $setting : array();
		$feature_slug = isset( $setting['feature_slug'] ) ? sanitize_title( (string) $setting['feature_slug'] ) : '';

		// Fall back to a sanitized option key when the registry omits feature_slug
		if ( '' === $feature_slug ) {
			$option_key   = isset( $setting['option_key'] ) ? (string) $setting['option_key'] : '';
			$feature_slug = sanitize_title( preg_replace( '/^fc_pro_|^fc_/', '', $option_key ) );
		}

		// Bail if no feature slug could be resolved
		if ( '' === $feature_slug ) {
			$feature_slug = 'recommendations';
		}

		return FluidCheckout_Admin::instance()->get_pro_feature_badge_html(
			$feature_slug,
			null,
			'guided-setup-' . $feature_slug
		);
	}



	/**
	 * Get recommendation toggles markup for AJAX refresh.
	 *
	 * @return string
	 */
	protected function get_recommended_setup_settings_html() {
		ob_start();
		$this->output_recommended_setup_settings();

		return (string) ob_get_clean();
	}



	/**
	 * Output recommended_setup step.
	 */
	protected function output_step_recommended_setup() {
		$this->maybe_refresh_recommendations_cache();
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'recommended_setup' ) ); ?>" data-fc-setup-step="recommended_setup">
			<div class="fc-setup-card__inner">
				<form>
					<h2><?php echo esc_html__( 'Recommended setup', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'Based on what you told us, these checkout options are a good starting point. You can turn recommended options off before applying them and the general default value will be used instead.', 'fluid-checkout' ); ?></p>
					<p class="fc-setup-intro"><?php echo esc_html__( 'You can change any of these settings later in the Fluid Checkout settings.', 'fluid-checkout' ); ?></p>
					<div data-fc-setup-recommendations>
						<?php $this->output_recommended_setup_settings(); ?>
					</div>
				</form>
			</div>
			<div class="fc-setup-card__footer">
				<div class="fc-setup-step-actions">
					<?php $this->output_step_actions_start( 'recommended_setup' ); ?>
					<div class="fc-setup-submit">
						<a href="#" class="button button-primary" data-step="recommended_setup"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html__( 'Apply settings & finish setup', 'fluid-checkout' ); ?></span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Output wrap_up step card.
	 */
	protected function output_step_wrap_up() {
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'wrap_up' ) ); ?>" data-fc-setup-step="wrap_up">
			<div class="fc-setup-card__inner">
				<h2><?php echo esc_html__( 'All set', 'fluid-checkout' ); ?></h2>
				<p class="fc-setup-intro"><?php echo esc_html__( 'Your recommended setup options have been applied.', 'fluid-checkout' ) ; ?></p>
				<p class="fc-setup-intro"><?php echo esc_html__( 'Test your checkout process and return to Fluid Checkout settings to adjust any settings if needed.', 'fluid-checkout' ) . ' ' . esc_html__( 'You can use the built-in live preview in settings to see how settings changes affect your checkout before saving them.', 'fluid-checkout' ); ?></p>
				<?php
				/**
				 * Fires after the wrap-up intro copy, before the support link.
				 *
				 * @since 5.0.0
				 *
				 * @param FluidCheckout_Admin_GuidedSetup_FluidCheckout $wizard Checkout guided setup instance.
				 */
				do_action( 'fc_guided_setup_wrap_up_content', $this );
				?>
				<p class="fc-setup-intro"><?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: support page URL */
							__( 'If you need help, <a href="%s" target="_blank" rel="noopener noreferrer">contact our support team</a>.', 'fluid-checkout' ),
							esc_url( 'https://fluidcheckout.com/support/?mtm_campaign=guided-setup&mtm_kwd=support&mtm_source=lite-plugin' )
						)
					);
				?></p>
			</div>
			<div class="fc-setup-card__footer">
				<div class="fc-setup-step-actions">
					<?php $this->output_step_actions_start( 'wrap_up' ); ?>
					<div class="fc-setup-submit">
						<a href="<?php echo esc_url( $this->get_start_over_guided_setup_url() ); ?>" class="button button-secondary fc-setup-start-over" aria-label="<?php echo esc_attr__( 'Start over and return to the first guided setup step', 'fluid-checkout' ); ?>"><?php echo esc_html__( 'Start over', 'fluid-checkout' ); ?></a>
						<?php
						/**
						 * Fires in the wrap-up submit area after Start over and before Finish.
						 *
						 * @since 5.0.0
						 *
						 * @param FluidCheckout_Admin_GuidedSetup_FluidCheckout $wizard Checkout guided setup instance.
						 */
						do_action( 'fc_guided_setup_wrap_up_submit_actions', $this );
						?>
						<a href="<?php echo esc_url( $this->get_finish_url() ); ?>" class="button button-primary"><?php echo esc_html__( 'Finish & go to settings', 'fluid-checkout' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Get continue button label for a step.
	 *
	 * @param string $step_slug Step slug.
	 *
	 * @return string
	 */
	public function get_proceed_to_next_step_button_label( $step_slug ) {
		$steps     = $this->get_steps();
		$step_keys = array_keys( $steps );
		$index     = array_search( $step_slug, $step_keys, true );

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
	 * Read posted form_data array.
	 *
	 * @return array
	 */
	protected function get_form_data_from_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['form_data'] ) || ! is_array( $_POST['form_data'] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return wp_unslash( $_POST['form_data'] );
	}



	/**
	 * Get answers from progress storage.
	 *
	 * @return array
	 */
	public function get_stored_answers() {
		$guided_setup_id = $this->get_guided_setup_id();
		$progress        = self::get_progress_for_slug( $guided_setup_id );
		$stored          = isset( $progress['answers'] ) && is_array( $progress['answers'] ) ? $progress['answers'] : array();

		return FluidCheckout_Guided_Setup_Checkout_Data::get_default_answers( $stored );
	}



	/**
	 * JSON success payload after a step is processed and progress is advanced.
	 */
	protected function send_step_ajax_success() {
		wp_send_json_success( $this->get_step_ajax_success_data() );
	}



	/**
	 * Shared AJAX success data for step continue and answer autosave.
	 *
	 * @return array{currentStep:string,maxReachedStepIndex:int,recommendationsHtml:string}
	 */
	protected function get_step_ajax_success_data() {
		return array(
			'currentStep'         => $this->get_max_reached_step(),
			'maxReachedStepIndex' => $this->get_max_reached_step_index(),
			'recommendationsHtml' => $this->get_recommended_setup_settings_html(),
		);
	}



	/**
	 * Save validated step answers.
	 *
	 * @param string $step_slug Step slug.
	 * @param array  $form_data Form data.
	 *
	 * @return array|WP_Error
	 */
	protected function save_step_answers( $step_slug, $form_data ) {
		$existing = $this->get_stored_answers();
		$answers  = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_step_form_data( $step_slug, $form_data, $existing );

		if ( is_wp_error( $answers ) ) {
			return $answers;
		}

		$this->persist_answers( $answers );

		// Recompute recommendations after about_store or product_types
		if ( in_array( $step_slug, array( 'about_store', 'product_types' ), true ) ) {
			$this->recompute_and_store_recommendations( $answers );
		}

		return $answers;
	}



	/**
	 * Merge answers into progress storage.
	 *
	 * @param array $answers Answers.
	 */
	protected function persist_answers( $answers ) {
		$this->ensure_started_at();

		self::set_progress_for_slug(
			$this->get_guided_setup_id(),
			array(
				'answers' => $answers,
			)
		);
	}



	/**
	 * Get cached recommendations from progress.
	 *
	 * @return array
	 */
	protected function get_recommendations_progress() {
		$progress = self::get_progress_for_slug( $this->get_guided_setup_id() );

		return isset( $progress['recommendations'] ) && is_array( $progress['recommendations'] ) ? $progress['recommendations'] : array();
	}



	/**
	 * Recompute recommendations and store under progress.
	 *
	 * @param array $answers Wizard answers.
	 */
	protected function recompute_and_store_recommendations( $answers ) {
		// Bail if the recommendations engine is unavailable
		if ( ! class_exists( 'FluidCheckout_Guided_Setup_Recommendations' ) ) {
			return;
		}

		$engine        = FluidCheckout_Guided_Setup_Recommendations::instance();
		$engine_result = $engine->get_recommendation( $answers );
		$existing      = $this->get_recommendations_progress();
		$payload       = $engine->build_progress_recommendations( $engine_result, $existing );

		self::set_progress_for_slug(
			$this->get_guided_setup_id(),
			array(
				'recommendations' => $payload,
			)
		);
	}



	/**
	 * Refresh recommendations when the answers hash is missing or stale.
	 */
	protected function maybe_refresh_recommendations_cache() {
		$answers         = $this->get_stored_answers();
		$recommendations = $this->get_recommendations_progress();

		// Bail if the recommendations engine is unavailable
		if ( ! class_exists( 'FluidCheckout_Guided_Setup_Recommendations' ) ) {
			return;
		}

		$new_hash = FluidCheckout_Guided_Setup_Recommendations::instance()->get_answers_hash( $answers );
		$old_hash = isset( $recommendations['answers_hash'] ) ? (string) $recommendations['answers_hash'] : '';

		// Bail if the cache already matches the current answers
		if ( $new_hash === $old_hash && ! empty( $recommendations['recommended'] ) ) {
			return;
		}

		$this->recompute_and_store_recommendations( $answers );
	}



	/**
	 * Persist toggle overrides from the recommended_setup step.
	 *
	 * @param array $form_data Form data.
	 */
	protected function save_recommended_setup_user_overrides( $form_data ) {
		// Bail if the recommendations engine is unavailable
		if ( ! class_exists( 'FluidCheckout_Guided_Setup_Recommendations' ) ) {
			return;
		}

		$this->maybe_refresh_recommendations_cache();

		$engine          = FluidCheckout_Guided_Setup_Recommendations::instance();
		$recommendations = $this->get_recommendations_progress();
		$recommended     = isset( $recommendations['recommended'] ) && is_array( $recommendations['recommended'] ) ? $recommendations['recommended'] : array();

		// Only pending (not already applied) rows are posted as toggles when PRO hides applied ones
		if ( $this->should_hide_already_applied_recommendations() ) {
			$partition   = $this->partition_recommended_setup_settings( $recommended );
			$recommended = $partition['pending'];
		}

		$overrides = $engine->build_user_overrides_from_form( $recommended, $form_data );

		$recommendations['user_overrides'] = $overrides;

		self::set_progress_for_slug(
			$this->get_guided_setup_id(),
			array(
				'recommendations' => $recommendations,
			)
		);
	}



	/**
	 * Apply merged recommendation profile values to the active settings profile.
	 *
	 * Called when leaving recommended_setup ("Apply settings & finish setup") and again
	 * on the final finish action as a safety net.
	 */
	protected function maybe_apply_recommendations_on_finish() {
		// Bail if the recommendations engine is unavailable
		if ( ! class_exists( 'FluidCheckout_Guided_Setup_Recommendations' ) || ! class_exists( 'FluidCheckout_Settings' ) ) {
			return;
		}

		$this->maybe_refresh_recommendations_cache();

		$recommendations = $this->get_recommendations_progress();
		$values          = FluidCheckout_Guided_Setup_Recommendations::instance()->get_values_to_apply( $recommendations );

		// Bail if there is nothing to apply
		if ( empty( $values ) ) {
			return;
		}

		FluidCheckout_Settings::instance()->update_active_profile_values( $values );
	}



	/**
	 * Set started_at once when the wizard begins.
	 */
	protected function ensure_started_at() {
		$guided_setup_id = $this->get_guided_setup_id();
		$progress        = self::get_progress_for_slug( $guided_setup_id );

		if ( ! empty( $progress['started_at'] ) ) {
			return;
		}

		self::set_progress_for_slug(
			$guided_setup_id,
			array(
				'started_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			)
		);
	}



	/**
	 * Set completed_at on finish.
	 */
	protected function set_completed_at() {
		self::set_progress_for_slug(
			$this->get_guided_setup_id(),
			array(
				'completed_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			)
		);
	}



	/**
	 * Persist telemetry opt-in from step 1.
	 *
	 * @param array $answers Answers.
	 */
	protected function maybe_save_telemetry_opt_in( $answers ) {
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) {
			return;
		}

		$enabled = ! empty( $answers['telemetry_opt_in'] ) ? 'yes' : 'no';
		FluidCheckout_Settings::instance()->update_raw_option( 'fc_telemetry_enabled', $enabled, false );

		if ( class_exists( 'FC_Telemetry_Client' ) ) {
			if ( 'yes' === $enabled ) {
				FC_Telemetry_Client::schedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
			} else {
				FC_Telemetry_Client::unschedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
			}
		}
	}



	/**
	 * Subscribe the merchant to the Fluid Checkout newsletter on finish when opted in.
	 *
	 * Sends a separate server-side request to the public fc-licenses newsletter endpoint.
	 * Email is never included in telemetry.
	 */
	protected function maybe_subscribe_newsletter() {
		$answers = $this->get_stored_answers();

		// Bail if newsletter was not opted in or email is missing
		if ( empty( $answers['newsletter_opt_in'] ) || empty( $answers['email'] ) ) {
			return;
		}

		$email = sanitize_email( $answers['email'] );

		// Bail if email is invalid
		if ( empty( $email ) || ! is_email( $email ) ) {
			return;
		}

		$this->send_newsletter_signup_request( $email );
	}



	/**
	 * Get the public newsletter signup API URL.
	 *
	 * Uses the same API host as telemetry.
	 */
	protected function get_newsletter_signup_url() {
		$api_base = class_exists( 'FluidCheckout' ) ? FluidCheckout::get_telemetry_api_url() : 'https://fluidcheckout.com';
		$api_base = untrailingslashit( (string) $api_base );

		// Bail if the API base URL is empty
		if ( '' === $api_base ) {
			return '';
		}

		return $api_base . '/wp-json/fc-licenses/v1/newsletter/subscribe';
	}



	/**
	 * POST the opted-in email to the public newsletter signup API on fluidcheckout.com.
	 *
	 * @param string $email Sanitized email address.
	 */
	protected function send_newsletter_signup_request( $email ) {
		$url = $this->get_newsletter_signup_url();

		// Bail if the signup URL is not configured
		if ( '' === $url ) {
			return;
		}

		$body = array(
			'email'  => $email,
			'source' => 'guided_setup',
		);

		// Maybe add the current admin user's name for the contact record
		$user = wp_get_current_user();
		if ( $user instanceof WP_User && $user->exists() ) {
			// Maybe set first name
			if ( ! empty( $user->first_name ) ) {
				$body['first_name'] = $user->first_name;
			}

			// Maybe set last name
			if ( ! empty( $user->last_name ) ) {
				$body['last_name'] = $user->last_name;
			}
		}

		wp_remote_post(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'headers'   => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'      => wp_json_encode( $body ),
			)
		);
	}



	/**
	 * Send telemetry with full guided setup progress when enabled.
	 *
	 * @param bool $is_finish Whether this send is triggered from finish.
	 */
	protected function maybe_send_guided_setup_telemetry( $is_finish ) {
		if ( ! class_exists( 'FC_Telemetry_Client' ) || ! FC_Telemetry_Client::is_telemetry_enabled() ) {
			return;
		}

		$guided_setup_id = $this->get_guided_setup_id();
		$progress        = self::get_progress_for_slug( $guided_setup_id );

		/**
		 * Filters guided setup progress before telemetry HTTP send.
		 *
		 * @since 5.0.0
		 *
		 * @param array  $progress        Progress document for the slug.
		 * @param string $guided_setup_id Guided setup identifier.
		 */
		$progress = apply_filters( 'fc_telemetry_guided_setup_progress', $progress, $guided_setup_id );

		$append_guided_setup = function( $payload ) use ( $progress, $guided_setup_id, $is_finish ) {
			if ( ! is_array( $payload ) ) {
				return $payload;
			}

			$payload['guided_setup'] = array(
				'schema_version'  => 1,
				'guided_setup_id' => $guided_setup_id,
				'completed'       => (bool) $is_finish,
				'progress'        => $progress,
			);

			return $payload;
		};

		add_filter( 'fc_telemetry_payload', $append_guided_setup, 10, 1 );

		FC_Telemetry_Client::send_telemetry_now(
			array( 'basic_environment' ),
			false,
			false,
			FluidCheckout::get_telemetry_api_url()
		);

		remove_filter( 'fc_telemetry_payload', $append_guided_setup, 10 );
	}



	/**
	 * Default telemetry sanitizer: never include the merchant email.
	 *
	 * Newsletter signup uses a separate request. Email must not ride along in telemetry.
	 *
	 * @param array  $progress        Progress document.
	 * @param string $guided_setup_id Guided setup identifier.
	 *
	 * @return array
	 */
	public function sanitize_guided_setup_progress_for_telemetry( $progress, $guided_setup_id ) {
		if ( ! is_array( $progress ) || 'fc_checkout' !== $guided_setup_id ) {
			return $progress;
		}

		if ( empty( $progress['answers'] ) || ! is_array( $progress['answers'] ) ) {
			return $progress;
		}

		// Ensure email is not included in telemetry
		unset( $progress['answers']['email'] );

		return $progress;
	}



	/**
	 * Get full progress snapshot for telemetry/tests.
	 *
	 * @return array
	 */
	public function get_progress_snapshot() {
		return self::get_progress_for_slug( $this->get_guided_setup_id() );
	}

}

FluidCheckout_Admin_GuidedSetup_FluidCheckout::instance();

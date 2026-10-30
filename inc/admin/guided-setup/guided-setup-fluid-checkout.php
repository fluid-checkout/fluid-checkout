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
	 * Finish guided setup: newsletter, completed_at, telemetry snapshot (no recommendation preset apply in Phase 1).
	 */
	public function process_finish_guided_setup() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'finish_nonce_action' ) ) ) {
			return;
		}

		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

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
	 * AJAX: recommended_setup placeholder step (shell only; no recommendation engine yet).
	 */
	public function ajax_process_step_recommended_setup() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$this->advance_step( 'wrap_up' );
		$this->maybe_send_guided_setup_telemetry( false );

		$this->send_step_ajax_success();
	}



	/**
	 * AJAX: debounced partial save for product types / delivery toggles.
	 */
	public function ajax_save_checkout_answers() {
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		$form_data = $this->get_form_data_from_request();
		$existing  = $this->get_stored_answers();
		$answers   = FluidCheckout_Guided_Setup_Checkout_Data::sanitize_debounced_form_data( $form_data, $existing );

		$this->persist_answers( $answers );

		wp_send_json_success();
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
				'industryRequired'       => __( 'Please select what type of products or services you sell.', 'fluid-checkout' ),
				'emailRequired'          => __( 'Please enter a valid email address for the newsletter.', 'fluid-checkout' ),
				'productTypesRequired'   => __( 'Please select at least one product type.', 'fluid-checkout' ),
				'deliveryMethodsRequired' => __( 'Please select at least one delivery method for physical products.', 'fluid-checkout' ),
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
		$industries           = FluidCheckout_Guided_Setup_Checkout_Data::get_industries();
		$selected_industry    = ! empty( $answers['business_industry'] ) ? $answers['business_industry'] : FluidCheckout_Guided_Setup_Checkout_Data::get_prefill_industry();
		$user_email           = ! empty( $answers['email'] ) ? $answers['email'] : wp_get_current_user()->user_email;
		$newsletter_checked   = ! empty( $answers['newsletter_opt_in'] );
		$telemetry_checked    = class_exists( 'FC_Telemetry_Client' ) && FC_Telemetry_Client::is_telemetry_enabled();
		if ( ! $telemetry_checked && ! empty( $answers['telemetry_opt_in'] ) ) {
			$telemetry_checked = true;
		}
		$telemetry_hidden     = class_exists( 'FC_Telemetry_Client' ) && FC_Telemetry_Client::is_disabled_by_constant();
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'about_store' ) ); ?>" data-fc-setup-step="about_store">
			<div class="fc-setup-card__inner">
				<form>
					<h2><?php echo esc_html__( 'Welcome to Fluid Checkout!', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'It is great to have you here with us! We will be guiding you through the setup process – your answers to a few questions will help us recommend checkout settings for your store. Applying those recommendations to your store is coming soon.', 'fluid-checkout' ); ?></p>

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
	 * Preview recommendation rows for the recommended_setup step (styling / IA only).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_recommended_setup_preview_settings() {
		return array(
			array(
				'id'          => 'multi_step_checkout',
				'title'       => __( 'Multi-step checkout', 'fluid-checkout' ),
				'description' => __( 'Split checkout into clear steps so customers know where they are in the flow.', 'fluid-checkout' ),
				'checked'     => true,
				'is_pro'      => false,
			),
			array(
				'id'          => 'shipping_before_billing',
				'title'       => __( 'Shipping before billing', 'fluid-checkout' ),
				'description' => __( 'Collect the shipping address first, then copy it to billing when both are the same.', 'fluid-checkout' ),
				'checked'     => true,
				'is_pro'      => false,
			),
			array(
				'id'          => 'hide_optional_fields',
				'title'       => __( 'Hide optional fields by default', 'fluid-checkout' ),
				'description' => __( 'Keep company, address line 2, and order notes behind an Add link until they are needed.', 'fluid-checkout' ),
				'checked'     => true,
				'is_pro'      => false,
			),
			array(
				'id'          => 'google_address_autocomplete',
				'title'       => __( 'Google address autocomplete', 'fluid-checkout' ),
				'description' => __( 'Suggest verified addresses as customers type to reduce typos and failed deliveries.', 'fluid-checkout' ),
				'checked'     => false,
				'is_pro'      => true,
			),
			array(
				'id'          => 'address_book',
				'title'       => __( 'Address book at checkout', 'fluid-checkout' ),
				'description' => __( 'Let logged-in customers pick a saved address instead of retyping it every time.', 'fluid-checkout' ),
				'checked'     => false,
				'is_pro'      => true,
			),
		);
	}



	/**
	 * Output preview recommendation toggles (not connected to real settings yet).
	 */
	protected function output_recommended_setup_preview_settings() {
		$settings = $this->get_recommended_setup_preview_settings();

		if ( empty( $settings ) ) {
			return;
		}

		$pro_badge_html = '';

		if ( class_exists( 'FluidCheckout_Admin' ) ) {
			$pro_badge_html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'guided-setup-recommendations' );
		}
		?>
		<div class="fc-setup-recommendation-options" role="group" aria-label="<?php echo esc_attr__( 'Recommended checkout settings', 'fluid-checkout' ); ?>">
			<?php foreach ( $settings as $setting ) : ?>
				<?php
				$input_id     = 'fc_setup_recommendation_preview_' . $setting['id'];
				$is_disabled  = ! empty( $setting['is_pro'] ) && ! empty( $pro_badge_html );
				$option_class = 'fc-setup-recommendation-option';

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
							value="1"
							<?php checked( ! empty( $setting['checked'] ) ); ?>
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
						<?php if ( ! empty( $setting['is_pro'] ) && ! empty( $pro_badge_html ) ) : ?>
							<?php echo $pro_badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}



	/**
	 * Output recommended_setup placeholder step.
	 */
	protected function output_step_recommended_setup() {
		?>
		<div class="fc-setup-card <?php echo esc_attr( $this->get_step_card_visibility_class( 'recommended_setup' ) ); ?>" data-fc-setup-step="recommended_setup">
			<div class="fc-setup-card__inner">
				<form>
					<h2><?php echo esc_html__( 'Recommended setup', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'Based on what you told us, these checkout options are a good starting point. Preview how recommendations will look—nothing here is saved to your store settings yet.', 'fluid-checkout' ); ?></p>
					<?php $this->output_recommended_setup_preview_settings(); ?>
				</form>
			</div>
			<div class="fc-setup-card__footer">
				<div class="fc-setup-step-actions">
					<?php $this->output_step_actions_start( 'recommended_setup' ); ?>
					<div class="fc-setup-submit">
						<a href="#" class="button button-primary" data-step="recommended_setup"><span class="spinner"></span><span class="fc-setup-continue-label"><?php echo esc_html( $this->get_proceed_to_next_step_button_label( 'recommended_setup' ) ); ?></span></a>
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
				<form>
					<h2><?php echo esc_html__( 'All set', 'fluid-checkout' ); ?></h2>
					<p class="fc-setup-intro"><?php echo esc_html__( 'Thanks for sharing details about your store. Finish to return to Fluid Checkout settings. Your answers are saved for future recommendations; checkout options are not applied automatically yet.', 'fluid-checkout' ); ?></p>
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
		wp_send_json_success(
			array(
				'currentStep'         => $this->get_max_reached_step(),
				'maxReachedStepIndex' => $this->get_max_reached_step_index(),
			)
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
	 * Newsletter subscribe hook on finish.
	 */
	protected function maybe_subscribe_newsletter() {
		$answers = $this->get_stored_answers();

		if ( empty( $answers['newsletter_opt_in'] ) || empty( $answers['email'] ) ) {
			return;
		}

		/**
		 * Fires when the merchant finishes guided setup with newsletter opt-in.
		 *
		 * @param string $email   Email address.
		 * @param array  $answers Guided setup answers.
		 */
		do_action( 'fc_guided_setup_subscribe_newsletter', sanitize_email( $answers['email'] ), $answers );
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
		 * Sanitize guided setup progress before telemetry HTTP send.
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
	 * Default telemetry sanitizer: omit email when newsletter is off.
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

		if ( empty( $progress['answers']['newsletter_opt_in'] ) ) {
			unset( $progress['answers']['email'] );
		}

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

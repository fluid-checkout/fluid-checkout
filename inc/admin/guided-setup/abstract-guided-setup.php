<?php
defined( 'ABSPATH' ) || exit;

/**
 * Abstract admin guided setup base class.
 *
 * Manages the guided setup layout, progress bar, steps and sub-steps system.
 */
abstract class FluidCheckout_Admin_GuidedSetup {

	/**
	 * Option name for multi-slug guided setup progress.
	 *
	 * @var string
	 */
	const PROGRESS_OPTION = 'fc_guided_setup_progress';

	/**
	 * Hidden admin page slug.
	 *
	 * @var string
	 */
	public $page_slug = '';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	public $page_hook_suffix = '';

	/**
	 * Guided setup steps.
	 *
	 * @var array
	 */
	public $steps = array();

	/**
	 * Current guided setup step slug.
	 *
	 * @var string
	 */
	public $current_step = '';

	/**
	 * Current guided setup step index.
	 *
	 * @var int
	 */
	public $current_step_index = 0;



	/**
	 * Singleton instances.
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Registered guided setup admin page slugs.
	 *
	 * @var array
	 */
	protected static $registered_page_slugs = array();

	/**
	 * Singleton instance function.
	 *
	 * @return static
	 */
	public static function instance() {
		$called_class = get_called_class();

		if ( ! array_key_exists( $called_class, self::$instances ) || null === self::$instances[ $called_class ] ) {
			self::$instances[ $called_class ] = new $called_class();
		}

		return self::$instances[ $called_class ];
	}

	/**
	 * Register a guided setup admin page slug for screen detection.
	 *
	 * @param   string  $page_slug  Admin page slug.
	 */
	public static function register_guided_setup_page_slug( $page_slug ) {
		$page_slug = (string) $page_slug;

		// Bail if page slug is empty
		if ( '' === $page_slug ) { return; }

		self::$registered_page_slugs[ $page_slug ] = true;
	}

	/**
	 * Whether the current admin request is any Fluid Checkout guided setup screen.
	 *
	 * @return  bool
	 */
	public static function is_guided_setup_admin_screen() {
		// Bail if not in admin
		if ( ! is_admin() ) { return false; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';

		// Bail if page query arg is empty
		if ( '' === $page ) { return false; }

		return isset( self::$registered_page_slugs[ $page ] );
	}



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Initialize hooks.
	 */
	abstract public function hooks();



	/**
	 * Get guided setup configuration values.
	 *
	 * @return  array
	 */
	abstract protected function get_config();

	/**
	 * Get a guided setup configuration value.
	 *
	 * @param   string  $key  Configuration key.
	 *
	 * @return  mixed
	 */
	protected function get_config_value( $key ) {
		$config = $this->get_config();

		return isset( $config[ $key ] ) ? $config[ $key ] : '';
	}



	/**
	 * Get the guided setup admin page title.
	 *
	 * @return  string
	 */
	abstract public function get_page_title();



	/**
	 * Get step definitions for the guided setup.
	 *
	 * Each step is keyed by slug and must include at least a `label` string.
	 * Optional keys: `output_callback`, `actions_callback`, `continue_enabled_callback`, `continue_attributes`, `hide_skip_link`, `use_finish_url`.
	 *
	 * @return  array
	 */
	abstract protected function get_step_definitions();



	/**
	 * Get sub-step definitions for the guided setup.
	 *
	 * @return  array
	 */
	abstract protected function get_sub_step_definitions();



	/**
	 * Get the plugin settings page URL.
	 *
	 * @return  string
	 */
	abstract public function get_settings_url();



	/**
	 * Output the guided setup page markup.
	 */
	abstract public function output_guided_setup();



	/**
	 * Whether the current admin request is the guided setup page.
	 *
	 * @return  bool
	 */
	public function is_guided_setup_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return is_admin() && ! empty( $_GET[ 'page' ] ) && $this->page_slug === sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) );
	}


	/**
	 * Register the hidden guided setup admin page.
	 */
	public function register_admin_page() {
		// Register hidden admin page without a parent menu item
		$this->page_hook_suffix = add_submenu_page(
			null, // Parent menu slug, intentionally empty to hide the page from the admin menu.
			$this->get_page_title(),
			$this->get_page_title(),
			$this->get_config_value( 'capability' ),
			$this->page_slug,
			array( $this, 'output_guided_setup' )
		);

		// Track page slug so notice suppression can detect guided setup screens
		self::register_guided_setup_page_slug( $this->page_slug );

		// Set browser tab title for hidden admin pages
		add_action( 'load-' . $this->page_hook_suffix, array( $this, 'setup_admin_page_title' ) );

		// Hide all admin notices (FC and third-party) on guided setup screens
		add_action( 'load-' . $this->page_hook_suffix, array( $this, 'maybe_strip_admin_notices_on_guided_setup' ), 20 );
	}

	/**
	 * Remove WordPress admin notice actions on guided setup screens.
	 */
	public function maybe_strip_admin_notices_on_guided_setup() {
		// Bail if Fluid Checkout admin helpers are unavailable
		if ( ! class_exists( 'FluidCheckout_Admin' ) ) { return; }

		FluidCheckout_Admin::strip_non_fc_admin_notices_on_load( false );
	}



	/**
	 * Set the admin page title used in the browser tab.
	 */
	public function setup_admin_page_title() {
		global $title;

		$title = $this->get_page_title();
	}


	/**
	 * Get the saved guided setup step option value.
	 *
	 * @return  string
	 */
	public function get_saved_step_option() {
		$guided_setup_id = $this->get_guided_setup_id();

		// Get the saved step from the slug progress store
		if ( ! empty( $guided_setup_id ) ) {
			return self::get_current_step_for_slug( $guided_setup_id );
		}

		// Get the saved step option value
		$saved_step = $this->get_guided_setup_stored_option( $this->get_config_value( 'current_step_option' ), '' );

		// Maybe return empty step slug
		if ( empty( $saved_step ) ) {
			return '';
		}

		return $saved_step;
	}


	/**
	 * Get the current guided setup step slug.
	 *
	 * @return  string
	 */
	public function get_current_step() {
		// Get the steps
		$steps             = $this->get_steps();
		$step_keys         = array_keys( $steps );

		// Get the max reached step
		$max_reached_step  = $this->get_max_reached_step();
		$max_reached_index = 0;

		// If there is a max reached step, get the max reached step index
		if ( ! empty( $max_reached_step ) ) {
			// Get max reached step index
			$index = array_search( $max_reached_step, $step_keys, true );

			// If the max reached step index is found, set it
			if ( false !== $index ) {
				$max_reached_index = (int) $index;
			}
		}

		// Maybe get requested step from URL query parameter
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET[ 'current_step' ] ) ) {
			// Get the requested step
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested_step = sanitize_text_field( wp_unslash( $_GET[ 'current_step' ] ) );

			if ( array_key_exists( $requested_step, $steps ) ) {
				$requested_index = array_search( $requested_step, $step_keys, true );

				// Return requested step when it has already been reached
				if ( false !== $requested_index && (int) $requested_index <= $max_reached_index ) {
					return $requested_step;
				}
			}
		}

		// Maybe return max reached step
		if ( ! empty( $max_reached_step ) ) {
			return $max_reached_step;
		}

		// Otherwise, return the first step slug
		return (string) key( $steps );
	}


	/**
	 * Get the current guided setup step index.
	 *
	 * @param   string  $current_step  Optional. Current step slug.
	 *
	 * @return  int
	 */
	public function get_current_step_index( $current_step = '' ) {
		// Maybe get current step
		if ( empty( $current_step ) ) {
			$current_step = $this->get_current_step();
		}

		// Get the current step index
		$step_keys = array_keys( $this->get_steps() );
		$index     = array_search( $current_step, $step_keys, true );

		// Return the current step index
		return false === $index ? 0 : (int) $index;
	}


	/**
	 * Get the furthest guided setup step reached in saved options.
	 *
	 * @return  string  Max reached step, or empty string if no step has been reached yet.
	 */
	public function get_max_reached_step() {
		// Maybe get saved step
		$steps      = $this->get_steps();
		$saved_step = $this->get_saved_step_option();

		// Maybe return saved step
		if ( ! empty( $saved_step ) && array_key_exists( $saved_step, $steps ) ) {
			return $saved_step;
		}

		// Otherwise, return empty step slug
		return '';
	}


	/**
	 * Get the furthest guided setup step index reached in saved options.
	 *
	 * @param   string  $max_reached_step  Optional. Max reached step slug.
	 *
	 * @return  int
	 */
	public function get_max_reached_step_index( $max_reached_step = '' ) {
		// Maybe get max reached step
		if ( empty( $max_reached_step ) ) {
			$max_reached_step = $this->get_max_reached_step();
		}

		// Maybe set to starting step index
		if ( empty( $max_reached_step ) ) {
			return 0;
		}

		// Get the max reached step index
		$step_keys = array_keys( $this->get_steps() );
		$index     = array_search( $max_reached_step, $step_keys, true );

		// Return the max reached step index
		return false === $index ? 0 : (int) $index;
	}


	/**
	 * Get the index of a guided setup step slug.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  int
	 */
	public function get_step_index( $step_slug ) {
		// Get step keys and find index for the slug
		$step_keys = array_keys( $this->get_steps() );
		$index     = array_search( $step_slug, $step_keys, true );

		return false === $index ? -1 : (int) $index;
	}


	/**
	 * Whether a guided setup step has been reached.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public function is_step_reached( $step_slug ) {
		$step_index = $this->get_step_index( $step_slug );

		// Bail if step slug is invalid
		if ( $step_index < 0 ) {
			return false;
		}

		return $step_index <= $this->get_max_reached_step_index();
	}


	/**
	 * Whether a guided setup step has been completed.
	 *
	 * Steps without sub-steps are complete once reached. Steps with sub-steps require all sub-steps to be complete.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public function is_step_complete( $step_slug ) {
		// Maybe return whether the step has been completed
		if ( empty( $this->get_sub_steps( $step_slug ) ) ) {
			return $this->is_step_reached( $step_slug );
		}

		// Return whether all sub-steps are complete for the step
		return $this->is_all_sub_steps_complete( $step_slug );
	}


	/**
	 * Whether the guided setup is completed.
	 *
	 * @return  bool
	 */
	public function is_completed() {
		// Get the last step slug
		$last_step = array_key_last( $this->get_steps() );

		// Return whether the last step has been completed, meaning the guided setup is complete
		return $this->is_step_complete( $last_step );
	}


	/**
	 * Get the guided setup page URL.
	 *
	 * @return  string
	 */
	public function get_guided_setup_url() {
		return add_query_arg(
			array(
				'page' => $this->page_slug,
			),
			admin_url( 'admin.php' )
		);
	}


	/**
	 * Get the guided setup page URL for a specific step.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  string
	 */
	public function get_guided_setup_step_url( $step_slug ) {
		return add_query_arg(
			array(
				'page'         => $this->page_slug,
				'current_step' => $step_slug,
			),
			admin_url( 'admin.php' )
		);
	}


	/**
	 * Get the skip guided setup URL.
	 *
	 * @return  string
	 */
	public function get_skip_guided_setup_url() {
		// Define the skip guided setup URL
		$skip_guided_setup_url = add_query_arg(
			array(
				'page'         => $this->page_slug,
				$this->get_config_value( 'admin_action_param' ) => 'skip_guided_setup',
			),
			admin_url( 'admin.php' )
		);

		// Return the nonce-protected skip guided setup URL
		return wp_nonce_url(
			$skip_guided_setup_url,
			$this->get_config_value( 'skip_nonce_action' ),
			$this->get_config_value( 'nonce_param' )
		);
	}


	/**
	 * Whether the back to previous step button should be shown for a step.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public function should_show_back_to_previous_step_button( $step_slug ) {
		// Get the step keys
		$step_keys = array_keys( $this->get_steps() );

		// Get the step index
		$index = array_search( $step_slug, $step_keys, true );

		// Return whether the step index is not negative and greater than 0
		return false !== $index && $index > 0;
	}


	/**
	 * Output the back to previous step button for a guided setup step.
	 *
	 * @param   string  $step_slug  Step slug.
	 */
	public function output_back_to_previous_step_button( $step_slug ) {
		// Bail if the back to previous step button should not be shown
		if ( ! $this->should_show_back_to_previous_step_button( $step_slug ) ) { return; }

		?>
		<button type="button" class="button button-secondary fc-setup-step-back" data-setup-step-back>
			<?php echo esc_html__( 'Back to previous step', 'fluid-checkout' ); ?>
		</button>
		<?php
	}


	/**
	 * Output the left side of guided setup step actions (back, start over, skip to settings).
	 *
	 * @param   string  $step_slug  Step slug.
	 */
	public function output_step_actions_start( $step_slug ) {
		?>
		<div class="fc-setup-step-actions__start">
			<?php $this->output_back_to_previous_step_button( $step_slug ); ?>
			<div class="fc-setup-step-actions__links">
				<?php $this->output_start_over_link(); ?>
				<?php $this->output_skip_to_settings_link(); ?>
			</div>
		</div>
		<?php
	}


	/**
	 * Output the skip guided setup link.
	 */
	public function output_skip_to_settings_link() {
		?>
		<a href="<?php echo esc_url( $this->get_skip_guided_setup_url() ); ?>" class="button button-link fc-setup-skip-to-settings" aria-label="<?php echo esc_attr__( 'Skip to settings', 'fluid-checkout' ); ?>"><?php echo esc_html__( 'Skip to settings', 'fluid-checkout' ); ?></a>
		<?php
	}


	/**
	 * Get the start over guided setup URL.
	 *
	 * @return  string
	 */
	public function get_start_over_guided_setup_url() {
		// Define the start over guided setup URL
		$start_over_guided_setup_url = add_query_arg(
			array(
				'page'          => $this->page_slug,
				$this->get_config_value( 'admin_action_param' ) => 'start_over_guided_setup',
			),
			admin_url( 'admin.php' )
		);

		// Return the nonce-protected start over guided setup URL
		return wp_nonce_url(
			$start_over_guided_setup_url,
			$this->get_config_value( 'start_over_nonce_action' ),
			$this->get_config_value( 'nonce_param' )
		);
	}


	/**
	 * Output the start over guided setup link.
	 */
	public function output_start_over_link() {
		?>
		<a href="<?php echo esc_url( $this->get_start_over_guided_setup_url() ); ?>" class="button button-link fc-setup-start-over" aria-label="<?php echo esc_attr__( 'Start over and return to the first guided setup step', 'fluid-checkout' ); ?>"><?php echo esc_html__( 'Start over', 'fluid-checkout' ); ?></a>
		<?php
	}


	/**
	 * Get the guided setup sub-step progress option name.
	 *
	 * @return  string
	 */
	public function get_sub_step_progress_option_name() {
		return $this->get_config_value( 'sub_step_progress_option' );
	}


	/**
	 * Get the guided setup identifier from configuration.
	 *
	 * @return  string
	 */
	protected function get_guided_setup_id() {
		return self::sanitize_guided_setup_id( $this->get_config_value( 'guided_setup_id' ) );
	}


	/**
	 * Get guided setup script settings.
	 *
	 * @return  array
	 */
	public function get_guided_setup_script_settings() {
		$settings = array(
			'guidedSetupId'        => $this->get_config_value( 'guided_setup_id' ),
			'currentStep'          => $this->get_current_step(),
			'currentStepIndex'     => $this->get_current_step_index(),
			'maxReachedStepIndex'  => $this->get_max_reached_step_index(),
			'steps'                => array_keys( $this->get_steps() ),
			'subSteps'             => $this->get_sub_steps_definitions_for_js(),
			'subStepProgress'      => $this->get_sub_step_progress(),
			'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
			'ajaxStepActionPrefix' => $this->get_config_value( 'ajax_step_action_prefix' ),
			'ajaxSubStepAction'    => $this->get_config_value( 'ajax_sub_step_action' ),
			'nonceFieldId'         => $this->get_config_value( 'nonce_param' ),
			'nonceParamName'       => $this->get_config_value( 'nonce_param' ),
			'i18n'                 => array(
				'subStepsRequired'      => __( 'Please complete all sub-steps before continuing.', 'fluid-checkout' ),
				'subStepExpand'         => __( 'Expand', 'fluid-checkout' ),
				'subStepCollapse'       => __( 'Collapse', 'fluid-checkout' ),
				'subStepSkip'           => __( 'Skip', 'fluid-checkout' ),
				'subStepMarkComplete'   => __( 'Mark as complete', 'fluid-checkout' ),
				'subStepMarkIncomplete' => __( 'Mark as incomplete', 'fluid-checkout' ),
				'backToPreviousStep'    => __( 'Back to previous step', 'fluid-checkout' ),
			),
		);

		return apply_filters( 'fc_guided_setup_script_settings', $settings, $this->get_config_value( 'guided_setup_id' ) );
	}


	/**
	 * Get guided setup sub-step definitions for JavaScript settings.
	 *
	 * @return  array
	 */
	public function get_sub_steps_definitions_for_js() {
		$sub_steps        = $this->get_sub_steps();
		$script_sub_steps = array();

		// Iterate through steps and build sub-step title map for JavaScript
		foreach ( $sub_steps as $step_slug => $step_sub_steps ) {
			$script_sub_steps[ $step_slug ] = array();

			foreach ( array_keys( $step_sub_steps ) as $sub_step_id ) {
				$script_sub_steps[ $step_slug ][ $sub_step_id ] = $this->get_sub_step_title( $step_slug, $sub_step_id );
			}
		}

		return $script_sub_steps;
	}


	/**
	 * Get the title for a guided setup sub-step.
	 *
	 * @param   string  $step_slug    Step slug.
	 * @param   string  $sub_step_id  Sub-step identifier.
	 *
	 * @return  string
	 */
	public function get_sub_step_title( $step_slug, $sub_step_id ) {
		$sub_steps = $this->get_sub_steps( $step_slug );

		// Bail if sub-step definition or title is not available
		if ( ! isset( $sub_steps[ $sub_step_id ] ) || ! is_array( $sub_steps[ $sub_step_id ] ) || empty( $sub_steps[ $sub_step_id ]['title'] ) ) {
			return '';
		}

		return $sub_steps[ $sub_step_id ]['title'];
	}


	/**
	 * Get the 1-based index for a guided setup sub-step.
	 *
	 * @param   string  $step_slug    Step slug.
	 * @param   string  $sub_step_id  Sub-step identifier.
	 *
	 * @return  int
	 */
	public function get_sub_step_index( $step_slug, $sub_step_id ) {
		$sub_step_ids = array_keys( $this->get_sub_steps( $step_slug ) );
		$index        = array_search( $sub_step_id, $sub_step_ids, true );

		// Bail if sub-step id is not found
		if ( false === $index ) {
			return 0;
		}

		return (int) $index + 1;
	}


	/**
	 * Get saved guided setup sub-step progress.
	 *
	 * @return  array
	 */
	public function get_saved_sub_step_progress() {
		$guided_setup_id = $this->get_guided_setup_id();

		// Get saved sub-step progress from the slug progress store
		if ( ! empty( $guided_setup_id ) ) {
			return self::get_sub_step_progress_for_slug( $guided_setup_id );
		}

		// Get saved sub-step progress from options
		$progress = $this->get_guided_setup_stored_option( $this->get_sub_step_progress_option_name(), array() );

		return is_array( $progress ) ? $progress : array();
	}

	/**
	 * Get sub-step progress for steps that define sub-steps.
	 *
	 * @return  array
	 */
	public function get_sub_step_progress() {
		$sub_steps_definition = $this->get_sub_steps();
		$all_progress         = $this->get_saved_sub_step_progress();
		$progress             = array();

		// Iterate through steps with sub-steps and collect saved progress
		foreach ( array_keys( $sub_steps_definition ) as $step_slug ) {
			$progress[ $step_slug ] = isset( $all_progress[ $step_slug ] ) && is_array( $all_progress[ $step_slug ] )
				? $all_progress[ $step_slug ]
				: array();
		}

		return $progress;
	}

	/**
	 * Get sub-step progress for one step.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  array
	 */
	public function get_sub_step_progress_for_step( $step_slug ) {
		// Maybe return empty array if no sub-steps are defined for the step
		if ( empty( $this->get_sub_steps( $step_slug ) ) ) { return array(); }

		// Get all progress
		$all_progress = $this->get_saved_sub_step_progress();

		// Maybe return empty array if no progress is defined for the step
		if ( ! isset( $all_progress[ $step_slug ] ) || ! is_array( $all_progress[ $step_slug ] ) ) { return array(); }

		// Return the progress for the step
		return $all_progress[ $step_slug ];
	}



	/**
	 * Get the state for a guided setup sub-step.
	 *
	 * @param   string  $step_slug     Step slug.
	 * @param   string  $sub_step_id   Sub-step identifier.
	 *
	 * @return  array
	 */
	public function get_sub_step_state( $step_slug, $sub_step_id ) {
		// Get saved progress for the step
		$progress = $this->get_sub_step_progress_for_step( $step_slug );

		// Return default incomplete state when no progress is saved
		if ( ! isset( $progress[ $sub_step_id ] ) || ! is_array( $progress[ $sub_step_id ] ) ) {
			return array(
				'completed' => false,
			);
		}

		return wp_parse_args(
			$progress[ $sub_step_id ],
			array(
				'completed' => false,
			)
		);
	}



	/**
	 * Whether a guided setup sub-step has been marked complete.
	 *
	 * @param   string  $step_slug    Step slug.
	 * @param   string  $sub_step_id  Sub-step identifier.
	 *
	 * @return  bool
	 */
	public function is_sub_step_complete( $step_slug, $sub_step_id ) {
		// Get saved sub-step state
		$state = $this->get_sub_step_state( $step_slug, $sub_step_id );

		return ! empty( $state['completed'] );
	}



	/**
	 * Save guided setup sub-step state.
	 *
	 * @param   string  $step_slug       Step slug.
	 * @param   string  $sub_step_id     Sub-step identifier.
	 * @param   array   $state_updates   State values to update.
	 *
	 * @return  bool
	 */
	public function save_sub_step_state( $step_slug, $sub_step_id, $state_updates ) {
		$sub_steps = $this->get_sub_steps( $step_slug );

		// Bail if sub-step is not registered for the step
		if ( ! isset( $sub_steps[ $sub_step_id ] ) ) {
			return false;
		}

		// Get all saved progress
		$progress = $this->get_saved_sub_step_progress();

		// Maybe initialize progress arrays for the step and sub-step
		if ( ! isset( $progress[ $step_slug ] ) || ! is_array( $progress[ $step_slug ] ) ) {
			$progress[ $step_slug ] = array();
		}

		if ( ! isset( $progress[ $step_slug ][ $sub_step_id ] ) || ! is_array( $progress[ $step_slug ][ $sub_step_id ] ) ) {
			$progress[ $step_slug ][ $sub_step_id ] = array(
				'completed' => false,
			);
		}

		// Merge state updates into saved progress
		$progress[ $step_slug ][ $sub_step_id ] = array_merge(
			$progress[ $step_slug ][ $sub_step_id ],
			$state_updates
		);

		// Only persist completion state for sub-steps.
		$progress[ $step_slug ][ $sub_step_id ] = array(
			'completed' => ! empty( $progress[ $step_slug ][ $sub_step_id ]['completed'] ),
		);

		// Save updated progress
		$this->save_sub_step_progress( $progress );

		return true;
	}


	/**
	 * Persist guided setup sub-step progress.
	 *
	 * @param   array  $progress  Sub-step progress for all steps.
	 */
	protected function save_sub_step_progress( $progress ) {
		$guided_setup_id = $this->get_guided_setup_id();

		// Save to the slug progress store
		if ( ! empty( $guided_setup_id ) ) {
			self::set_sub_step_progress_for_slug( $guided_setup_id, $progress );
			return;
		}

		update_option( $this->get_sub_step_progress_option_name(), $progress, true );
	}



	/**
	 * Mark a guided setup sub-step as incomplete and revert max reached step when needed.
	 *
	 * Only the target sub-step state is changed. Other sub-step progress remains independent.
	 * Reverts the max reached step to the parent step when it is ahead of that step.
	 *
	 * @param   string  $step_slug    Step slug.
	 * @param   string  $sub_step_id  Sub-step identifier.
	 *
	 * @return  bool
	 */
	public function mark_sub_step_incomplete( $step_slug, $sub_step_id ) {
		$state_updates = array(
			'completed' => false,
		);

		// Save incomplete state for the sub-step
		if ( ! $this->save_sub_step_state( $step_slug, $sub_step_id, $state_updates ) ) {
			return false;
		}

		$step_keys         = array_keys( $this->get_steps() );
		$step_index        = array_search( $step_slug, $step_keys, true );
		$max_reached_index = $this->get_max_reached_step_index();

		// Maybe revert max reached step when viewing a step ahead of the reverted step
		if ( false !== $step_index && (int) $step_index < $max_reached_index ) {
			$this->advance_step( $step_slug );
		}

		return true;
	}



	/**
	 * Whether all sub-steps for a guided setup step are complete.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public function is_all_sub_steps_complete( $step_slug ) {
		$sub_steps = $this->get_sub_steps( $step_slug );

		// Bail if step has no sub-steps
		if ( empty( $sub_steps ) ) {
			return true;
		}

		// Iterate through sub-steps and check completion state
		foreach ( array_keys( $sub_steps ) as $sub_step_id ) {
			if ( ! $this->is_sub_step_complete( $step_slug, $sub_step_id ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Get the full-size URL for a guided setup step image.
	 *
	 * @param   string  $url  Image URL, optionally including a WordPress size suffix.
	 *
	 * @return  string
	 */
	public function get_step_image_full_url( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );

		// Bail if URL path is empty
		if ( empty( $path ) ) {
			return $url;
		}

		// Remove WordPress thumbnail size suffix from the path
		$full_path = preg_replace( '/-\d+x\d+(?=\.[^.]+$)/', '', $path );

		// Return original URL when no size suffix was found
		if ( $full_path === $path ) {
			return $url;
		}

		return str_replace( $path, $full_path, $url );
	}


	/**
	 * Get the preview URL for a guided setup step image.
	 *
	 * @param   string  $url  Image URL, optionally including a WordPress size suffix.
	 *
	 * @return  string
	 */
	public function get_step_image_preview_url( $url ) {
		$full_url = $this->get_step_image_full_url( $url );
		$size     = apply_filters(
			'fc_guided_setup_step_image_preview_size',
			'medium',
			$this->get_config_value( 'guided_setup_id' )
		);

		$attachment_id = attachment_url_to_postid( $full_url );

		// Bail if the image is not in the media library
		if ( empty( $attachment_id ) ) {
			return $full_url;
		}

		$src = wp_get_attachment_image_src( $attachment_id, $size );

		// Bail if attachment size URL is not available
		if ( empty( $src[0] ) ) {
			return $full_url;
		}

		return $src[0];
	}


	/**
	 * Get preview image dimensions for a guided setup step image.
	 *
	 * @param   string  $url  Image URL.
	 *
	 * @return  array  Keys width and height, empty strings when unknown.
	 */
	protected function get_step_image_preview_dimensions( $url ) {
		$dimensions = array(
			'width'  => '',
			'height' => '',
		);

		$full_url = $this->get_step_image_full_url( $url );
		$size     = apply_filters(
			'fc_guided_setup_step_image_preview_size',
			'medium',
			$this->get_config_value( 'guided_setup_id' )
		);

		$attachment_id = attachment_url_to_postid( $full_url );

		// Bail if the image is not in the media library
		if ( empty( $attachment_id ) ) {
			return $dimensions;
		}

		$src = wp_get_attachment_image_src( $attachment_id, $size );

		// Bail if attachment size dimensions are not available
		if ( empty( $src ) ) {
			return $dimensions;
		}

		if ( ! empty( $src[1] ) ) {
			$dimensions['width'] = (string) $src[1];
		}

		if ( ! empty( $src[2] ) ) {
			$dimensions['height'] = (string) $src[2];
		}

		return $dimensions;
	}


	/**
	 * Output a clickable guided setup step image preview.
	 *
	 * @param   string  $url  Image URL.
	 * @param   string  $alt  Accessible description for the image.
	 */
	public function output_step_image( $url, $alt ) {
		// Get full-size and preview image URLs
		$full_url    = $this->get_step_image_full_url( $url );
		$preview_url = $this->get_step_image_preview_url( $url );
		$dimensions  = $this->get_step_image_preview_dimensions( $url );
		?>
		<span class="fc-setup-step-image">
			<button
				type="button"
				class="fc-setup-step-image__trigger"
				data-guided-setup-image-full="<?php echo esc_url( $full_url ); ?>"
				data-guided-setup-image-alt="<?php echo esc_attr( $alt ); ?>"
				aria-label="<?php
				echo esc_attr(
					sprintf(
						/* translators: %s: image description. */
						__( 'View full-size image: %s', 'fluid-checkout' ),
						$alt
					)
				);
				?>"
			>
				<img
					class="fc-setup-step-image__preview"
					src="<?php echo esc_url( $preview_url ); ?>"
					data-fallback-src="<?php echo esc_url( $full_url ); ?>"
					alt=""
					loading="lazy"
					<?php if ( '' !== $dimensions['width'] ) : ?>
					width="<?php echo esc_attr( $dimensions['width'] ); ?>"
					<?php endif; ?>
					<?php if ( '' !== $dimensions['height'] ) : ?>
					height="<?php echo esc_attr( $dimensions['height'] ); ?>"
					<?php endif; ?>
				/>
			</button>
		</span>
		<?php
	}


	/**
	 * Output the guided setup image lightbox modal.
	 */
	public function output_image_modal() {
		?>
		<div class="fc-setup-image-modal" data-guided-setup-image-modal hidden>
			<div class="fc-setup-image-modal__backdrop" data-guided-setup-image-modal-close></div>
			<div class="fc-setup-image-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Full-size setup guide image', 'fluid-checkout' ); ?>">
				<button type="button" class="fc-setup-image-modal__close" data-guided-setup-image-modal-close aria-label="<?php echo esc_attr__( 'Close image', 'fluid-checkout' ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
				<img class="fc-setup-image-modal__image" src="" alt="" />
			</div>
		</div>
		<?php
	}


	/**
	 * Output all registered sub-steps for a guided setup step.
	 *
	 * @param   string  $step_slug  Step slug.
	 */
	public function output_sub_steps( $step_slug ) {
		$sub_steps = $this->get_sub_steps( $step_slug );

		if ( empty( $sub_steps ) ) {
			return;
		}

		echo '<div class="fc-setup-sub-steps">';

		// Iterate through sub-steps and output each collapsible section
		foreach ( $sub_steps as $sub_step_id => $sub_step_definition ) {
			// Skip invalid sub-step definitions
			if ( ! is_array( $sub_step_definition ) ) {
				continue;
			}

			$this->output_collapsible_section_start( $step_slug, $sub_step_id );

			// Maybe output sub-step content from callback
			if ( ! empty( $sub_step_definition['output_callback'] ) && is_callable( $sub_step_definition['output_callback'] ) ) {
				call_user_func( $sub_step_definition['output_callback'] );
			}

			$additional_actions_html = '';

			// Maybe capture additional action buttons from callback
			if ( ! empty( $sub_step_definition['actions_callback'] ) && is_callable( $sub_step_definition['actions_callback'] ) ) {
				ob_start();
				call_user_func( $sub_step_definition['actions_callback'] );
				$additional_actions_html = ob_get_clean();
			}

			$this->output_collapsible_section_end( $step_slug, $sub_step_id, $additional_actions_html );
		}

		echo '</div>';
	}


	/**
	 * Output the start tag for a guided setup collapsible sub-step section.
	 *
	 * @param   string  $step_slug   Parent step slug.
	 * @param   string  $section_id  Sub-step identifier.
	 */
	public function output_collapsible_section_start( $step_slug, $section_id ) {
		$title          = $this->get_sub_step_title( $step_slug, $section_id );
		$sub_step_index = $this->get_sub_step_index( $step_slug, $section_id );

		// Bail if sub-step title or index is not available
		if ( empty( $title ) || $sub_step_index < 1 ) {
			return;
		}

		$section_id_esc      = esc_attr( $section_id );
		$step_slug_esc       = esc_attr( $step_slug );
		$sub_step_state      = $this->get_sub_step_state( $step_slug, $section_id );
		$is_complete         = ! empty( $sub_step_state['completed'] );
		$initial_state       = 'collapsed';
		$is_expanded         = false;
		$content_state_class = 'is-collapsed';
		$wrapper_classes     = array( 'fc-setup-collapsible', 'fc-setup-sub-step' );

		// Maybe add complete state class
		if ( $is_complete ) {
			$wrapper_classes[] = 'fc-setup-sub-step--complete';
		}
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			data-setup-sub-step
			data-setup-step-slug="<?php echo $step_slug_esc; ?>"
			data-setup-sub-step-id="<?php echo $section_id_esc; ?>"
			data-setup-sub-step-index="<?php echo esc_attr( (string) $sub_step_index ); ?>"
		>

			<div class="fc-setup-collapsible__header">
				<span class="fc-setup-sub-step__status" aria-hidden="true">
					<span class="fc-setup-sub-step__status-number"><?php echo esc_html( (string) $sub_step_index ); ?></span>
					<span class="fc-setup-sub-step__status-check dashicons dashicons-yes"></span>
				</span>

				<button
					type="button"
					id="fc-setup-collapsible-handler--<?php echo $section_id_esc; ?>"
					class="fc-setup-collapsible__handler"
					data-collapsible-handler
					aria-controls="fc-setup-collapsible-content--<?php echo $section_id_esc; ?>"
					aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>"
				>
					<span class="fc-setup-collapsible__title"><?php echo esc_html( $title ); ?></span>
				</button>

				<div class="fc-setup-sub-step__actions">
					<?php if ( ! $is_complete ) : ?>
						<button type="button" class="button button-link fc-setup-skip" data-setup-sub-step-skip>
							<?php echo esc_html__( 'Skip', 'fluid-checkout' ); ?>
						</button>
					<?php endif; ?>

					<button type="button" class="button button-secondary fc-setup-sub-step__toggle" data-setup-sub-step-toggle>
						<?php echo $is_expanded ? esc_html__( 'Collapse', 'fluid-checkout' ) : esc_html__( 'Expand', 'fluid-checkout' ); ?>
					</button>
				</div>
			</div>

			<div
				id="fc-setup-collapsible-content--<?php echo $section_id_esc; ?>"
				class="fc-setup-collapsible__content <?php echo esc_attr( $content_state_class ); ?>"
				data-collapsible
				data-collapsible-content
				data-collapsible-initial-state="<?php echo esc_attr( $initial_state ); ?>"
			>
				<div class="collapsible-content__inner">
		<?php
	}


	/**
	 * Output the end tag for a guided setup collapsible sub-step section.
	 *
	 * @param   string  $step_slug               Parent step slug.
	 * @param   string  $section_id              Sub-step identifier.
	 * @param   string  $additional_actions_html Optional. Additional action buttons HTML (already escaped).
	 */
	public function output_collapsible_section_end( $step_slug, $section_id, $additional_actions_html = '' ) {
		$is_complete         = $this->is_sub_step_complete( $step_slug, $section_id );
		$has_mark_complete   = ! $is_complete;
		$has_mark_incomplete = $is_complete;
		$has_actions         = $has_mark_complete || $has_mark_incomplete || ! empty( $additional_actions_html );

		// Maybe output footer action buttons
		if ( $has_actions ) :
			?>
			<div class="fc-setup-sub-step__substep-actions">
				<?php if ( ! empty( $additional_actions_html ) ) : ?>
					<?php echo $additional_actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when passed in. ?>
				<?php endif; ?>

				<?php if ( $has_mark_complete ) : ?>
					<button type="button" class="button button-primary fc-setup-sub-step__complete" data-setup-sub-step-complete>
						<?php echo esc_html__( 'Mark as complete', 'fluid-checkout' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( $has_mark_incomplete ) : ?>
					<button type="button" class="button button-secondary fc-setup-sub-step__incomplete" data-setup-sub-step-incomplete>
						<?php echo esc_html__( 'Mark as incomplete', 'fluid-checkout' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<?php
		endif;
		?>
				</div>
			</div>

		</div>
		<?php
	}


	/**
	 * Get the finish guided setup URL.
	 *
	 * @return  string
	 */
	public function get_finish_url() {
		// Return nonce-protected finish guided setup URL
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'         => $this->page_slug,
					$this->get_config_value( 'admin_action_param' ) => 'finish_guided_setup',
				),
				admin_url( 'admin.php' )
			),
			$this->get_config_value( 'finish_nonce_action' ),
			$this->get_config_value( 'nonce_param' )
		);
	}


	/**
	 * Maybe process admin actions for the guided setup.
	 */
	public function maybe_process_admin_action() {
		// Bail if no admin action is requested
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'admin_action_param' ) ] ) ) {
			return;
		}

		// Get sanitized admin action name
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'admin_action_param' ) ] ) );

		// Dispatch to the registered admin action handler
		do_action( $this->get_config_value( 'admin_action_hook_prefix' ) . $action );
	}


	/**
	 * Process the skip guided setup admin action.
	 */
	public function process_skip_guided_setup() {
		// Bail if nonce verification fails
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'skip_nonce_action' ) ) ) {
			return;
		}

		// Bail if user does not have permission
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

		$this->clear_guided_setup_progress();

		// Redirect to plugin settings
		wp_safe_redirect( $this->get_settings_url() );
		exit;
	}


	/**
	 * Process the finish guided setup admin action.
	 */
	public function process_finish_guided_setup() {
		// Bail if nonce verification fails
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'finish_nonce_action' ) ) ) {
			return;
		}

		// Bail if user does not have permission
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

		$this->clear_guided_setup_progress();

		// Redirect to plugin settings
		wp_safe_redirect( $this->get_settings_url() );
		exit;
	}


	/**
	 * Process the start over guided setup admin action.
	 */
	public function process_start_over_guided_setup() {
		// Bail if nonce verification fails
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'start_over_nonce_action' ) ) ) {
			return;
		}

		// Bail if user does not have permission
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return;
		}

		$this->clear_guided_setup_progress();

		// Redirect to the first guided setup step
		wp_safe_redirect( $this->get_guided_setup_url() );
		exit;
	}


	/**
	 * Clear saved guided setup progress for the current configuration.
	 */
	protected function clear_guided_setup_progress() {
		$guided_setup_id = $this->get_guided_setup_id();

		// Clear slug progress store
		if ( ! empty( $guided_setup_id ) ) {
			self::clear_progress_for_slug( $guided_setup_id );
			return;
		}

		// Clear legacy per-plugin options
		$this->delete_guided_setup_stored_option( $this->get_config_value( 'current_step_option' ) );
		$this->delete_guided_setup_stored_option( $this->get_sub_step_progress_option_name() );
	}


	/**
	 * Verify AJAX request for the guided setup.
	 *
	 * @return  bool
	 */
	public function verify_ajax_request() {
		// Bail if user does not have permission
		if ( ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			return false;
		}

		// Bail if nonce verification fails
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST[ $this->get_config_value( 'nonce_param' ) ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->get_config_value( 'nonce_param' ) ] ) ), $this->get_config_value( 'ajax_nonce_action' ) ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Advance the guided setup to the next step.
	 *
	 * @param   string  $next_step  Next step slug.
	 */
	public function advance_step( $next_step ) {
		$guided_setup_id = $this->get_guided_setup_id();

		// Save the next step as the max reached step in the slug progress store
		if ( ! empty( $guided_setup_id ) ) {
			self::set_current_step_for_slug( $guided_setup_id, $next_step );
			return;
		}

		update_option( $this->get_config_value( 'current_step_option' ), $next_step, true );
	}


	/**
	 * Maybe redirect to the guided setup after first plugin activation.
	 */
	public function maybe_redirect_after_plugin_activation() {
		// Bail if transient is not set
		if ( ! get_transient( $this->get_config_value( 'activation_redirect_transient' ) ) ) { return; }

		// Initialize variables
		$do_redirect = true;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Maybe set to skip redirecting based on the current page and user capabilities
		if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( $this->get_config_value( 'capability' ) ) ) {
			$do_redirect = false;
		}

		// Avoid redirecting when activating multiple plugins at the same time,
		// or when already on the guided setup page.
		if ( $this->page_slug === $current_page || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_transient( $this->get_config_value( 'activation_redirect_transient' ) );
			$do_redirect = false;
		}

		// Maybe redirect to the guided setup
		if ( $do_redirect ) {
			// Delete transient to prevent redirecting again
			delete_transient( $this->get_config_value( 'activation_redirect_transient' ) );

			// Redirect to the guided setup
			wp_safe_redirect( $this->get_guided_setup_url() );
			exit;
		}
	}


	/**
	 * AJAX handler for saving guided setup sub-step progress.
	 */
	public function ajax_save_sub_step_progress() {
		// Bail if AJAX request is not valid
		if ( ! $this->verify_ajax_request() ) {
			wp_die( 0 );
		}

		// Get sanitized request parameters
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$step_slug = isset( $_POST['step_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['step_slug'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sub_step_id = isset( $_POST['sub_step_id'] ) ? sanitize_text_field( wp_unslash( $_POST['sub_step_id'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$progress_action = isset( $_POST['progress_action'] ) ? sanitize_text_field( wp_unslash( $_POST['progress_action'] ) ) : '';

		// Bail if step slug, sub-step ID, or progress action is empty
		if ( empty( $step_slug ) || empty( $sub_step_id ) || empty( $progress_action ) ) {
			wp_send_json_error();
		}

		// Mark sub-step as complete
		if ( 'complete' === $progress_action ) {
			$state_updates = array(
				'completed' => true,
			);

			if ( ! $this->save_sub_step_state( $step_slug, $sub_step_id, $state_updates ) ) {
				wp_send_json_error();
			}
		}
		// Mark sub-step as incomplete
		elseif ( 'incomplete' === $progress_action ) {
			if ( ! $this->mark_sub_step_incomplete( $step_slug, $sub_step_id ) ) {
				wp_send_json_error();
			}
		}
		// Bail if progress action is invalid
		else {
			wp_send_json_error();
		}

		// Send success response with updated progress state
		wp_send_json_success(
			array(
				'subStepProgress'     => $this->get_sub_step_progress(),
				'maxReachedStep'      => $this->get_max_reached_step(),
				'maxReachedStepIndex' => $this->get_max_reached_step_index(),
			)
		);
	}


	/**
	 * Add admin body class on the guided setup page.
	 *
	 * @param   string  $classes  Admin body classes.
	 *
	 * @return  string
	 */
	public function add_admin_body_class( $classes ) {
		// Bail if not on the guided setup page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) || $this->page_slug !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return $classes;
		}

		return $classes . ' ' . $this->get_config_value( 'body_class' );
	}


	/**
	 * Get the CSS class used to show a guided setup card on page load.
	 *
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  string
	 */
	public function get_step_card_visibility_class( $step_slug ) {
		return $this->current_step === $step_slug ? 'fc-setup-card--visible' : '';
	}


	/**
	 * Get guided setup steps.
	 *
	 * @return  array
	 */
	public function get_steps() {
		$steps = array();

		foreach ( $this->get_step_definitions() as $step_slug => $step_definition ) {
			if ( ! is_array( $step_definition ) || empty( $step_definition['label'] ) ) {
				continue;
			}

			$steps[ $step_slug ] = $step_definition['label'];
		}

		return $steps;
	}


	/**
	 * Get guided setup sub-steps for one or all steps.
	 *
	 * @param   string|null  $step_slug  Optional. Step slug.
	 *
	 * @return  array
	 */
	public function get_sub_steps( $step_slug = null ) {
		$sub_steps = apply_filters(
			'fc_guided_setup_sub_steps',
			$this->get_sub_step_definitions(),
			$this->get_config_value( 'guided_setup_id' )
		);

		if ( null === $step_slug ) {
			return $sub_steps;
		}

		return isset( $sub_steps[ $step_slug ] ) ? $sub_steps[ $step_slug ] : array();
	}



	/**
	 * Sanitize a guided setup identifier for progress storage.
	 *
	 * @param   string  $guided_setup_id  Guided setup identifier.
	 *
	 * @return  string
	 */
	public static function sanitize_guided_setup_id( $guided_setup_id ) {
		$guided_setup_id = sanitize_key( $guided_setup_id );

		// Bail if identifier is empty after sanitization
		if ( empty( $guided_setup_id ) ) {
			return '';
		}

		return $guided_setup_id;
	}


	/**
	 * Get all guided setup progress entries keyed by slug.
	 *
	 * @return  array
	 */
	public static function get_all_progress() {
		$all_progress = get_option( self::PROGRESS_OPTION, array() );

		return is_array( $all_progress ) ? $all_progress : array();
	}


	/**
	 * Get guided setup progress for one slug.
	 *
	 * @param   string  $guided_setup_id  Guided setup identifier.
	 *
	 * @return  array
	 */
	public static function get_progress_for_slug( $guided_setup_id ) {
		$guided_setup_id = self::sanitize_guided_setup_id( $guided_setup_id );

		// Bail if identifier is empty
		if ( empty( $guided_setup_id ) ) {
			return array(
				'current_step'      => '',
				'sub_step_progress' => array(),
				'completed_at'      => '',
			);
		}

		$all_progress = self::get_all_progress();
		$progress     = isset( $all_progress[ $guided_setup_id ] ) && is_array( $all_progress[ $guided_setup_id ] )
			? $all_progress[ $guided_setup_id ]
			: array();

		return wp_parse_args(
			$progress,
			array(
				'current_step'      => '',
				'sub_step_progress' => array(),
				'completed_at'      => '',
			)
		);
	}


	/**
	 * Merge and save guided setup progress for one slug.
	 *
	 * @param   string  $guided_setup_id  Guided setup identifier.
	 * @param   array   $progress         Progress values to merge.
	 *
	 * @return  bool
	 */
	public static function set_progress_for_slug( $guided_setup_id, $progress ) {
		$guided_setup_id = self::sanitize_guided_setup_id( $guided_setup_id );

		// Bail if identifier is empty
		if ( empty( $guided_setup_id ) || ! is_array( $progress ) ) {
			return false;
		}

		$all_progress = self::get_all_progress();
		$existing     = self::get_progress_for_slug( $guided_setup_id );

		$all_progress[ $guided_setup_id ] = array_merge( $existing, $progress );

		$updated = update_option( self::PROGRESS_OPTION, $all_progress, false );

		// `update_option()` returns false when the stored value is unchanged.
		if ( false === $updated ) {
			return self::get_all_progress() == $all_progress; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Intentional array value compare.
		}

		return true;
	}


	/**
	 * Save the current step for a guided setup slug.
	 *
	 * @param   string  $slug       Guided setup identifier.
	 * @param   string  $step_slug  Step slug.
	 *
	 * @return  bool
	 */
	public static function set_current_step_for_slug( $slug, $step_slug ) {
		return self::set_progress_for_slug(
			$slug,
			array(
				'current_step' => sanitize_text_field( $step_slug ),
			)
		);
	}


	/**
	 * Get the saved current step for a guided setup slug.
	 *
	 * @param   string  $slug  Guided setup identifier.
	 *
	 * @return  string
	 */
	public static function get_current_step_for_slug( $slug ) {
		$progress = self::get_progress_for_slug( $slug );

		return isset( $progress['current_step'] ) ? (string) $progress['current_step'] : '';
	}


	/**
	 * Get saved sub-step progress for a guided setup slug.
	 *
	 * @param   string  $slug  Guided setup identifier.
	 *
	 * @return  array
	 */
	public static function get_sub_step_progress_for_slug( $slug ) {
		$progress = self::get_progress_for_slug( $slug );

		if ( ! isset( $progress['sub_step_progress'] ) || ! is_array( $progress['sub_step_progress'] ) ) {
			return array();
		}

		return $progress['sub_step_progress'];
	}


	/**
	 * Save sub-step progress for a guided setup slug.
	 *
	 * @param   string  $slug      Guided setup identifier.
	 * @param   array   $progress  Sub-step progress.
	 *
	 * @return  bool
	 */
	public static function set_sub_step_progress_for_slug( $slug, $progress ) {
		if ( ! is_array( $progress ) ) {
			return false;
		}

		return self::set_progress_for_slug(
			$slug,
			array(
				'sub_step_progress' => $progress,
			)
		);
	}


	/**
	 * Remove guided setup progress for one slug.
	 *
	 * @param   string  $slug  Guided setup identifier.
	 *
	 * @return  bool
	 */
	public static function clear_progress_for_slug( $slug ) {
		$slug = self::sanitize_guided_setup_id( $slug );

		// Bail if identifier is empty
		if ( empty( $slug ) ) {
			return false;
		}

		$all_progress = self::get_all_progress();

		// Bail if slug has no saved progress
		if ( ! isset( $all_progress[ $slug ] ) ) {
			return true;
		}

		unset( $all_progress[ $slug ] );

		$updated = update_option( self::PROGRESS_OPTION, $all_progress, false );

		// `update_option()` returns false when the stored value is unchanged.
		if ( false === $updated ) {
			$remaining = self::get_all_progress();
			return ! isset( $remaining[ $slug ] );
		}

		return true;
	}


	/**
	 * Read a guided setup option.
	 *
	 * @since 5.0.0
	 *
	 * @param   string  $option_name  Option name.
	 * @param   mixed   $default      Value when the option does not exist.
	 */
	public function get_guided_setup_stored_option( $option_name, $default = false ) {
		$stored = get_option( $option_name, null );

		// Use the current option when it has been saved
		if ( null !== $stored ) {
			return $stored;
		}

		return $default;
	}

	/**
	 * Delete a guided setup option.
	 *
	 * @since 5.0.0
	 *
	 * @param   string  $option_name  Option name.
	 */
	public function delete_guided_setup_stored_option( $option_name ) {
		delete_option( $option_name );
	}

}

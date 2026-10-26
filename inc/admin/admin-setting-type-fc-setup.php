<?php
defined( 'ABSPATH' ) || exit;

/**
 * Setup checklist card for the Dashboard tab of the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Setup extends FluidCheckout {

	/**
	 * Option key for completed getting-started checklist steps.
	 */
	const CHECKLIST_OPTION = 'fc_setup_checklist_completed';



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

		// Drop stored completion for settings-driven and invalid stored steps
		add_action( 'admin_init', array( $this, 'maybe_prune_stored_non_visit_checklist_steps' ), 14 );

		// Drop stored completion for add-on steps when the add-on plugin is inactive
		add_action( 'admin_init', array( $this, 'maybe_prune_inactive_add_on_checklist_steps' ), 15 );

		// Mark checklist steps when visiting related settings tabs
		add_action( 'admin_init', array( $this, 'maybe_mark_checklist_steps_from_current_tab' ), 20 );

		// Mark checklist steps when switching tabs in the settings SPA
		add_action( 'wp_ajax_fc_setup_checklist_mark_tab', array( $this, 'ajax_mark_checklist_step_from_tab' ), 10 );

		// Mark checklist steps when saving settings on a mapped tab
		add_action( 'fc_admin_settings_saved', array( $this, 'maybe_mark_checklist_step_from_saved_tab' ), 10, 1 );
	}



	/**
	 * Get the getting-started checklist steps.
	 *
	 * @return  array[]
	 */
	public function get_checklist_steps() {
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();

		$steps = array(
			array(
				'id'    => 'checkout',
				/* translators: %s: Checkout settings URL. */
				'label' => sprintf( __( 'Set up layout and design on the <a href="%s">checkout options</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'checkout' ) ) ),
			),
			array(
				'id'    => 'customer_accounts',
				/* translators: %s: Customer accounts settings URL. */
				'label' => sprintf( __( 'Enable guest checkout and/or review <a href="%s">customer accounts settings</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'customer_accounts' ) ) ),
			),
			array(
				'id'         => 'optimized_pages',
				/* translators: %1$s: Cart settings URL. %2$s: Thank you settings URL. %3$s: Order pay settings URL. */
				'label'      => sprintf( __( 'Enable optimized <a href="%1$s">cart</a>, <a href="%2$s">thank you</a> and <a href="%3$s">order pay</a> pages.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'cart' ) ), esc_url( $settings_page->get_settings_url( 'order_received' ) ), esc_url( $settings_page->get_settings_url( 'order_pay' ) ) ),
				'badge'      => 'setup-optimized-pages',
			),
			array(
				'id'         => 'address_autocomplete',
				/* translators: %s: Address autocomplete settings URL. */
				'label'      => sprintf( __( 'Enable <a href="%s">address autocomplete</a> from Google Maps.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'address_autocomplete' ) ) ),
				'badge'      => 'setup-address-autocomplete',
			),
			array(
				'id'         => 'address_book',
				/* translators: %s: Address book settings URL. */
				'label'      => sprintf( __( 'Enable multiple saved addresses with <a href="%s">address book</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'address_book' ) ) ),
				'badge'      => 'setup-address-book',
			),
			array(
				'id'    => 'integrations',
				/* translators: %s: Integrations settings URL. */
				'label' => sprintf( __( 'Check if there are any <a href="%s">integration options</a> available for the theme or other plugins you have installed.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'integrations' ) ) ),
			),
		);

		// Hide usage tracking checklist step when turned off by PHP constant
		if ( ! class_exists( 'FC_Telemetry_Client' ) || ! FC_Telemetry_Client::is_disabled_by_constant() ) {
			$steps[] = array(
				'id'    => 'tracking',
				/* translators: %s: Tools settings URL. */
				'label' => sprintf( __( 'Get faster support and help us improve compatibility and measure impact. Usage tracking is optional and OFF by default. <a href="%s">Review usage tracking</a> in the tools settings. You can preview the data and turn it off at any time.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'tools' ) ) ),
			);
		}

		/**
		 * Filters the getting-started checklist steps on the Dashboard.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'fc_setup_checklist_steps', $steps );
	}



	/**
	 * Get completed checklist step ids.
	 *
	 * @return  string[]
	 */
	public function get_completed_steps() {
		$completed = get_option( self::CHECKLIST_OPTION, array() );

		if ( ! is_array( $completed ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_key', $completed ) ) );
	}



	/**
	 * Map getting-started checklist step ids to their add-on plugin basename.
	 *
	 * @param   string  $step_id  Step id.
	 * @return  string  Plugin basename, or empty when the step is not tied to an add-on.
	 */
	public function get_checklist_add_on_plugin_file( $step_id ) {
		$step_id = sanitize_key( $step_id );

		$map = array(
			'fc_gaa_address_autocomplete' => 'fc-google-address-autocomplete/fc-google-address-autocomplete.php',
		);

		return array_key_exists( $step_id, $map ) ? $map[ $step_id ] : '';
	}



	/**
	 * Whether the add-on plugin for a checklist step is active.
	 *
	 * @param   string  $step_id  Step id.
	 * @return  bool
	 */
	public function is_checklist_add_on_plugin_active( $step_id ) {
		$plugin_file = $this->get_checklist_add_on_plugin_file( $step_id );

		// Steps without an add-on mapping are not gated by plugin activation
		if ( '' === $plugin_file ) {
			return true;
		}

		return FluidCheckout::instance()->is_plugin_activated( $plugin_file );
	}



	/**
	 * Whether a checklist step is completed.
	 *
	 * @param   string  $step_id  Step id.
	 * @return  bool
	 */
	public function is_step_completed( $step_id ) {
		$step_id = sanitize_key( $step_id );

		// Usage tracking is complete only when reporting is enabled
		if ( 'tracking' === $step_id ) {
			return 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_enabled' );
		}

		// PRO feature steps: only complete while PRO is active, even if options stay enabled in the database
		if ( in_array( $step_id, array( 'optimized_pages', 'address_autocomplete', 'address_book' ), true ) ) {
			// Bail if PRO is not activated — keep the step number visible
			if ( ! FluidCheckout::instance()->is_pro_activated() ) { return false; }

			$settings = FluidCheckout_Settings::instance();

			if ( 'optimized_pages' === $step_id ) {
				return 'yes' === $settings->get_option( 'fc_pro_enable_cart_page' )
					&& 'yes' === $settings->get_option( 'fc_pro_enable_order_received' )
					&& 'yes' === $settings->get_option( 'fc_pro_enable_order_pay' );
			}

			if ( 'address_autocomplete' === $step_id ) {
				return $this->is_address_autocomplete_step_complete( $settings );
			}

			if ( 'address_book' === $step_id ) {
				return 'yes' === $settings->get_option( 'fc_pro_enable_address_book' );
			}
		}

		// Address Autocomplete (legacy step id on the Dashboard)
		if ( 'fc_gaa_address_autocomplete' === $step_id ) {
			return $this->is_address_autocomplete_step_complete( FluidCheckout_Settings::instance() );
		}

		// Add-on steps stay incomplete while the add-on plugin is inactive
		if ( ! $this->is_checklist_add_on_plugin_active( $step_id ) ) {
			return false;
		}

		return in_array( $step_id, $this->get_completed_steps(), true );
	}



	/**
	 * Get completion state for all getting-started checklist steps.
	 *
	 * @return  array<string, bool>
	 */
	public function get_checklist_steps_completion_state() {
		$state = array();

		foreach ( $this->get_checklist_steps() as $step ) {
			$step_id = isset( $step['id'] ) ? sanitize_key( $step['id'] ) : '';

			// Skip steps without an id
			if ( empty( $step_id ) ) { continue; }

			$state[ $step_id ] = $this->is_step_completed( $step_id );
		}

		return $state;
	}



	/**
	 * Whether the address autocomplete checklist step is complete.
	 * Brasil API alone is enough. Google Maps requires the feature enabled and a successfully tested API key.
	 *
	 * @param   FluidCheckout_Settings  $settings  Settings instance.
	 * @return  bool
	 */
	public function is_address_autocomplete_step_complete( $settings ) {
		// Brasil API does not need a Google API key
		if ( 'yes' === $settings->get_option( 'fc_gaa_enabled_brasil_api' ) ) {
			return true;
		}

		// Bail if Google address autocomplete is not enabled
		if ( 'yes' !== $settings->get_option( 'fc_gaa_enabled' ) ) { return false; }

		$api_key = $settings->get_option( 'fc_gaa_google_places_api_key' );

		// Bail if the Google API key is empty
		if ( empty( $api_key ) ) { return false; }

		$validated_hash = $settings->get_option( 'fc_gaa_google_places_api_key_validated_hash' );

		// Bail if the saved key has not been tested successfully
		if ( empty( $validated_hash ) ) { return false; }

		return hash_equals( (string) $validated_hash, wp_hash( (string) $api_key ) );
	}



	/**
	 * Mark a checklist step as completed.
	 *
	 * @param   string  $step_id  Step id.
	 */
	public function mark_step_completed( $step_id ) {
		$step_id = sanitize_key( $step_id );

		// Bail if step id is empty
		if ( empty( $step_id ) ) { return; }

		$visit_marked_steps = $this->get_visit_marked_checklist_step_ids();

		// Visit-marked steps only check stored completion so a tab visit always persists
		if ( in_array( $step_id, $visit_marked_steps, true ) ) {
			// Bail if already stored as completed
			if ( in_array( $step_id, $this->get_completed_steps(), true ) ) { return; }
		}
		// Otherwise bail if already completed via settings or stored state
		else if ( $this->is_step_completed( $step_id ) ) {
			return;
		}

		$completed   = $this->get_completed_steps();
		$completed[] = $step_id;
		$completed   = array_values( array_unique( $completed ) );

		update_option( self::CHECKLIST_OPTION, $completed, false );
	}



	/**
	 * Remove stored completion for add-on checklist steps when their plugin is inactive.
	 */
	public function maybe_prune_inactive_add_on_checklist_steps() {
		$completed = $this->get_completed_steps();
		$pruned    = array();

		foreach ( $completed as $step_id ) {
			// Keep steps that are not add-on gated or whose add-on is still active
			if ( $this->is_checklist_add_on_plugin_active( $step_id ) ) {
				$pruned[] = $step_id;
			}
		}

		// Bail if nothing changed
		if ( count( $pruned ) === count( $completed ) ) { return; }

		update_option( self::CHECKLIST_OPTION, array_values( array_unique( $pruned ) ), false );
	}



	/**
	 * Map settings tab slugs to getting-started checklist step ids.
	 *
	 * @return  array<string, string>
	 */
	public function get_checklist_tab_to_step_map() {
		$tab_to_step = array(
			'checkout'          => 'checkout',
			'customer_accounts' => 'customer_accounts',
			'integrations'      => 'integrations',
		);

		/**
		 * Filters settings tabs that mark a getting-started checklist step when visited.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'fc_setup_checklist_tab_to_step', $tab_to_step );
	}



	/**
	 * Checklist step ids that may be stored when visiting a settings tab.
	 *
	 * @return  string[]
	 */
	public function get_visit_marked_checklist_step_ids() {
		return array_values( array_unique( $this->get_checklist_tab_to_step_map() ) );
	}



	/**
	 * Checklist step ids derived from saved settings, not tab visits.
	 *
	 * @return  string[]
	 */
	public function get_settings_derived_checklist_step_ids() {
		return array(
			'tracking',
			'optimized_pages',
			'address_autocomplete',
			'address_book',
			'fc_gaa_address_autocomplete',
		);
	}



	/**
	 * Remove settings-driven step ids mistakenly stored in the visit checklist option.
	 */
	public function maybe_prune_stored_non_visit_checklist_steps() {
		$completed   = $this->get_completed_steps();
		$allowed     = $this->get_visit_marked_checklist_step_ids();
		$pruned      = array();

		foreach ( $completed as $step_id ) {
			// Keep only visit-marked step ids in storage
			if ( in_array( $step_id, $allowed, true ) ) {
				$pruned[] = $step_id;
			}
		}

		// Bail if nothing changed
		if ( count( $pruned ) === count( $completed ) ) { return; }

		update_option( self::CHECKLIST_OPTION, array_values( array_unique( $pruned ) ), false );
	}



	/**
	 * Maybe mark a checklist step from a settings tab slug.
	 *
	 * @param   string  $tab  Settings tab slug.
	 */
	public function maybe_mark_checklist_step_from_tab( $tab ) {
		$tab = sanitize_key( $tab );

		$tab_to_step = $this->get_checklist_tab_to_step_map();

		// Bail if the tab does not map to a checklist step
		if ( ! array_key_exists( $tab, $tab_to_step ) ) { return; }

		$this->mark_step_completed( $tab_to_step[ $tab ] );
	}



	/**
	 * Maybe mark a checklist step after settings are saved on a mapped tab.
	 *
	 * @param   string  $tab  Settings tab slug that was active when saving.
	 */
	public function maybe_mark_checklist_step_from_saved_tab( $tab ) {
		$this->maybe_mark_checklist_step_from_tab( $tab );
	}



	/**
	 * Maybe mark checklist steps when visiting related settings tabs.
	 */
	public function maybe_mark_checklist_steps_from_current_tab() {
		// Bail if settings page class is missing
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return; }

		$settings_page = FluidCheckout_Admin_Settings_Page::instance();

		// Bail if not on the Fluid Checkout settings page
		if ( ! $settings_page->is_settings_page() ) { return; }

		// Bail if user cannot manage settings
		if ( ! current_user_can( FluidCheckout_Admin_Settings_Page::CAPABILITY ) ) { return; }

		$this->maybe_mark_checklist_step_from_tab( $settings_page->get_current_tab() );
	}



	/**
	 * AJAX: sync getting-started checklist state when a settings tab is activated.
	 * Marks visit-based steps when the tab maps to one, then returns every step state.
	 */
	public function ajax_mark_checklist_step_from_tab() {
		// Bail if user cannot manage settings
		if ( ! current_user_can( FluidCheckout_Admin_Settings_Page::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'fluid-checkout' ) ), 403 );
		}

		check_ajax_referer( 'fc_setup_checklist_mark_tab', 'nonce' );

		$tab = sanitize_key( wp_unslash( $_POST['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Bail if tab slug is missing
		if ( empty( $tab ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing settings tab.', 'fluid-checkout' ) ) );
		}

		$this->maybe_mark_checklist_step_from_tab( $tab );

		wp_send_json_success(
			array(
				'checklist_state' => $this->get_checklist_steps_completion_state(),
			)
		);
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$steps            = $this->get_checklist_steps();
		$guided_setup_url = class_exists( 'FluidCheckout_Admin_GuidedSetup_FluidCheckout' )
			? FluidCheckout_Admin_GuidedSetup_FluidCheckout::instance()->get_guided_setup_url()
			: FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' );
		?>
		<div class="fc-settings-card fc-settings-card--setup">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'Getting started', 'fluid-checkout' ) ); ?></h3>
			</div>

			<div class="fc-settings-card__inner">
				<p><?php echo wp_kses_post( __( '<strong>Great! Your checkout page is now running on Fluid Checkout.</strong> Here are a few steps to get started:', 'fluid-checkout' ) ); ?></p>

				<ol class="fc-dashboard-checklist">
					<?php foreach ( $steps as $step ) : ?>
						<?php
						$step_id     = isset( $step['id'] ) ? sanitize_key( $step['id'] ) : '';
						$is_complete = ! empty( $step_id ) && $this->is_step_completed( $step_id );
						$item_class  = 'fc-dashboard-checklist__item';
						if ( $is_complete ) {
							$item_class .= ' is-completed';
						}

						$badge_html = '';
						// Maybe show a PRO feature badge with pricing link
						if ( ! empty( $step['badge'] ) ) {
							$badge_html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( $step['badge'] );
						}
						?>
						<li class="<?php echo esc_attr( $item_class ); ?>"<?php echo ! empty( $step_id ) ? ' data-fc-setup-checklist-step="' . esc_attr( $step_id ) . '"' : ''; ?>>
							<span class="fc-dashboard-checklist__marker" aria-hidden="true"></span>
							<span class="fc-dashboard-checklist__label"><?php echo wp_kses_post( isset( $step['label'] ) ? $step['label'] : '' ); ?></span>
							<?php if ( $is_complete ) : ?>
								<span class="screen-reader-text" data-fc-setup-checklist-completed-label><?php echo esc_html( __( 'Completed', 'fluid-checkout' ) ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $badge_html ) ) : ?>
								<span class="fc-dashboard-checklist__promo"><?php echo wp_kses_post( $badge_html ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<div class="fc-settings-card__footer">
				<a class="fc-settings-button fc-settings-button--primary" href="<?php echo esc_url( $guided_setup_url ); ?>"><?php echo esc_html( __( 'Guided setup', 'fluid-checkout' ) ); ?></a>
			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Setup::instance();

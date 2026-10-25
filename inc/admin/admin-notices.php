<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notices.
 */
class FluidCheckout_AdminNotices extends FluidCheckout {

	/**
	 * Plugin prefix for the admin notices options.
	 */
	private static $plugin_prefix = 'fc';



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
		$this->load_notices();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'admin_notices', array( $this, 'display_notices' ), 10 );
		add_action( 'admin_init', array( $this, 'dismiss_notice' ), 10 );

		// Register assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// WooCommerce dependency notice (checked when notices are built, not at load time)
		add_filter( 'fc_admin_notices', array( $this, 'add_woocommerce_required_notice' ), 10 );
	}

	/**
	 * Load individual admin notice classes.
	 */
	private function load_notices() {
		require_once self::$directory_path . 'inc/admin/admin-notice-db-migrations.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-db-migrations-applied.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-telemetry.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-settings-legacy-cleanup.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-new-settings-page.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-review-request.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-divi-checkout-layout-being-used.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-germanized-pro-multistep-enabled.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-woocommerce-checkout-manager-enabled.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-coderockz-delivery-plugins-detected.php';
	}



	/**
	 * Register admin notice assets.
	 */
	public function register_assets() {
		// Bail if checkout assets are not loaded yet
		if ( ! class_exists( 'FluidCheckout_Enqueue' ) ) { return; }

		// Styles
		wp_register_style( 'fc-admin-notices', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-notices' ), array(), NULL );
	}

	/**
	 * Enqueue admin notice assets.
	 */
	public function enqueue_assets() {
		// Styles
		wp_enqueue_style( 'fc-admin-notices' );
	}

	/**
	 * Maybe enqueue admin notice assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Get the plugin icon URL for admin notices.
	 *
	 * @return  string
	 */
	public function get_plugin_icon_url() {
		$icon_url = self::$directory_url . 'images/admin/fluid-checkout-icon.svg';

		/**
		 * Filters the plugin icon URL for Fluid Checkout Lite admin notices.
		 *
		 * @since 5.0.0
		 *
		 * @param  string  $icon_url  Default Lite plugin icon URL.
		 */
		return apply_filters( 'fc_admin_notice_icon_url', $icon_url );
	}



	/**
	 * Output a single admin notice.
	 *
	 * @param   array  $notice  Notice data.
	 */
	public function output_notice( $notice ) {
		// Initialize notice classes
		$notice_classes = array(
			'notice',
			esc_attr( self::$plugin_prefix ) . '-admin-notice',
		);

		// Add error class if notice is an error
		if ( true === $notice['error'] ) {
			$notice_classes[] = 'notice-error';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $notice_classes ) ); ?>">
			<div class="fc-admin-notice__inner">
				<div class="fc-admin-notice__icon">
					<img src="<?php echo esc_url( $this->get_plugin_icon_url() ); ?>" alt="" width="48" height="48" />
				</div>

				<div class="fc-admin-notice__content">
					<?php if ( ! empty( $notice['title'] ) ) : ?>
						<p><strong><?php echo wp_kses_post( $notice['title'] ); ?></strong></p>
					<?php endif; ?>

					<?php if ( ! empty( $notice['description'] ) ) : ?>
						<?php if ( $notice['paragraph_wrap'] ) : ?>
							<p><?php echo wp_kses_post( $notice['description'] ); ?></p>
						<?php else : ?>
							<?php echo wp_kses_post( $notice['description'] ); ?>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( is_array( $notice['actions'] ) && count( $notice['actions'] ) > 0 ) { ?>
						<p class="submit"><?php echo wp_kses_post( implode( ' ', $notice['actions'] ) ); ?></p>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Display notices if they exist.
	 */
	public function display_notices() {
		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail on guided setup screens (no FC or third-party notices)
		if ( class_exists( 'FluidCheckout_Admin_GuidedSetup' ) && FluidCheckout_Admin_GuidedSetup::is_guided_setup_admin_screen() ) { return; }

		/**
		 * Filters the admin notices displayed by Fluid Checkout.
		 *
		 * The notice list is built from the plugin prefix stored in
		 * `FluidCheckout_AdminNotices::$plugin_prefix`, which is `fc`.
		 * Possible hook names include:
		 *
		 * - `fc_admin_notices`
		 *
		 * @since 2.3.2
		 *
		 * Each notice item may include `dismissible` (bool). Default true.
		 * `dismissable` is a deprecated alias kept for backwards compatibility.
		 * It is used only when `dismissible` is omitted; if both are set, `dismissible` wins.
		 *
		 * Each notice item may include `visible` (bool). Default true. When false, the notice
		 * stays registered for dismiss handling but is not output on the current screen.
		 *
		 * @param array $value Value to filter. Default empty array.
		 */
		$notices = apply_filters( self::$plugin_prefix . '_admin_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		$default_options = array(
			'name'            => null,
			'title'           => '',
			'description'     => '',
			'error'           => false,
			'actions'         => array(),
			'dismissible'     => true,
			'dismiss_label'   => __( 'Don\'t show this again', 'fluid-checkout' ),
			'paragraph_wrap'  => true,
			'visible'         => true,
		);

		foreach ( $notices as $notice ) {
			$has_dismissible = is_array( $notice ) && array_key_exists( 'dismissible', $notice );
			$notice = wp_parse_args( $notice, $default_options );

			// `dismissable` is a deprecated alias of `dismissible`. The correct key wins when both are set.
			if ( ! $has_dismissible && array_key_exists( 'dismissable', $notice ) ) {
				$notice['dismissible'] = $notice['dismissable'];
			}

			// Maybe skip notice if it's already dismissed
			if ( is_null( $notice['name'] ) || $this->is_dismissed( $notice['name'] ) ) { continue; }

			// Maybe skip output while keeping the notice registered for dismiss handling
			if ( ! $notice['visible'] ) { continue; }

			// Maybe add dismiss action
			if ( $notice['dismissible'] ) {
				$notice['actions'][] = '<a class="fc-admin-notice__dismiss" href="' . esc_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'dismiss_notice', self::$plugin_prefix . '_notice' => $notice['name'], '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ) . '">' . esc_html( $notice['dismiss_label'] ) . '</a>';
			}

			$this->output_notice( $notice );
		}
	}



	/**
	 * Check if notice is dismissed.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function is_dismissed( $name ) {
		// Need to get option directly as the Lite plugin might not be activated at this point
		$is_dismissed = (bool) get_option( self::$plugin_prefix . '_dismissed_notice_' . $name, false );

		/**
		 * Filters whether a Fluid Checkout admin notice is dismissed.
		 *
		 * Used by PRO to honour legacy dismiss options from PRO and former
		 * standalone add-ons (Address Book, VAT Assistant, Google Address Autocomplete).
		 *
		 * @param  bool    $is_dismissed  Whether the notice is dismissed via the Lite option.
		 * @param  string  $name          Notice name.
		 */
		return (bool) apply_filters( 'fc_is_admin_notice_dismissed', $is_dismissed, $name );
	}



	/**
	 * Dismiss notices.
	 */
	public function dismiss_notice() {
		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'dismiss-notice' ) ) { return; }

		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail if not dismissing notices
		if ( ! array_key_exists( self::$plugin_prefix . '_action', $_GET ) || 'dismiss_notice' !== sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_action' ] ) ) || ! array_key_exists( self::$plugin_prefix . '_notice', $_GET ) || empty( sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_notice' ] ) ) ) ) { return; }

		// Get notice name
		$name = sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_notice' ] ) );

		// Bail if notice name is not registered
		$registered_notices = apply_filters( self::$plugin_prefix . '_admin_notices', array() );
		$registered_names = array();
		foreach ( $registered_notices as $notice ) {
			// Skip notices without a name
			if ( empty( $notice['name'] ) ) { continue; }
			$registered_names[] = $notice['name'];
		}
		if ( ! in_array( $name, $registered_names, true ) ) { return; }

		// Update notice dismiss option
		update_option( self::$plugin_prefix . '_dismissed_notice_' . $name, 1 );
	}



	/**
	 * Display an admin notice regarding the need for WooCommerce to be active.
	 *
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_woocommerce_required_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Bail if WooCommerce is already active (check at display time; WC may not be loaded yet during plugin bootstrap)
		if ( $this->is_woocommerce_activated() ) { return $notices; }

		$required_plugin_name = __( 'WooCommerce', 'fluid-checkout' );
		$required_plugin_path_name = 'woocommerce/woocommerce.php';
		$required_plugin_search_term = 'woocommerce';
		$action_label = wp_kses_post( __( 'Go to Plugin Search', 'fluid-checkout' ) );
		$action_url = admin_url( 'plugin-install.php?s=' . $required_plugin_search_term . '&tab=search&type=term' );

		if ( ! is_wp_error( validate_plugin( $required_plugin_path_name ) ) ) {
			/** translators: %s: Required plugin name. */
			$action_label = wp_kses_post( sprintf( __( 'Activate %s', 'fluid-checkout' ), $required_plugin_name ) );
			$action_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $required_plugin_path_name ), 'activate-plugin_' . $required_plugin_path_name );
		}

		/** translators: %1$s: Plugin name, %2$s: Required plugin name, %3$s: Action label, %4$s: Action URL. */
		$description = wp_kses_post( sprintf( __( '<strong>%1$s</strong> requires <strong>%2$s</strong> to be installed and activated. <a href="%4$s">%3$s</a>', 'fluid-checkout' ),
			self::$plugin,
			$required_plugin_name,
			$action_label,
			$action_url
		) );

		$notices[] = array(
			'name'        => 'woocommerce_required',
			'error'       => true,
			'description' => $description,
			'dismissible' => false,
		);

		return $notices;
	}

}

FluidCheckout_AdminNotices::instance();

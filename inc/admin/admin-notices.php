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
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'admin_notices', array( $this, 'display_notices' ), 10 );
		add_action( 'admin_init', array( $this, 'dismiss_notice' ), 10 );
	}



	/**
	 * Display notices if they exist.
	 */
	public function display_notices() {
		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		$notices = apply_filters( self::$plugin_prefix . '_admin_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		$default_options = array(
			'name'           => null,
			'title'          => '',
			'description'    => '',
			'error'          => false,
			'actions'        => array(),
			'dismissable'    => true,
			'dismiss_label'  => __( 'Don\'t show this again', 'fluid-checkout' ),
		);

		foreach ( $notices as $notice ) {
			$notice = wp_parse_args( $notice, $default_options );

			// Maybe skip notice if it's already dismissed
			if ( is_null( $notice['name'] ) || $this->is_dismissed( $notice['name'] ) ) { continue; }

			// Maybe add dismiss action
			if ( $notice['dismissable'] ) {
				$notice['actions'][] = '<a href="' . esc_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'dismiss_notice', self::$plugin_prefix . '_notice' => $notice['name'], '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ) . '" style="margin: 0 20px;">' . $notice['dismiss_label'] . '</a>';
			}
			
			?>
			<div class="notice <?php echo esc_attr( self::$plugin_prefix ); ?>-admin-notice <?php echo $notice['error'] === true ? 'notice-error' : ''; ?>" <?php echo $notice['error'] === true ? '' : 'style="border-left-color: #0047e1;"'; ?>>
				<?php if ( ! empty( $notice['title'] ) ) : ?>
					<p><strong><?php echo wp_kses_post( $notice['title'] ); ?></strong></p>
				<?php endif; ?>

				<p><?php echo wp_kses_post( $notice['description'] ); ?></p>

				<?php if ( is_array( $notice['actions'] ) && count( $notice['actions'] ) > 0 ) { ?>
					<p><?php echo wp_kses_post( implode( ' ',  $notice['actions'] ) ); ?></p>
				<?php } ?>
			</div>
			<?php
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
		return (bool) get_option( self::$plugin_prefix . '_dismissed_notice_' . $name, false );
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

		// Update notice dismiss option
		$name = sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_notice' ] ) );
		update_option( self::$plugin_prefix . '_dismissed_notice_' . $name, 1 );
	}
	
}

FluidCheckout_AdminNotices::instance();

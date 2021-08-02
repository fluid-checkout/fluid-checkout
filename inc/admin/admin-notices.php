<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notices.
 */
class FluidCheckout_AdminNotices extends FluidCheckout {

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
	public static function display_notices() {
		$notices = apply_filters( 'fc_admin_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		$default_options = array(
			'name'        => null,
			'title'       => '',
			'description' => '',
			'error'       => false,
			'actions'     => array(),
			'dismissable' => true,
		);

		foreach ( $notices as $notice ) {
			$notice = wp_parse_args( $notice, $default_options );

			if ( is_null( $notice['name'] ) || self::is_dismissed( $notice['name'] ) ) {
				continue;
			}

			if ( $notice['dismissable'] ) {
				$notice['actions'][] = '<a href="' . esc_url( add_query_arg( array( 'fc_action' => 'dismiss_notice', 'fc_notice' => $notice['name'] ) ) ) . '">' . __( 'Dismiss Notice', 'fluid-checkout' ) . '</a>';
			}
			
			?>
			<div class="notice fc-admin-notice <?php echo $notice['error'] === true ? 'notice-error' : ''; ?>" <?php echo $notice['error'] === true ? '' : 'style="border-left-color: #0047e1;"'; ?>>
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
	public static function is_dismissed( $name ) {
		return (bool) get_option( 'fc_dismissed_notice_' . $name, false );
	}



	/**
	 * Dismiss notices.
	 */
	public static function dismiss_notice() {
		// Permissions check.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'fc_action', FILTER_SANITIZE_STRING );

		// Bail if not our notices.
		if ( 'dismiss_notice' !== $action ) {
			return;
		}

		// Get notice.
		$name = filter_input( INPUT_GET, 'fc_notice', FILTER_SANITIZE_STRING );

		if ( ! $name ) {
			return;
		}

		// Update notice dismiss option
		update_option( 'fc_dismissed_notice_' . $name, 1 );
	}
	
}

FluidCheckout_AdminNotices::instance();

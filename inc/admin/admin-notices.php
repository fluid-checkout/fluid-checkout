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
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_styles' ), 10 );
	}



	/**
	 * Maybe enqueue admin notice styles.
	 */
	public function maybe_enqueue_styles() {
		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Styles
		wp_enqueue_style( 'fc-admin-notices', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-notices' ), array(), null );
	}



	/**
	 * Get the plugin icon URL for admin notices.
	 *
	 * @return  string
	 */
	public function get_plugin_icon_url() {
		return self::$directory_url . 'images/fluid-checkout-icon.png';
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

		// Initialize notice style
		$notice_style = true === $notice['error'] ? '' : 'border-left-color: #0047e1;'; // TODO: Set color via CSS class
		?>
		<div class="<?php echo esc_attr( implode( ' ', $notice_classes ) ); ?>" <?php echo $notice_style ? 'style="' . esc_attr( $notice_style ) . '"' : ''; ?>>
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
			'dismissable'     => true,
			'dismiss_label'   => __( 'Don\'t show this again', 'fluid-checkout' ),
			'paragraph_wrap'  => true,
		);

		foreach ( $notices as $notice ) {
			$notice = wp_parse_args( $notice, $default_options );

			// Maybe skip notice if it's already dismissed
			if ( is_null( $notice['name'] ) || $this->is_dismissed( $notice['name'] ) ) { continue; }

			// Maybe add dismiss action
			if ( $notice['dismissable'] ) {
				$notice['actions'][] = '<a href="' . esc_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'dismiss_notice', self::$plugin_prefix . '_notice' => $notice['name'], '_wpnonce' => wp_create_nonce( 'dismiss-notice' ) ) ) ) . '" style="margin: 0 20px;">' . esc_html( $notice['dismiss_label'] ) . '</a>';
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

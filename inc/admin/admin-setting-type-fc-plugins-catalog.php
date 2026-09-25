<?php
defined( 'ABSPATH' ) || exit;

/**
 * Dashboard field type: more plugins from Fluid Checkout (separate products, not add-ons).
 */
class FluidCheckout_Admin_SettingType_PluginsCatalog extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_plugins_catalog', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Get the plugins catalog for the Dashboard “More plugins” list.
	 */
	public function get_plugins_catalog() {
		$directory_url = FluidCheckout::$directory_url;
		$icon_url      = $directory_url . 'images/admin/fluid-checkout-icon.svg';

		$catalog = array(
			array(
				'id'          => 'fc-conversion-kit',
				'item_class'  => 'fc-addons__item--wide',
				'plugin_file' => 'fc-conversion-kit/fc-conversion-kit.php',
				'plugin_slug' => 'fc-conversion-kit',
				'title'       => __( 'Fluid Conversion Kit', 'fluid-checkout' ),
				'subtitle'    => __( 'Conversion tools for shop and product pages.', 'fluid-checkout' ),
				'description' => __( 'Boost conversions across your store with <strong>countdowns</strong>, a <strong>free shipping bar</strong>, <strong>product badges</strong> (bestseller, discount, on-sale, backorder), plus <strong>add to cart from listing pages</strong> and <strong>stock meter / FOMO</strong> prompts — without changing cart, checkout, thank you, or order pay.', 'fluid-checkout' ),
				'image'       => $icon_url,
				'product_url' => 'https://fluidcheckout.com/fc-conversion-kit/',
			),
			array(
				'id'          => 'fc-paddle-payments',
				'plugin_file' => 'fc-paddle-payments/fc-paddle-payments.php',
				'plugin_slug' => 'fc-paddle-payments',
				'title'       => __( 'Paddle Payments for WooCommerce', 'fluid-checkout' ),
				'subtitle'    => __( 'Sell with Paddle as your merchant of record.', 'fluid-checkout' ),
				'description' => __( 'Accept payments through <strong>Paddle</strong> on your WooCommerce store, with taxes and compliance handled for you.', 'fluid-checkout' ),
				'image'       => $directory_url . 'images/admin/addons/fc-paddle-payments-icon.svg',
				'product_url' => 'https://fluidcheckout.com/fc-paddle-payments/',
			),
			array(
				'id'          => 'fc-licenses',
				'plugin_file' => 'fc-licenses/fc-licenses.php',
				'plugin_slug' => 'fc-licenses',
				'title'       => __( 'Fluid Licenses', 'fluid-checkout' ),
				'subtitle'    => __( 'License keys, updates, and plugin usage tracking.', 'fluid-checkout' ),
				'description' => __( 'Sell and manage <strong>software license keys</strong> on WooCommerce, with customer account downloads, automatic plugin updates, and <strong>plugin usage tracking</strong>.', 'fluid-checkout' ),
				'image'       => $icon_url,
				'product_url' => 'https://fluidcheckout.com/fc-licenses/',
			),
		);

		return apply_filters( 'fc_plugins_catalog', $catalog );
	}



	/**
	 * Build a product URL with Matomo tracking parameters.
	 *
	 * @param  string  $product_url  Base product page URL.
	 * @param  string  $plugin_id    Catalog item id used in `mtm_kwd`.
	 * @param  string  $action       Tracking action suffix (e.g. early-access, learn-more).
	 */
	private function get_tracked_product_url( $product_url, $plugin_id, $action ) {
		return add_query_arg(
			array(
				'mtm_campaign' => 'plugins-catalog',
				'mtm_kwd'      => $plugin_id . '-' . $action,
				'mtm_source'   => 'lite-plugin',
			),
			$product_url
		);
	}



	/**
	 * Output action buttons for a plugins catalog card.
	 *
	 * @param array $plugin Catalog item.
	 */
	private function output_plugin_actions( $plugin ) {
		$plugin_file = isset( $plugin[ 'plugin_file' ] ) ? $plugin[ 'plugin_file' ] : '';
		$plugin_id   = isset( $plugin[ 'id' ] ) ? $plugin[ 'id' ] : '';
		$product_url = isset( $plugin[ 'product_url' ] ) ? $plugin[ 'product_url' ] : '';

		// Bail if plugin file is missing
		if ( empty( $plugin_file ) ) { return; }

		$is_activated = FluidCheckout::instance()->is_plugin_activated( $plugin_file );
		$is_installed = FluidCheckout::instance()->is_plugin_installed( $plugin_file );

		// Show activated state when the plugin is already active
		if ( $is_activated ) :
			?>
			<a href="javascript:void(0);" class="button button--activated disabled"><?php echo esc_html( __( 'Activated', 'fluid-checkout' ) ); ?></a>
			<?php
		// Show activate when the plugin is installed but inactive
		elseif ( $is_installed ) :
			?>
			<button
				type="button"
				class="button fc-addons__item-action--activate"
				data-action="activate"
				data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
			><?php echo esc_html( __( 'Activate plugin', 'fluid-checkout' ) ); ?></button>
			<div class="fc-addons__item-action-notice" hidden></div>
			<?php
		// Otherwise show early access and learn more marketing actions
		else :
			$early_access_url = $this->get_tracked_product_url( $product_url, $plugin_id, 'early-access' );
			$learn_more_url   = $this->get_tracked_product_url( $product_url, $plugin_id, 'learn-more' );
			?>
			<a href="<?php echo esc_url( $early_access_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Get early access', 'fluid-checkout' ) ); ?></a>
			<a href="<?php echo esc_url( $learn_more_url ); ?>" class="fc-settings-button" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Learn more', 'fluid-checkout' ) ); ?></a>
			<?php
		endif;
	}



	/**
	 * Output a single plugins catalog item.
	 *
	 * @param array $plugin Catalog item.
	 */
	private function output_plugin_item( $plugin ) {
		$plugin_file  = isset( $plugin[ 'plugin_file' ] ) ? $plugin[ 'plugin_file' ] : '';
		$is_activated = ! empty( $plugin_file ) && FluidCheckout::instance()->is_plugin_activated( $plugin_file );
		$is_installed = ! empty( $plugin_file ) && FluidCheckout::instance()->is_plugin_installed( $plugin_file );
		$show_coming_soon = ! $is_activated && ! $is_installed;
		$is_marketing     = $show_coming_soon;

		$item_class = 'fc-addons__item';
		if ( ! empty( $plugin[ 'item_class' ] ) ) {
			$item_class .= ' ' . $plugin[ 'item_class' ];
		}

		$actions_class = 'fc-addons__item-actions';
		if ( $is_marketing ) {
			$actions_class .= ' fc-addons__item-actions--marketing';
		}
		?>
		<li class="<?php echo esc_attr( $item_class ); ?>">
			<div class="fc-addons__item-inner">
				<div class="fc-addons__item-header">
					<img class="fc-addons__item-image" src="<?php echo esc_url( $plugin[ 'image' ] ); ?>" alt="<?php echo esc_attr( $plugin[ 'title' ] ); ?>">
					<div class="fc-addons__item-title-section">
						<h3 class="fc-addons__item-title">
							<?php echo esc_html( $plugin[ 'title' ] ); ?>
							<?php if ( $show_coming_soon ) : ?>
								<span class="fc-settings-badge"><?php echo esc_html( __( 'Coming soon', 'fluid-checkout' ) ); ?></span>
							<?php endif; ?>
						</h3>
						<p class="fc-dashboard-section__subtitle"><?php echo wp_kses_post( $plugin[ 'subtitle' ] ); ?></p>
					</div>
				</div>
				<div class="fc-addons__item-description">
					<p><?php echo wp_kses_post( $plugin[ 'description' ] ); ?></p>
				</div>
			</div>
			<div class="fc-addons__item-footer">
				<div class="<?php echo esc_attr( $actions_class ); ?>">
					<?php $this->output_plugin_actions( $plugin ); ?>
				</div>
			</div>
		</li>
		<?php
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		?>
		<div class="fc-settings-card fc-settings-card--plugins-catalog">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'More plugins from Fluid Checkout', 'fluid-checkout' ) ); ?></h3>
			</div>

			<div class="fc-settings-card__inner">

				<ul class="fc-addons-list">
					<?php foreach ( $this->get_plugins_catalog() as $plugin ) : ?>
						<?php $this->output_plugin_item( $plugin ); ?>
					<?php endforeach; ?>
				</ul>

				<div class="fc-dashboard__disclaimer">
					<ul>
						<li><?php echo wp_kses_post( __( 'These are <strong>separate plugins</strong> from Fluid Checkout, not add-ons. They are not included with Fluid Checkout PRO or add-on bundle plans.', 'fluid-checkout' ) ); ?></li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_PluginsCatalog::instance();

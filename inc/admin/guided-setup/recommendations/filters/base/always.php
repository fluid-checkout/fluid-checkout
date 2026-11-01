<?php
defined( 'ABSPATH' ) || exit;

/**
 * Always-on recommendation votes (every guided setup run).
 *
 * Registered at the lowest filter priority/specificity so answer-based filters
 * can override these keys. Matching registry rows use a low score so they sort
 * last in the recommended_setup UI.
 */
return array(
	'woocommerce_enable_order_comments' => 'no',
	'fc_pro_enable_cart_page'           => 'yes',
	'fc_pro_enable_order_received'      => 'yes',
	'fc_pro_enable_order_pay'           => 'yes',
);

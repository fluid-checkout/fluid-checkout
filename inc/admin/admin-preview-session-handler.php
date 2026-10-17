<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce session handler for preview requests, which keeps the session in memory only.
 * Ignores the session cookie sent by the browser, so preview pages never read or change the visitor's own session.
 */
class FluidCheckout_Admin_Preview_Session_Handler extends WC_Session_Handler {

	/**
	 * Initialize hooks and session data.
	 */
	public function init() {
		// Add session hooks, and set the preview session instead of reading the session cookie
		$this->init_hooks();
		$this->init_session_cookie();
	}

	/**
	 * Set the preview customer ID with empty session data, without reading the session cookie.
	 */
	public function init_session_cookie() {
		// Set preview customer ID and empty session data
		$this->_customer_id = FluidCheckout_Admin_Preview::instance()->get_preview_customer_id();
		$this->_data = array();
	}



	/**
	 * Check whether the customer has a session, always true as the preview session exists in memory.
	 *
	 * @return  bool
	 */
	public function has_session() {
		return true;
	}

	/**
	 * Get the session cookie, never available as preview sessions have no cookie.
	 *
	 * @return  bool
	 */
	public function get_session_cookie() {
		return false;
	}

	/**
	 * Skip setting the session cookie.
	 *
	 * @param   bool  $set  Whether the session cookie should be set.
	 */
	public function set_customer_session_cookie( $set ) {
		// Intentionally empty, preview sessions have no cookie
	}



	/**
	 * Skip saving session data to the database.
	 *
	 * @param   string  $old_session_key  Session key before the customer logged in.
	 */
	public function save_data( $old_session_key = '' ) {
		// Intentionally empty, preview sessions are kept in memory only
	}

	/**
	 * Destroy the session data, in memory only.
	 */
	public function destroy_session() {
		// Forget session data, without deleting sessions from the database
		$this->forget_session();
	}

	/**
	 * Forget the session data and empty the cart, in memory only.
	 * Keeps the customer ID, as nonces created for the preview page depend on it.
	 */
	public function forget_session() {
		// Maybe empty the cart, like the parent method
		if ( ! is_admin() ) {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			wc_empty_cart();
		}

		// Forget session data
		$this->_data = array();
		$this->_dirty = false;
	}

}

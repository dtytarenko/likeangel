<?php

/**
 * Abstract Server 2 Server class
 *
 */

namespace WCPM\Classes\Http;

use WCPM\Classes\Helpers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

abstract class S2S {

	/**
	 * Function to make the Pinterest APIC event request be sent blocking for more logging.
	 * This is useful for debugging.
	 *
	 * @return bool
	 * @since 1.31.2
	 */
	public static function should_send_requests_blocking() {

		return
			apply_filters('pmw_send_s2s_requests_blocking_for_' . static::$pixel_name, false)
			|| Helpers::should_all_s2s_requests_be_sent_blocking();
	}

	abstract protected static function get_identifiers_from_browser();

	/**
	 * Save the identifiers into the session.
	 *
	 * @return void
	 */
	public static function set_session_identifiers() {

		// Don't run if WC has not initialized a session yet
		if (!WC()->session->has_session()) {
			return;
		}

		// Don't run if we already have set the user identifiers into the session
		if (null !== WC()->session->get(static::$identifiers_key)) {
			return;
		}

		$identifiers = static::get_identifiers_from_browser();

		WC()->session->set(static::$identifiers_key, $identifiers);
	}

	/**
	 * Logs the response of the event request.
	 *
	 * @param $response
	 * @param $event_name
	 * @return void
	 * @since 1.31.2
	 */
	public static function report_response( $response, $event_name = null ) {

		if (!static::$post_request_args['blocking']) {
			return;
		}

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			error_log('error response for: ' . static::$pixel_name . ' ' . $event_name . PHP_EOL . print_r($response, true));
		} else {
			error_log('event sent successfully for: ' . static::$pixel_name . ' ' . $event_name);
		}
	}
}

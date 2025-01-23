<?php

/**
 * Class for deprecated filters
 */

namespace WCPM\Classes;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Deprecated_Filters {

	public static function load_deprecated_filters() {

		// Choose what purchase event name has to be emitted, as TikTok has a choice of those
		// "CompletePayment" seems to be the one that is used to optimize catalog sales
		// "Purchase" seems also to work as per TikTok Pixel Helper
		apply_filters_deprecated(
			'wpm_tiktok_purchase_event_name',
			[ 'CompletePayment' ],
			'1.25.1',
			null,
			'This filter has been deprecated without replacement'
		);

		/**
		 * GA3 MP logger
		 */
		$ga3_mp_logger = apply_filters_deprecated('wooptpm_send_http_api_ga_ua_requests_blocking', [ false ], '1.13.0', 'wpm_send_http_api_ga_ua_requests_blocking');
		apply_filters_deprecated('wpm_send_http_api_ga_ua_requests_blocking', [ $ga3_mp_logger ], '1.27.9', 'pmw_http_send_hit_logger');

		/**
		 * GA4 MP logger
		 */
		$ga4_mp_logger = apply_filters_deprecated('wooptpm_send_http_api_ga_4_requests_blocking', [ false ], '1.13.0', 'wpm_send_http_api_ga_4_requests_blocking');
		apply_filters_deprecated('wpm_send_http_api_ga_4_requests_blocking', [ $ga4_mp_logger ], '1.27.9', 'pmw_http_send_hit_logger');
	}
}

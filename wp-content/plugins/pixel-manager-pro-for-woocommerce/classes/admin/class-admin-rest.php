<?php

namespace WCPM\Classes\Admin;

use WCPM\Classes\Helpers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Admin_REST {

	protected $rest_namespace = 'pmw/v1';

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action('rest_api_init', [ $this, 'register_routes' ]);
	}

	public function register_routes() {

		register_rest_route($this->rest_namespace, '/notifications/', [
			'methods'             => 'POST',
			'callback'            => function ( $request ) {

				$data = $request->get_json_params();

				$data = Helpers::generic_sanitization($data);

//				error_log(print_r($data, true));

				if (wpm_fs()->is__premium_only()) {
					if (isset($data['notification']) && 'pmw-dismiss-license-expiry-message-button' === $data['notification']) {
						Notifications::dismiss_expired_license_warning__premium_only();
					}
				}

				if (isset($data['notification']) && 'pmw-dismiss-opportunities-message-button' === $data['notification']) {
					Opportunities::dismiss_dashboard_notification();
				}

				if (isset($data['notification']) && 'dismiss_opportunity' === $data['notification']) {
					Opportunities::dismiss_opportunity($data['opportunityId']);
				}

				// If the text in $data['notification'] contains the text incompatible-plugin-error-dismissal-button
				// then dismiss the incompatible plugin error
				if (
					isset($data['type'])
					&& isset($data['id'])
					&& 'generic-notification' === $data['type']
				) {

//					error_log('update option with incompatible-plugin-error-dismissal-button');
//					error_log(print_r($data, true));

					$pmw_notifications              = get_option(PMW_DB_NOTIFICATIONS_NAME);
					$pmw_notifications[$data['id']] = true;

					update_option(PMW_DB_NOTIFICATIONS_NAME, $pmw_notifications);
				}

				wp_send_json_success();
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
		]);
	}
}

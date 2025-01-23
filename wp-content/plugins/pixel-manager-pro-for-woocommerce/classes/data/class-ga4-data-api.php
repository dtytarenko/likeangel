<?php

namespace WCPM\Classes\Data;

use WCPM\Classes\Helpers;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class GA4_Data_API {

	protected static $options;
	protected static $options_obj;

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::$options     = Options::get_options();
		self::$options_obj = Options::get_options_obj();
	}

	public static function get_order_attribution_data( $order ) {

		$scope = 'https://www.googleapis.com/auth/analytics https://www.googleapis.com/auth/analytics.readonly';

		$endpoint = 'https://analyticsdata.googleapis.com/v1beta/properties/' . self::$options_obj->google->analytics->ga4->data_api->property_id . ':runReport';

		// Get the order date in the format YYYY-MM-DD
		$order_date = $order->get_date_created();

		// Fallback if get_date_created() returns null
		if (!is_null($order_date)) {
			$order_date_start = $order_date->date('Y-m-d');
			$order_date_end   = $order_date_start;
		} else {
			// $order_date_start is today minus 90 days
			$order_date_start = wp_date('Y-m-d', strtotime('-90 days'));
			// $order_date_end is today
			$order_date_end = wp_date('Y-m-d');
		}

		// https://ga-dev-tools.web.app/ga4/query-explorer/
		$data_request = [
			'dimensions'      => [
				[ 'name' => 'sourceMedium' ],
				[ 'name' => 'transactionId' ],
			],
			'metrics'         => [
				[ 'name' => 'purchaseRevenue' ],
			],
			'dateRanges'      => [
				[
					'startDate' => $order_date_start,
					'endDate'   => $order_date_end,
				],
			],
			'dimensionFilter' => [
				'filter' =>
					[
						'fieldName'    => 'transactionId',
						'stringFilter' => [
							'matchType' => 'EXACT',
							'value'     => $order->get_order_number(),
						],
					],
			],
		];

		$result = self::fetch_from_api($endpoint, $scope, $data_request);

		// If we receive an error, maybe the token saved in the transient is wrong or expired
		// so we try to get a new one and try again
		if (isset($result['error']) && self::get_ga4_access_token_transient_key($scope)) {
			delete_transient(self::get_ga4_access_token_transient_key($scope));
			$result = self::fetch_from_api($endpoint, $scope, $data_request);
		}

		return $result;
	}

	public static function fetch_from_api( $endpoint, $scope, $data_request ) {

		// Rewrite above with wp_remote_post
		$response = wp_remote_post($endpoint, [
			'headers' => [
				'Authorization' => 'Bearer ' . self::get_access_token($scope),
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode($data_request),
		]);

		// Return json decoded body
		return json_decode(wp_remote_retrieve_body($response), true);
	}

	private static function get_ga4_access_token_transient_key( $scope ) {
		return 'pmw_ga4_data_api_access_token_' . hash('sha256', $scope);
	}

	public static function get_access_token( $scope ) {

		// Return access token from transient if it exists.
		// If it doesn't exist, create a new one.
		if (get_transient(self::get_ga4_access_token_transient_key($scope))) {
			return get_transient(self::get_ga4_access_token_transient_key($scope));
		}

		$jwt   = self::get_jwt($scope);
		$token = self::create_new_access_token($jwt);

		// Set transient for 50 minutes
		set_transient('pmw_ga4_data_api_access_token_' . hash('sha256', $scope), $token, 50 * MINUTE_IN_SECONDS);

		return $token;
	}

	public static function create_new_access_token( $jwt ) {

		$endpoint = 'https://oauth2.googleapis.com/token';

		$token_request = [
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $jwt,
		];

		$response = wp_remote_post($endpoint, [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode($token_request),
		]);

		$response_body = json_decode($response['body']);

		return $response_body->access_token;
	}

	private static function base64url_encode( $data ) {
		// phpcs:disable
		// return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
		return str_replace([ '+', '/', '=' ], [ '-', '_', '' ], base64_encode($data));
		// phpcs:enable
	}

	public static function get_jwt( $scope ) {

		// Create token header as a JSON string
		$header = wp_json_encode([
			'alg' => 'RS256',
			'typ' => 'JWT',
		]);

		// Create token payload as a JSON string
		// https://developers.google.com/identity/protocols/oauth2/service-account#httprest
		// JWT token expiry must be an hour or less
		$claim = wp_json_encode([
			'iss'   => self::$options_obj->google->analytics->ga4->data_api->credentials->client_email,
			'scope' => $scope,
			'aud'   => 'https://oauth2.googleapis.com/token',
			'iat'   => time(),
			'exp'   => time() + 50 * MINUTE_IN_SECONDS,  // 50 minutes
		]);

		// Encode header to Base64Url String
		$jwt_header = self::base64url_encode($header);

		// Encode claim to Base64Url String
		$jwt_claim = self::base64url_encode($claim);

		openssl_sign(
			$jwt_header . '.' . $jwt_claim,
			$jwt_signature,
			self::$options_obj->google->analytics->ga4->data_api->credentials->private_key,
			'SHA256'
		);

		$jwt_signature = self::base64url_encode($jwt_signature);

		// Return JWT
		return $jwt_header . '.' . $jwt_claim . '.' . $jwt_signature;
	}
}

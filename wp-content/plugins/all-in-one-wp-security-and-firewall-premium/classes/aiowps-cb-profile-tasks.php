<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 *  Country Blocking Profile Tasks
 */
class AIOWPS_CB_Profile_Tasks extends AIOWPS_Premium_Base_Tasks {

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct();
		$this->do_country_blocking_profile_tasks();
		
	}

	/**
	 * Performs country blocking profile tasks.
	 *
	 * This method checks if the user has enabled the feature to save the country blocking login settings.
	 * If enabled, it adds actions to redirect users on login failure due to region-locked login restrictions,
	 * and sets a cookie upon user logout for future login exemptions.
	 *
	 * @return void
	 */
	private function do_country_blocking_profile_tasks() {
		global $aio_wp_security_premium;
		
		// Check if the user has enabled the feature to save the country blocking login settings
		if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_enabled')) {

			// Add action to redirect user on login failure due to region-locked login restrictions
			// Adding define( 'AIOWPS_COUNTRY_LOGIN_RESTRICTION_DISABLED', true ); in wp-config.php will disable this feature
			if (!defined('AIOWPS_COUNTRY_LOGIN_RESTRICTION_DISABLED') || !AIOWPS_COUNTRY_LOGIN_RESTRICTION_DISABLED) {
				add_action('wp_authenticate', array($this, 'cb_redirect_on_login'), 10, 1);
			}

			// Set cookie upon user login for future login exemptions
			add_action('wp_login', array($this, 'cb_set_profile_login_cookie'), 10, 2);
		}
	}

	/**
	 * Redirect the user when login fails due to region-locked login restrictions.
	 *
	 * @param string $username The user's username.
	 */
	public function cb_redirect_on_login($username) {
		global $aio_wp_security_premium;

		$user = get_user_by('login', $username);

		// Check if the user is a valid WP_User object
		if (is_a($user, 'WP_User')) {
			
		$user_ip = AIOWPSecurity_Utility_IP::get_user_ip_address();

		$aiowps_cookie_secret = get_user_meta($user->ID, 'aiowps_cb_cookie_secret', true);

			// Verify whether the user is permitted to log in by confirming that the aiowps_country_login_exempt cookie does not match the user's cb_cookie_secret.
			if (!isset($_COOKIE['aiowps_country_login_exempt']) || $aiowps_cookie_secret != $_COOKIE['aiowps_country_login_exempt']) {
				// Check if region locked login is enabled for the user
				if ('1' === get_user_meta($user->ID, 'aiowps_cb_region_locked_login_enabled', true)) {

					$user_data = get_userdata($user->ID);

					if (array_intersect($aio_wp_security_premium->configs->get_value('aiowps_cb_login_allowed_roles'), $user_data->roles)) {

						$blocked_countries = (array) get_user_meta($user->ID, 'aiowps_cb_blocked_countries', true);

						$user_country_code = $this->get_country_code_from_ip($user_ip);

						// Check if user's country is blocked
						if ($user_country_code && in_array($user_country_code, $blocked_countries)) {

							$blocking_action = get_user_meta($user->ID, 'aiowps_cb_blocking_action', true);

							// Check if global URL is enabled
							if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url_enabled')) {

								$redirect_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url');

							} else {

								$redirect_url = get_user_meta($user->ID, 'aiowps_cb_redirect_url', true);
								
							}

							// Perform blocking action
							switch ($blocking_action) {
								case 'redirect':
									wp_redirect($redirect_url);
									exit;
									break;
								// Add additional cases for other blocking actions here
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Set cookie upon user logout for future login exemptions.
	 *
	 * @param string  $username The user's username.
	 * @param WP_User $user     The user object.
	 */
	public static function cb_set_profile_login_cookie($username, WP_User $user) {
		global $aio_wp_security_premium;
		
		// Check if region locked login is enabled
		if ('1' === get_user_meta($user->ID, 'aiowps_cb_region_locked_login_enabled', true)) {

			$user_data = get_userdata($user->ID);

			if (array_intersect($aio_wp_security_premium->configs->get_value('aiowps_cb_login_allowed_roles'), $user_data->roles)) {

				setcookie('aiowps_country_login_exempt', get_user_meta($user->ID, 'aiowps_cb_cookie_secret', true), time() + 86400 * 30, COOKIEPATH, COOKIE_DOMAIN);

			}
		}
	}
}

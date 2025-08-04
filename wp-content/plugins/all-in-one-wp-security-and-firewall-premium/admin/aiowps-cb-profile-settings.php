<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 *  Country-Blocking Profile Settings Class
 */
class AIOWPS_CB_Settings_Profile {

	/**
	 * Constructor for initializing actions related to profile page and user profile fields.
	 */
	public function __construct() {

		add_action('show_user_profile', array($this, 'add_region_locked_login_fields'));
		add_action('edit_user_profile', array($this, 'add_region_locked_login_fields'));
		add_action('personal_options_update', array($this, 'save_region_locked_login_fields'));
		add_action('edit_user_profile_update', array($this, 'save_region_locked_login_fields'));
		
	}

	/**
	 * Add region-locked login fields to user profile page.
	 *
	 * @param WP_User $user The user object.
	 */
	public function add_region_locked_login_fields($user) {
		global $aio_wp_security_premium;
		$aiowps_cb_region_locked_login_enabled = (bool) get_user_meta($user->ID, 'aiowps_cb_region_locked_login_enabled', true);
		$aiowps_cb_blocked_countries = (array) get_user_meta($user->ID, 'aiowps_cb_blocked_countries', true);
		$aiowps_cb_blocking_action = get_user_meta($user->ID, 'aiowps_cb_blocking_action', true);
		$aiowps_cb_redirect_url = get_user_meta($user->ID, 'aiowps_cb_redirect_url', true);

		$user_data = get_userdata($user->ID);

		// Check if region-locked login is enabled for the user role
		if (array_intersect($aio_wp_security_premium->configs->get_value('aiowps_cb_login_allowed_roles'), $user_data->roles)) {
			$aio_wp_security_premium->include_template('wp-admin/profile/cb-profile-settings.php', false, array('aiowps_cb_region_locked_login_enabled' => $aiowps_cb_region_locked_login_enabled, 'aiowps_cb_blocked_countries' => $aiowps_cb_blocked_countries, 'aiowps_cb_blocking_action' => $aiowps_cb_blocking_action, 'aiowps_cb_redirect_url' => $aiowps_cb_redirect_url));
		}
	}

	/**
	 * Save region-locked login fields for a user.
	 *
	 * @param int $user_id The ID of the user.
	 */
	public function save_region_locked_login_fields($user_id) {

		$user = get_user_by('ID', $user_id);

		// Check if the user is a valid WP_User object and the current user has permission to edit the user
		if (is_a($user, 'WP_User') && current_user_can('edit_user', $user->ID)) {

				$aiowps_cb_region_locked_login_enabled = !empty($_POST['aiowps_cb_region_locked_login_enabled']) && filter_var($_POST['aiowps_cb_region_locked_login_enabled'], FILTER_VALIDATE_BOOLEAN);
				$aiowps_cb_blocked_countries = isset($_POST['aiowps_cb_blocked_countries']) ? array_map('sanitize_text_field', $_POST['aiowps_cb_blocked_countries']) : array();
				$aiowps_cb_blocking_action = !empty($_POST['aiowps_cb_blocking_action']) ? sanitize_text_field($_POST['aiowps_cb_blocking_action']) : 'default';
				$aiowps_cb_redirect_url = !empty($_POST['aiowps_cb_redirect_url']) ? sanitize_url($_POST['aiowps_cb_redirect_url']) : '';
				$aiowps_cb_cookie_secret = !empty($_POST['aiowps_cb_cookie_secret']) ? sanitize_text_field($_POST['aiowps_cb_cookie_secret']) : wp_generate_uuid4();

				update_user_meta($user->ID, 'aiowps_cb_region_locked_login_enabled', (bool) $aiowps_cb_region_locked_login_enabled);
				update_user_meta($user->ID, 'aiowps_cb_blocked_countries', (array) $aiowps_cb_blocked_countries);
				update_user_meta($user->ID, 'aiowps_cb_blocking_action', $aiowps_cb_blocking_action);
				update_user_meta($user->ID, 'aiowps_cb_redirect_url', $aiowps_cb_redirect_url);
				update_user_meta($user->ID, 'aiowps_cb_cookie_secret', $aiowps_cb_cookie_secret);

				AIOWPS_CB_Profile_Tasks::cb_set_profile_login_cookie($user->user_login, $user);
		}
	}
}

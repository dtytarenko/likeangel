<?php

if (!defined('ABSPATH')) exit;

class AIOWPS_CB_General_Init_Tasks {

	public function __construct() {
		add_action('wp', array($this, 'do_wp_tasks'));
		add_action('wp_head', array($this, 'do_head_tasks'));
		add_action('wp_ajax_nopriv_country_check_ajax', array($this, 'country_check_ajax'));
		add_action('wp_ajax_country_check_ajax', array($this, 'country_check_ajax'));

		$this->do_country_blocking_general_tasks();
		
		// Add more tasks that need to be executed at init time
		
	}

	public function do_country_blocking_general_tasks() {
		global $aio_wp_security_premium;
		if ($aio_wp_security_premium->configs->get_value('aiowps_cb_enable_country_blocking') == '1') {
			// check if accessing the test function to capture IP info
			// if (isset($_GET['troubleshoot_aiowps_cb'])) {
			// AIOWPS_CB_Utilities::test_function();
			// }
			if ((!is_user_logged_in() || !current_user_can('administrator')) && !(defined('DOING_AJAX') && DOING_AJAX)) {
				$aio_wp_security_premium->country_tasks_obj->perform_country_check();
			}
		}
	}

	public function do_wp_tasks() {
		global $aio_wp_security_premium;
		// Get the post/page ID and do secondary blocking tasks
		global $post;

		if (!is_a($post, 'WP_Post')) {
			return;
		}
		
		$page_id = $post->ID;
		if (!empty($page_id)) {
			// Handle secondary blocking tasks
			// If feature is enabled do the following:
			// 1. check if current page is listed in the settings
			// 2. yes - get user IP and check country.
			// no - fall through
			// 3. if country is in list block - redirect to URL/page
			if ($aio_wp_security_premium->configs->get_value('aiowps_cb_enable_secondary_country_blocking') == '1') {
				if (!is_user_logged_in() || !current_user_can('administrator') || !is_admin()) {
					$aio_wp_security_premium->country_tasks_obj->perform_secondary_country_check($page_id);
				}
			}
		}
	}
	
	public function do_head_tasks() {
		global $aio_wp_security_premium;
		// For ajax case
		if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_ajax_enabled') && ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_country_blocking') || '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_secondary_country_blocking'))) {
			// hide the page content initially so blocked site or post can't be visible to user.
			?>
			<style id='aios-cb-style' type="text/css"> html { visibility:hidden; } </style>
			<?php
		}
	}
	
	public function country_check_ajax() {
		check_ajax_referer('wp_nonce', 'security');
		global $aio_wp_security_premium;
		$is_blocked = false;
		$is_primary_blocked = false;
		$is_secondary_blocked = false;
		$redirect_url = '';
		if (!is_user_logged_in() || !current_user_can('administrator') || !is_admin()) {
			$redirect_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_redirect_url');
			$current_page_url = $_SERVER["HTTP_REFERER"];
			$redirect_secondary_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_redirect_url');
			$post_id = intval($_POST['post_id']);

			if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_country_blocking')) {
				$is_blocked = $aio_wp_security_premium->country_tasks_obj->is_blocked();
				$is_primary_blocked = $is_blocked;
			}
			if (true == $is_primary_blocked && $current_page_url == $redirect_url) {
				$is_blocked = false; // allow page to load if user lands on the redirect URL
			}
			if (false == $is_primary_blocked && false == $is_blocked && '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_secondary_country_blocking')) {
				$post_ids = $aio_wp_security_premium->configs->get_value('aiowps_secondary_cb_protected_post_ids');
				$post_id_array = preg_split('/\R/', $post_ids);
				if (!empty($post_id) && in_array($post_id, $post_id_array)) {
					$is_blocked = $aio_wp_security_premium->country_tasks_obj->is_blocked('secondary');
					$is_secondary_blocked = $is_blocked;
					$redirect_url = $redirect_secondary_url;
				}
			}
			if (true == $is_secondary_blocked && $current_page_url == $redirect_secondary_url) {
				$is_blocked = false; // allow page to load if user lands on the redfirect URL
			}
		}
		if ($is_blocked) {
			$response = json_encode(array('status' => 'block', 'redirect_url' => $redirect_url));
		} else {
			$response = json_encode(array('status' => 'ok'));
		}
		echo $response;
		die();
	}
}

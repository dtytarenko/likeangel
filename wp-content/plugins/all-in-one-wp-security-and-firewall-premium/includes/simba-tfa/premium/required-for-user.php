<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Required_For_User {
	
	private $simba_tfa;

	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_action('init', array($this, 'action_init'));
		
		add_action('all_admin_notices', array($this, 'all_admin_notices'));
		
		add_filter('simba_tfa_after_user_roles', array($this, 'simba_tfa_after_user_roles'));
		
	}
	
	/**
	 * Runs upon the WP 'init' action
	 */
	public function action_init() {
		
		$check_constants = array('DOING_AJAX', 'DOING_CRON');
		foreach ($check_constants as $constant) {
			if (defined($constant) && constant($constant)) return;
		}
		
		if (!is_user_logged_in()) return;
		
		if (is_admin() && apply_filters('simba_tfa_apply_redirect_in_admin', false)) return;
		
		$redirect_to = trim($this->simba_tfa->get_option('tfa_if_required_redirect_to'));
		
		if ('' == $redirect_to || preg_match('#^https?://?$#i', $redirect_to)) return;
			
		$current_url = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		
		$current_url_details = parse_url($current_url);
		$redirect_url_details = parse_url($redirect_to);
		
		if (!isset($current_url_details['query'])) $current_url_details['query'] = '';
		if (!isset($redirect_url_details['query'])) $redirect_url_details['query'] = '';
		
		if ($current_url_details['path'] == $redirect_url_details['path'] && $current_url_details['query'] == $redirect_url_details['query']) {
			return;
		}
		
		$user_id = get_current_user_id();
		
		if ($this->simba_tfa->is_activated_for_user($user_id) && $this->simba_tfa->is_required_for_user($user_id) && !$this->simba_tfa->is_activated_by_user($user_id)) {
			wp_redirect($redirect_to);
			exit;
		}
		
	}
	
	/**
	 * Called by the WP filter simba_tfa_after_user_roles
	 *
	 * @param String $default - unfiltered output
	 *
	 * @return String - filtered output
	 */
	public function simba_tfa_after_user_roles($default) {
		
		wp_register_script('tfa-flatpickr-js', plugins_url('', __FILE__).'/includes/flatpickr.min.js', array('jquery'), filemtime(__DIR__.'/includes/flatpickr.min.js'));
		
		wp_register_script('tfa-required-for-user-js', plugins_url('', __FILE__).'/includes/required-for-user.js', array('tfa-flatpickr-js'), filemtime(__DIR__.'/includes/required-for-user.js'));
		
		wp_enqueue_script('tfa-required-for-user-js');
		
		wp_enqueue_style('tfa-flatpickr-css', plugins_url('', __FILE__).'/includes/flatpickr.min.css', array(), filemtime(__DIR__.'/includes/flatpickr.min.css'));
		
		$require_enforce_after = $this->simba_tfa->get_option('tfa_require_enforce_after');
		if (!is_string($require_enforce_after)) $require_enforce_after = 'today';
		
		wp_localize_script(
			'tfa-required-for-user-js',
			'simbatfa_required_for_user_js',
			apply_filters('simba_tfa_require_for_user_localize', array(
				'default_date' => $require_enforce_after,
				'tfa_unavailable_for_role' => __('(TFA is configured to not be available for this role)', 'all-in-one-wp-security-and-firewall-premium')
			))
		);
		
		$ret = '';
		$ret .= '<form method="post" action="options.php" style="margin-top: 12px">';
		
		// settings_fields('tfa_user_roles_required_group');
		$ret .= "<input type='hidden' name='option_page' value='tfa_user_roles_required_group'>";
		$ret .= '<input type="hidden" name="action" value="update">';
		$ret .= wp_nonce_field("tfa_user_roles_required_group-options", '_wpnonce', true, false);
		
		$ret .= __('Choose which user roles are required to have two-factor authentication active (remember to also make it available for any chosen roles).', 'all-in-one-wp-security-and-firewall-premium');
		$ret .= '<p>';

		if (is_multisite()) {
			// Not a real WP role; needs separate handling
			$id = '_super_admin';
			$name = __('Multisite Super Admin', 'all-in-one-wp-security-and-firewall-premium');
			$setting = (bool)$this->simba_tfa->get_option('tfa_required_'.$id);
			
			$ret .= '<input type="checkbox" id="tfa_required_'.$id.'" name="tfa_required_'.$id.'" class="tfa_pre_requisite_role_'.$id.'" value="1" '.($setting ? 'checked="checked"' :'').'> <label id="tfa_required_'.$id_esc_attr.'_label" for="tfa_required_'.$id.'">'.htmlspecialchars($name).' <span class="label_append"></span></label><br>'."\n";
		}
		
		global $wp_roles;
		if (!isset($wp_roles)) $wp_roles = new WP_Roles();
		
		foreach($wp_roles->role_names as $id => $name) {	
			$setting = (bool)$this->simba_tfa->get_option('tfa_required_'.$id);
			
			$id_esc_attr = esc_attr($id);
			
			$ret .= '<input type="checkbox" id="tfa_required_'.$id_esc_attr.'" name="tfa_required_'.$id_esc_attr.'"  class="tfa_pre_requisite_role_'.$id_esc_attr.'" value="1" '.($setting ? 'checked="checked"' : '').'> <label id="tfa_required_'.$id_esc_attr.'_label" for="tfa_required_'.$id_esc_attr.'">'.htmlspecialchars(translate_user_role($name)).' <span class="label_append"></span></label><br>'."\n";
		}
		
		$ret .= '</p><p>';
		
		$hide_turn_off = $this->simba_tfa->get_option('tfa_hide_turn_off');
		
		$ret .= '<br>'.'<input type="checkbox" '.($hide_turn_off ? 'checked="checked" ' : '').' name="tfa_hide_turn_off" id="tfa_hide_turn_off" value="1"><label for="tfa_hide_turn_off">'.__('For these users, hide the option to turn TFA off', 'all-in-one-wp-security-and-firewall-premium').'</label>';
		
		$redirect_to = $this->simba_tfa->get_option('tfa_if_required_redirect_to');
		
		$ret .= '<br>'.__("If a user with TFA required but not yet setup logs in, then forcibly redirect them from any other page to the following URL (which should be a preferred page where they can set it up - make sure that it is a full URL beginning with http:// or https:// and that it is the WordPress canonical URL for the page so that a redirection loop is not created; a page in the WordPress dashboard (i.e. wp-admin) can be used if your users are allowed to access the dashboard):", 'all-in-one-wp-security-and-firewall-premium');
		
		$ret .= '<br><input type="text" style="width:60%;" name="tfa_if_required_redirect_to" id="tfa_if_required_redirect_to" value="'.$redirect_to.'" placeholder="https://">';
		
		$requireafter = $this->simba_tfa->get_option('tfa_requireafter');
		
		$requireafter = (false === $requireafter) ? 10 : absint($requireafter);
		
		$ret .= '<br><em>'.__('N.B. Note that the following settings forbid login entirely; if you are using the above setting for redirecting users upon login to a user-settings page where they can set up TFA, then you likely do not want to forbid them from logging in.', 'all-in-one-wp-security-and-firewall-premium').'</em><br>';
		
		$ret .= sprintf(__('Forbid users who have not set up TFA to login once their accounts are %s days old', 'all-in-one-wp-security-and-firewall-premium'), '<input type="number" style="width:80px;" step="1" min="0" name="tfa_requireafter" id="tfa_requireafter" value="'.$requireafter.'">');
		
		$require_enforce_after = $this->simba_tfa->get_option('tfa_require_enforce_after');
		
		$ret .= '<br>'.sprintf(__('Do not begin forbidding such users to login until: %s', 'all-in-one-wp-security-and-firewall-premium'), '<input type="text" style="width: 120px;" name="tfa_require_enforce_after" id="tfa_require_enforce_after">').'<br>'.__('(If you are setting up for the first time and have pre-existing users, then you may also want to inform them).', 'all-in-one-wp-security-and-firewall-premium');;		
		
		$ret .= '</p>'.get_submit_button().'</form>';
		
		return $ret;
		
	}
	
	/**
	 * Runs upon the WP action all_admin_notices
	 */
	public function all_admin_notices() {
		
		global $current_user;
		// Test for whether they're require to have TFA active and haven't yet done so.
		if ($this->simba_tfa->is_activated_for_user($current_user->ID) && $this->simba_tfa->is_required_for_user($current_user->ID) && !$this->simba_tfa->is_activated_by_user($current_user->ID)) {
			$this->simba_tfa->show_admin_warning('<strong>'.__('Please set up two-factor authentication', 'all-in-one-wp-security-and-firewall-premium').'</strong><br> <a href="'.esc_url(admin_url('admin.php').'?page='. $this->simba_tfa->get_user_settings_page_slug()) .'">'.__('You will need to set up and use two-factor authentication to login in future.</a>', 'all-in-one-wp-security-and-firewall-premium'), 'error');
		}
	}
}

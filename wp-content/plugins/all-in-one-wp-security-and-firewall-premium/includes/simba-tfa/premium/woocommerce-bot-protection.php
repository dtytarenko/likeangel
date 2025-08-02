<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_WooCommerce_Bot_Protection {

	/**
	 * Simba 2FA object.
	 * @var object
	 */
	private $simba_tfa;
	
	/**
	 * Login forms array to replace WooCommerce form.
	 * @var array
	 */
	private $login_forms;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_action('init', array($this, 'action_init'));
		add_action('simba_tfa_woocommerce_after_account_setting', array($this, 'simba_tfa_woocommerce_after_account_setting'));
	}
	
	/**
	 * Runs upon the WP 'woocommerce_login_form_start' action
	 */
	public function woocommerce_login_form_start() {
		ob_start();
	}
	/**
	 * Runs upon the WP 'init' action
	 */
	public function action_init() {
	
		$check_constants = array('DOING_AJAX', 'DOING_CRON');
		foreach ($check_constants as $constant) {
			if (defined($constant) && constant($constant)) return;
		}
	
		if (!is_admin() && $this->simba_tfa->get_option('tfa_bot_protection')) {
			add_action('woocommerce_login_form_start', array($this, 'woocommerce_login_form_start'));
			add_action('woocommerce_login_form_end', array($this, 'woocommerce_login_form_end'));
		}
	}
	
	/**
	 * Runs upon the WP 'woocommerce_login_form_end' action
	 */
	public function woocommerce_login_form_end() {
		$html = ob_get_clean();
		static $index = 0;
		echo "<div id=\"tfa-login-form-replace-$index\"></div>\n";
		$this->login_forms[$index] = array('type' => 'woocommerce', 'contents' => $html);
		$index++;
		
		static $hooked_footer = false;
		if (!$hooked_footer) {
			$hooked_footer = true;
			// Lower than default priority, in case other scripts are going to rely on the form being there
			add_action('wp_footer', array($this, 'wp_footer'), 1);
		}
	}
	
	/**
	 * Runs upon the WP action simba_tfa_woocommerce_after_account_setting
	 */
	public function simba_tfa_woocommerce_after_account_setting() {
		
		$bot_protection = $this->simba_tfa->get_option('tfa_bot_protection');
		
		?>
		
		<br>
		
		<input type="checkbox" id="tfa_bot_protection" name="tfa_bot_protection" value="1" <?php echo $bot_protection ? 'checked="checked"' :''; ?>> <label for="tfa_bot_protection"><?php echo htmlspecialchars(__("Protect against bots by hiding login form HTML (requires JavaScript in user's browser to un-hide it)", 'all-in-one-wp-security-and-firewall-premium')); ?></label>
		
		<?php
		
	}
	
	/**
	 * Runs upon the WP 'wp_footer' action
	 */
	public function wp_footer() {
		if (empty($this->login_forms)) return;
		?>
		<script>
		var tfa_login_forms = JSON.parse(window.atob("<?php echo base64_encode(json_encode($this->login_forms)); ?>"));
		var replace_index = 0;
		for (var i=0 ; i< tfa_login_forms.length; i++) {
			var replace_this = document.getElementById('tfa-login-form-replace-'+i.toString());
			replace_this.outerHTML = tfa_login_forms[i].contents;
		}
		</script>
		<?php
	}
	
}

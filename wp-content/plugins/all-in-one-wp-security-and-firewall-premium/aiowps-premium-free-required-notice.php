<?php
if (!defined('ABSPATH')) die('No direct access.');

/**
 * AIOS Free required notice class.
 *
 * Responsible to render AIOS Free required notice.
 */
class AIOWPS_Free_Required_Notice {

	/**
	 * Add WP hooks.
	 *
	 * @return void
	 */
	public function add_hooks() {
		if (!current_user_can('manage_options')) {
			return;
		}

		if (is_multisite() && !is_network_admin()) {
			return;
		}

		// The notice should not displayed when installation of the AIOS free plugin.
		if (isset($_GET['action']) && 'install-plugin' == $_GET['action'] && isset($_GET['plugin']) && 'all-in-one-wp-security-and-firewall' == $_GET['plugin']) {
			return;
		}

		add_action('all_admin_notices', array($this, 'all_admin_notices'));
	}

	/**
	 * Show admin notice to install AIOS free plugin.
	 *
	 * @return void
	 */
	public function all_admin_notices() {
		$aios_free_plugin_file_rel_to_plugins_dir = $this->get_aios_free_plugin_file_rel_to_plugins_dir();
		?>
		<div class="notice notice-error">
			<h3>
				<?php
				echo __('Attention:', 'all-in-one-wp-security-and-firewall-premium'). ' ';
				if ($aios_free_plugin_file_rel_to_plugins_dir) {
					_e('please activate the free AIOS plugin', 'all-in-one-wp-security-and-firewall-premium');
				} else {
					_e('please install and activate the free AIOS plugin', 'all-in-one-wp-security-and-firewall-premium');
				}
				?>
			</h3>
			<p>
				<?php _e('AIOS Premium requires the AIOS free plugin to be installed and activated.', 'all-in-one-wp-security-and-firewall-premium'); ?>
			</p>

			<?php

			if ($aios_free_plugin_file_rel_to_plugins_dir) {
				?>
				<p>
					<?php _e('The AIOS free plugin is installed, but not activated.', 'all-in-one-wp-security-and-firewall-premium'); ?>
				</p>
				<p>
					<?php _e('Would you like to activate the AIOS free plugin?', 'all-in-one-wp-security-and-firewall-premium'); ?>
				</p>
				<?php
				$activate_url = add_query_arg(array(
					'_wpnonce'    => wp_create_nonce('activate-plugin_'.$aios_free_plugin_file_rel_to_plugins_dir),
					'action'      => 'activate',
					'plugin'      => $aios_free_plugin_file_rel_to_plugins_dir,
				), network_admin_url('plugins.php'));

				// If is network admin then add to link network activation.
				if (is_network_admin()) {
					$activate_url = add_query_arg(array('networkwide' => 1), $activate_url);
				}
				?>
				<p>
					<a class="button button-primary" href="<?php echo esc_url($activate_url); ?>">
						<?php _e('Activate AIOS free plugin', 'all-in-one-wp-security-and-firewall-premium'); ?>
					</a>
				</p>
			<?php
			} else {
			?>
				<p>
					<?php _e('Would you like to install and activate the AIOS free plugin?', 'all-in-one-wp-security-and-firewall-premium'); ?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo $this->get_aios_install_url(); ?>">
						<?php echo htmlspecialchars(__('Install the AIOS plugin now', 'all-in-one-wp-security-and-firewall-premium')); ?>
					</a>
				</p>
			<?php
			}
			?>
		</div>
		<?php
	}


	/**
	 * Get path to the AIOS free plugin file relative to the plugins directory.
	 *
	 * @return string|false path to the UpdraftPlus plugin file relative to the plugins directory
	 */
	private function get_aios_free_plugin_file_rel_to_plugins_dir() {
		if (!function_exists('get_plugins')) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		$installed_plugins_keys = array_keys($installed_plugins);
		foreach ($installed_plugins_keys as $plugin_file_rel_to_plugins_dir) {
			$temp_plugin_file_name = substr($plugin_file_rel_to_plugins_dir, strpos($plugin_file_rel_to_plugins_dir, '/') + 1);
			if ('wp-security.php' == $temp_plugin_file_name) {
				return $plugin_file_rel_to_plugins_dir;
			}
		}
		return false;
	}

	/**
	 * Get AIOS free plugin URL.
	 *
	 * @return string AIOS free plugin installation URL.
	 */
	private function get_aios_install_url() {
		return wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=all-in-one-wp-security-and-firewall'), 'install-plugin_all-in-one-wp-security-and-firewall');
	}
}

<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h2><?php _e('Country-blocking login settings', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<div class="aio_blue_box">
		<?php
		echo '<p>' . __('This feature gives you the option of allowing certain user roles to enable country-restricted login.', 'all-in-one-wp-security-and-firewall-premium') . '</p>' .
			'<h4>' . __('Enabling region-locked login for user profiles:', 'all-in-one-wp-security-and-firewall-premium') . '</h4>' .
			'<ol><li>' . __('After enabling the country-blocking for user account login feature, visit any user profile page.', 'all-in-one-wp-security-and-firewall-premium') . '</li>' .
			'<li>' . htmlspecialchars(__('Scroll down to the section titled "Region-locked login".', 'all-in-one-wp-security-and-firewall-premium')) . '</li>' .
			'<li>' . htmlspecialchars(__('Enable the "Enable region-locked login" checkbox to activate region-based login restrictions for the selected user.', 'all-in-one-wp-security-and-firewall-premium')) . '</li>' .
			'<li>' . htmlspecialchars(__('Choose the desired action to take when a blocked login attempt is detected from the dropdown menu under "Blocking action".', 'all-in-one-wp-security-and-firewall-premium')) . '</li>' .
			'<li>' . __('Specify a custom redirect URL where blocked users should be directed.', 'all-in-one-wp-security-and-firewall-premium') . '</li>' .
			'<li>' . __('To block login attempts from specific countries, select the respective countries from the provided list.', 'all-in-one-wp-security-and-firewall-premium') . '</li></ol>' .
			'<p>' . __('For users who frequently travel and may forget to disable region-locked login, a cookie mechanism has been implemented.', 'all-in-one-wp-security-and-firewall-premium') .
			'<br>' . __('When a user logs in from a particular browser, a cookie is set to indicate prior successful login.', 'all-in-one-wp-security-and-firewall-premium') .
			'<br>' . __('Subsequent login attempts from the same browser will be exempted from region-locked login restrictions.', 'all-in-one-wp-security-and-firewall-premium') . '</p>';
		?>
		</div>
		<div class="aio_grey_box">
			<?php
				echo '<p>' . sprintf(__('If you are locked out by the country-blocking user account login feature, define the following constant %s in wp-config.php to disable the feature.', 'all-in-one-wp-security-and-firewall-premium'), '<strong>define(\'AIOWPS_COUNTRY_LOGIN_RESTRICTION_DISABLED\', true);</strong>') . '</p>';
			?>
		</div>
		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Enable country-blocking for user account login', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
			
		<form action="" method="POST">
		<?php wp_nonce_field('aiowpsec-cb-login-settings-nonce'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Enable country-blocking login for users', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<div class="aiowps_switch_container">
						<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want the country-blocking login feature', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_login_enabled', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_enabled')); ?>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Enable global country-blocking login redirect URL', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<div class="aiowps_switch_container">
						<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want to use the same URL across every user', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_login_global_url_enabled', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url_enabled')); ?>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Global redirect URL', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td><input type="text" size="50" name="aiowps_cb_login_global_url" value="<?php echo $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url'); ?>">
				<span class="description"><?php _e('Set the value for the global URL where you want to send the blocked visitor to', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
				</td> 
			</tr>
			<!-- Add checkboxes for user roles -->
			<tr valign="top">
				<th scope="row"><?php _e('User roles', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<p>
						<?php
						$saved_allowed_roles = ($aio_wp_security_premium->configs->get_value('aiowps_cb_login_allowed_roles') == '') ? array() : $aio_wp_security_premium->configs->get_value('aiowps_cb_login_allowed_roles');
						$roles_array = $aio_wp_security_premium->profile_tasks_obj->user_roles;
						foreach ($roles_array as $role_key => $role_label) {
							$checked = in_array($role_key, $saved_allowed_roles) ? ' checked="checked"' : '';
							echo '<input type="checkbox" id="aiowps_user_roles_checkbox_' . esc_attr($role_key) . '" name="aiowps_user_roles_checkbox_' . esc_attr($role_key) . '" class="user_roles" value="'. esc_attr($role_key) .'" ' . $checked . '> <label for="aiowps_user_roles_checkbox_' . esc_attr($role_key) . '">' . esc_attr($role_label) . '</label><br>';
						}
						?>
					</p>
				</td>
			</tr>
		</table>
		<input type="submit" name="aiowps_save_cb_login_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium');?>" class="button-primary">
		</form>
		</div></div>
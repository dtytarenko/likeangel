<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access allowed'); ?>

<h2><?php _e('Smart 404 configuration', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<div class="aio_blue_box">
			<?php
			echo '<p>'.__('Hackers often use automated scripts and bots to constantly probe your site looking for certain URLs they think they can exploit.', 'all-in-one-wp-security-and-firewall-premium').'
			<br />'.__('This repeated probing will cause a lot of http 404 events.', 'all-in-one-wp-security-and-firewall-premium').'
			<br />'.__('This addon allows you to monitor and permanently block IP addresses if they cause more than a certain number of 404 events.', 'all-in-one-wp-security-and-firewall-premium').'
			</p>';
			?>
		</div>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('Smart 404 settings', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
			<div class="inside">
				<?php
				if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_smart_404')) {
					// display yellow blocked count summary
					global $wpdb;
					// Get number of 404 events for today
					$events_404_today = AIOWPS_Premium_Utilities::get_todays_404_events();
					$num_404_today = empty($events_404_today) ? 0 : count($events_404_today);

					// Get blocked IPs due to 404
					$sql = $wpdb->prepare('SELECT * FROM ' . AIOWPSEC_TBL_PERM_BLOCK . ' WHERE block_reason=%s', '404');
					$total_res = $wpdb->get_results($sql);
					?>
					<div class="aio_yellow_box">
						<?php
						if (empty($total_res)) {
							echo '<p><strong>' . __('You currently have no IP addresses permanently blocked due to 404.', 'all-in-one-wp-security-and-firewall-premium') . '</strong></p>';
						} else {
							$total_count = count($total_res);
							$todays_blocked_count = 0;
							foreach ($total_res as $blocked_item) {
								$now = current_time('mysql');
								$now_date_time = new DateTime($now);
								$blocked_date = new DateTime($blocked_item->blocked_date);
								if ($blocked_date->format('Y-m-d') == $now_date_time->format('Y-m-d')) {
									// there was an IP added to permanent block list today
									++$todays_blocked_count;
								}
							}
							echo '<p><strong>' . __('# 404 events Today:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $num_404_today . '</strong></p>' .
								'<p><strong>' . __('# IPs permanently blocked today:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $todays_blocked_count . '</strong></p>' .
								'<hr><p><strong>' . __('All time total IPs blocked:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $total_count . '</strong></p>' .
								'<p><a class="button" href="admin.php?page=' . AIOWPS_SMART_404_SETTINGS_MENU_SLUG . '&tab=tab2" target="_blank">' . __('View blocked IPs', 'all-in-one-wp-security-and-firewall-premium') . '</a></p>';
						}
						?>
					</div>
				<?php
				}
				// Display security info badge
				$aiowps_feature_mgr->output_feature_details_badge("enable-smart-404");
				?>
				<form action="" method="POST">
					<?php wp_nonce_field('aiowpsec-smart-404-nonce'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Enable smart 404 feature', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td>
								<div class="aiowps_switch_container">
									<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want smart404 blocking', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_enable_smart_404', '1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_smart_404')); ?>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Max 404 events', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td><input type="text" size="5" name="aiowps_max_404_attempts" value="<?php echo $aio_wp_security_premium->configs->get_value('aiowps_max_404_attempts'); ?>" />
								<span class="description"><?php _e('Set the value for the maximum number of 404 events before IP address is blocked', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('404 retry time period (min)', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td><input type="text" size="5" name="aiowps_404_retry_time_period" value="<?php echo $aio_wp_security_premium->configs->get_value('aiowps_404_retry_time_period'); ?>" />
								<span class="description"><?php _e('If the maximum number of http 404 events for a particular IP address occur within this time period the plugin will permanently block that address.', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
							</td>
						</tr>
					</table>
					<input type="submit" name="aiowps_smart404_settings_save" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
				</form>
			</div></div>

		<div class="aio_blue_box">
			<?php
			echo '<p><strong>'.__('Instant 404 blocking based on string match', 'all-in-one-wp-security-and-firewall-premium').'</strong></p>';
			echo '<p>'.__('This feature allows you instantly block an IP address based on whether a certain string is contained within the URL which produced the 404 event.', 'all-in-one-wp-security-and-firewall-premium').'
			<br />'.__('The settings below allow you to specify the strings you wish to look out for inside a URL which causes a 404.', 'all-in-one-wp-security-and-firewall-premium').'
				<br />'.__('If the plugin detects one of the strings inside the URL which caused the 404, it will instantly add the IP address to the permanent block list.', 'all-in-one-wp-security-and-firewall-premium').'
			</p>';
			?>
		</div>
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('Instant 404 block by string match', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
			<div class="inside">
				<?php
				// Display security info badge
				$aiowps_feature_mgr->output_feature_details_badge("enable-instant-404-block-based-on-string-match");
				?>
				<form action="" method="POST">
					<?php wp_nonce_field('aiowpsec-instant-404-block-nonce'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Enable instant 404 block based on string match', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td>
								<div class="aiowps_switch_container">
									<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want to instantly block an IP address if the URL contains one of the strings listed below and a 404 event.', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_enable_instant_404_string_block', '1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_instant_404_string_block')); ?>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Enter strings you wish to base 404 blocking on:', 'all-in-one-wp-security-and-firewall-premium'); ?></th>
							<td>
								<textarea name="smart_404_instant_block_strings" rows="5" cols="50"><?php echo esc_textarea($aio_wp_security_premium->configs->get_value('smart_404_instant_block_strings')); ?></textarea>
								<br />
								<span class="description">
									<?php _e('Enter one or more strings you want to listen for during 404 events and based on this block the IP address instantly.', 'all-in-one-wp-security-and-firewall-premium');?>
									<br /><strong><?php _e('Each string must be on a new line.', 'all-in-one-wp-security-and-firewall-premium');?></strong>
								</span>
							</td>
						</tr>
					</table>
					<input type="submit" name="save_404_instant_block_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
				</form>
			</div></div>

		<div class="aio_blue_box">
			<?php
			echo '<p><strong>'.__('Smart 404 white list', 'all-in-one-wp-security-and-firewall-premium').'</strong></p>';
			echo '<p>'.__('In certain cases you may want to prevent particular IP addresses from being permanently blocked due to 404 events.', 'all-in-one-wp-security-and-firewall-premium').'
			<br />'.__('One common case is if you are using a malware scanning service, because the malware scanning bots can often produce 404 events as part of their checks.', 'all-in-one-wp-security-and-firewall-premium').'
				<br />'.__('This feature gives you the option of allowing certain IP addresses or ranges to be immune from being blocked permanently due to too many 404 events.', 'all-in-one-wp-security-and-firewall-premium').'
			</p>';
			?>
		</div>

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('Smart 404 whitelist settings', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
			<div class="inside">
				<?php
				// Display security info badge
				$aiowps_feature_mgr->output_feature_details_badge("smart-404-whitelist-settings");
				?>
				<form action="" method="POST">
					<?php wp_nonce_field('smart-404-whitelist-nonce'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Enable IP whitelisting', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td>
								<div class="aiowps_switch_container">
									<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this for the whitelisting of selected IP addresses specified in the settings below', 'all-in-one-wp-security-and-firewall-premium'), 'enable_smart_404_whitelist', '1' == $aio_wp_security_premium->configs->get_value('enable_smart_404_whitelist')); ?>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Your current IP address', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td>
								<input size="20" name="aiowps_user_ip" type="text" value="<?php echo $your_ip_address; ?>" readonly="readonly"/>
								<span class="description"><?php _e('You can copy and paste this address in the text box below if you want to include it in your smart 404 whitelist.', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Enter whitelisted IP addresses:', 'all-in-one-wp-security-and-firewall-premium');?></th>
							<td>
								<textarea name="smart_404_ip_whitelist" rows="5" cols="50"><?php echo esc_textarea($aio_wp_security_premium->configs->get_value('smart_404_ip_whitelist')); ?></textarea>
								<br />
								<span class="description"><?php _e('Enter one or more IP addresses or IP ranges you wish to include in your whitelist.', 'all-in-one-wp-security-and-firewall-premium');?></span>
								<?php $aio_wp_security->include_template('info/ip-address-ip-range-info.php');?>

							</td>
						</tr>
					</table>
					<input type="submit" name="save_smart_404_whitelist_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium');?>" class="button-primary" />
				</form>
			</div></div>
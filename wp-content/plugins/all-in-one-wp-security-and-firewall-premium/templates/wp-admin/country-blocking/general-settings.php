<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h2><?php _e('Country blocking settings', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<form action="" method="POST">
		<?php wp_nonce_field('aiowpsec-country-blocking-settings-nonce'); ?>

		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Enable country blocking', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
		<div class="aio_blue_box">
			<?php
			echo '<p>'.__('Each country and territory around the world has an IP address range which is allocated to it.', 'all-in-one-wp-security-and-firewall-premium').
			'<br />'.__('When a visitor lands on your site, this feature will determine which country they are from by examining their IP address and it will block users based their country if applicable.', 'all-in-one-wp-security-and-firewall-premium').
			'<br />'.__('We use the most professional, accurate and up-to-date Geo-IP database available to maximize the successful identification of users and their originating countries.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
			?>
		</div>
		<?php
		$saved_blocked_countries = ($aio_wp_security_premium->configs->get_value('aiowps_cb_blocked_countries') == '') ? array() : $aio_wp_security_premium->configs->get_value('aiowps_cb_blocked_countries');
		$countries_array = $aio_wp_security_premium->country_tasks_obj->country_codes;
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Enable country blocking', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<div class="aiowps_switch_container">
						<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want the country blocking feature', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_enable_country_blocking', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_country_blocking')); ?>
					</div>
				</td>
			</tr>
		</table>
		</div></div>
		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Country blocking options', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Blocking action', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<select id="aiowps_cb_blocking_action" name="aiowps_cb_blocking_action">
						<option value="0" <?php selected($aio_wp_security_premium->configs->get_value('aiowps_cb_blocking_action'), '0'); ?>><?php _e('redirect', 'all-in-one-wp-security-and-firewall-premium'); ?></option>
					</select>
				<span class="description"><?php _e('Set the type of blocking action you would like to perform', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
				</td> 
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Redirect URL', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td><input type="text" size="50" name="aiowps_cb_redirect_url" value="<?php echo $aio_wp_security_premium->configs->get_value('aiowps_cb_redirect_url'); ?>" />
				<span class="description"><?php _e('Set the value for the URL where you want to send the blocked visitor to', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
				</td> 
			</tr>
			
			<tr valign="top">
				<th scope="row"><?php _e('Select countries', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
			<td>
				<input type="checkbox" id="aiowps_cb_general_select_all" name="aiowps_cb_general_select_all" value="1" <?php if (count($saved_blocked_countries) === count($countries_array)) echo 'checked="checked"';?>/><label for="aiowps_cb_general_select_all">Select/De-select All</label>
			<ul class="aiowps-checkbox-grid">
				<?php
				asort($countries_array);
				foreach ($countries_array as $c_code => $c_name) {
					if (in_array($c_code, $saved_blocked_countries)) {
						$check_txt = ' checked="checked"';
					} else {
						$check_txt = '';
					}
					echo '<li><input type="checkbox" class="aiowps_country_checkbox" id="aiowps_country_checkbox_' . $c_code . '" name="aiowps_country_checkbox_' . $c_code . '"'.$check_txt.' value="' . $c_code . '" /><label for="aiowps_country_checkbox_' . $c_code . '">' . $c_name . '</label></li>';
				}
				?>
			</ul>
			</td>
			</tr>
		</table>
		</div></div>
		<input type="submit" name="aiowps_save_country_blocking_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
		</form>
		<script type="text/javascript">
		jQuery(function($) {

			$('.form-table').on('click', '#aiowps_cb_general_select_all', function(){
				if (this.checked) {
					$('.aiowps_country_checkbox').prop('checked', true);
				} else {
					$('.aiowps_country_checkbox').prop('checked', false);
				}
			});
		});
		</script>
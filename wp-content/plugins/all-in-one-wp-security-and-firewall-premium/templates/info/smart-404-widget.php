<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access allowed'); ?>

<div class="aio_yellow_box">
	<p><strong><?php echo __('# 404 Events Today:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $num_404_today; ?></strong></p>
	<p><strong><?php echo __('# IPs Permanently Blocked Today:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $todays_blocked_count; ?></strong></p>
	<hr><p><strong><?php echo __('All Time Total IPs Blocked:', 'all-in-one-wp-security-and-firewall-premium') . ' ' . $total_count; ?></strong></p>
</div>
<p><a class="button" href="admin.php?page=<?php echo AIOWPS_SMART_404_SETTINGS_MENU_SLUG; ?>&tab=tab2" target="_blank"><?php _e('View Blocked IPs', 'all-in-one-wp-security-and-firewall-premium'); ?></a></p>		
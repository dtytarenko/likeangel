<?php 
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

//if (!get_option('plugin_do_uninstall', false)) exit;

$options = array(
	'wpadmin_ga4mid' ,
	'wpadmin_ga4secr' ,
	'wpadmin_gadsid' ,
	'wpadmin_tmid' ,
	'wpadmin_fbid' ,
	'wpadmin_fbapisecr' ,
	'wpadmin_fbapiver', 
	'wpadmin_fbtestcode',
	'wpadmin_gfeed',
	'wpadmin_fbfeed',
	'wpadmin_feed_color_attr', 
	'wpadmin_feed_size_attr',
	'wpadmin_add_tm' , 
	'wpadmin_add_ga' , 
	'wpadmin_add_fb' , 
	'wpadmin_add_gads' ,
	'wpadmin_outofst', 
	'wpadmin_brand',
	'wpadmin_brand_taxonomy',
	'wpadmin_secret_api',
);
foreach ($options as $option) {
	if (get_option($option)) delete_option($option);
}
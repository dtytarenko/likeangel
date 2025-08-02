<?php

if (!defined('ABSPATH')) exit;

class AIOWPS_SMART_404_Installer {

	/**
	 * Runs the installer for Smart 404.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return void
	 */
	public static function run_installer() {
		global $wpdb;
		if (function_exists('is_multisite') && is_multisite()) {
			// check if it is a network activation - if so, run the activation function for each blog id
			if (isset($_GET['networkwide']) && 1 == $_GET['networkwide']) {
				$old_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					// AIOWPS_SMART_404_Installer::create_db_tables();
					AIOWPS_SMART_404_Configure_Settings::add_option_values();
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		// AIOWPS_SMART_404_Installer::create_db_tables();
		AIOWPS_SMART_404_Configure_Settings::add_option_values();
	}
	
	public static function create_db_tables() {

	}
}

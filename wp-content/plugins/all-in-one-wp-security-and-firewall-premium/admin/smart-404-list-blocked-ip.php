<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_List_404_Blocked_IP extends AIOWPSecurity_List_Table {

	/**
	 * Sets up some table attributes (i.e: the plurals and whether it's ajax or not)
	 */
	public function __construct() {


		// Set parent defaults
		parent::__construct(array(
			'singular' => 'item',     // singular name of the listed records
			'plural' => 'items',    // plural name of the listed records
			'ajax' => false        // does this table support ajax?
		));

	}

	/**
	 * Returns the default column item
	 *
	 * @param object $item        - item from which column data is returned
	 * @param string $column_name - column name to be fetched from item
	 * @return string
	 */
	public function column_default($item, $column_name) {
		return $item[$column_name];
	}

	/**
	 * Returns html string for blocked ip column
	 *
	 * @param array $item - item from which column data is returned
	 *
	 * @return string - an html string
	 */
	public function column_blocked_ip($item) {
		$tab = 'tab2';
		// Add nonce to delete URL
		$unblock_ip_url = sprintf('admin.php?page=%s&tab=%s&action=%s&blocked_id=%s', AIOWPS_SMART_404_SETTINGS_MENU_SLUG, $tab, 'unblock_ip', $item['id']);
		// Add nonce to unlock IP URL
		$unblock_ip_nonce = wp_nonce_url($unblock_ip_url, "unblock_ip", "aiowps_nonce");

		// Build row actions
		$actions = array(
			'unblock' => '<a href="' . $unblock_ip_nonce . '" onclick="return confirm(\'Are you sure you want to unblock this IP address?\')">Unblock</a>',
		);

		// Return the user_login contents
		return sprintf('%1$s <span style="color:silver"></span>%2$s',
			/*$1%s*/
			$item['blocked_ip'],
			/*$2%s*/
			$this->row_actions($actions)
		);
	}


	/**
	 * Returns cb column html to be rendered.
	 *
	 * @param array $item - data for the columns on the current row
	 *
	 * @return string - the html to be rendered
	 */
	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'],  // Let's simply repurpose the table's singular label
			/*$2%s*/
			$item['id']                // The value of the checkbox should be the record's id
		);
	}

	/**
	 * Sets the columns for the table
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />', // Render a checkbox
			'blocked_ip' => 'Blocked IP',
			'block_reason' => 'Reason',
			'country_origin' => 'Country',
			'blocked_date' => 'Date'
		);
	}

	/**
	 * Sets which of the columns the table data can be sorted by
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'blocked_ip' => array('blocked_ip', false),
			'block_reason' => array('block_reason', false),
			'country_origin' => array('country_origin', false),
			'blocked_date' => array('blocked_date', false)
		);
	}

	/**
	 * This function will display a list of bulk actions for the list table
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'unblock' => 'Unblock'
		);
	}

	/**
	 * This function will process the bulk action request
	 *
	 * @return void
	 */
	private function process_bulk_action() {
		if (empty($_GET['_wpnonce']) || !isset($_GET['_wp_http_referer'])) return;

		$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($_GET['_wpnonce'], 'bulk-items');
		
		if (is_wp_error($result)) return;
		
		if ('unblock' === $this->current_action()) {
			// Process unlock bulk actions
			if (!isset($_GET['item'])) {
				AIOWPSecurity_Admin_Menu::show_msg_error_st(__('Please select some records using the checkboxes', 'all-in-one-wp-security-and-firewall-premium'));
			} else {
				$items = $_GET['item'];
				$sanitized_items = array_map('sanitize_text_field', $items); // Sanitize each item
				$this->unblock_ip_address($sanitized_items);
			}
		}
	}


	/**
	 * This function will delete selected records from the "AIOWPSEC_TBL_PERM_BLOCK" table.
	 * The function accepts either an array of IDs or a single ID
	 *
	 * @param array|int $entries - an id or array of ids to be unlocked
	 *
	 * @return void
	 */
	public function unblock_ip_address($entries) {
		global $wpdb;
		if (is_array($entries)) {
			// Delete multiple records
			$delete_command = "DELETE FROM " . AIOWPSEC_TBL_PERM_BLOCK . " WHERE id  IN(".implode(', ', array_fill(0, count($entries), '%s')).")";
			$delete_query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($delete_command), $entries));

			$result = $wpdb->query($delete_query);
			if (null != $result) {
				AIOWPSecurity_Admin_Menu::show_msg_record_deleted_st();
			}
		} elseif (null != $entries) {

			// Delete single record
			$delete_command = $wpdb->prepare("DELETE FROM " . AIOWPSEC_TBL_PERM_BLOCK . " WHERE id = %s", absint($entries));
			$result = $wpdb->query($delete_command);
			if (null != $result) {
				AIOWPSecurity_Admin_Menu::show_msg_record_deleted_st();
			}
		}
	}

	/**
	 * Grabs the data from database and handles the pagination
	 *
	 * @return void
	 */
	public function prepare_items() {
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 100;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		global $wpdb;
		$block_table_name = AIOWPSEC_TBL_PERM_BLOCK;

		/* -- Ordering parameters -- */
		// Parameters that are going to be used to order the result
		isset($_GET["orderby"]) ? $orderby = strip_tags($_GET["orderby"]) : $orderby = '';
		isset($_GET["order"]) ? $order = strip_tags($_GET["order"]) : $order = '';

		$orderby = !empty($orderby) ? esc_sql($orderby) : 'id';
		$order = !empty($order) ? esc_sql($order) : 'DESC';

		$orderby = AIOWPSecurity_Utility::sanitize_value_by_array($orderby, $sortable);
		$order = AIOWPSecurity_Utility::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));

		if (isset($_GET['s'])) {
			$search_term = sanitize_text_field($_GET['s']);
			$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $block_table_name . " WHERE block_reason=%s AND (`blocked_ip` LIKE '%%%s%%' OR `block_reason` LIKE '%%%s%%' OR `country_origin` LIKE '%%%s%%' OR `blocked_date` LIKE '%%%s%%')", '404', $search_term, $search_term, $search_term, $search_term), ARRAY_A);
		} else {
			$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $block_table_name . " WHERE block_reason=%s ORDER BY $orderby $order", '404'), ARRAY_A);
		}

		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
		$this->items = $data;
		$this->set_pagination_args(array(
			'total_items' => $total_items,                  // WE have to calculate the total number of items
			'per_page' => $per_page,                     // WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)   // WE have to calculate the total number of pages
		));
	}
}

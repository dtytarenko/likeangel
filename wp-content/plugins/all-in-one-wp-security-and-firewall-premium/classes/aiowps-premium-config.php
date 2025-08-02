<?php

if (!defined('ABSPATH')) exit;

class AIOWPS_Premium_Config {

	public $configs;

	public $message_stack;

	public static $_this;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->message_stack = new stdClass();
	}

	/**
	 * Load plugin configs
	 *
	 * @return void
	 */
	public function load_config() {
		$this->configs = get_site_option('aiowps_premium_configs');
	}

	/**
	 * Get an option value
	 *
	 * @param string $key - Key for config
	 *
	 * @return mixed|string
	 */
	public function get_value($key) {
		return isset($this->configs[$key]) ? $this->configs[$key] : '';
	}

	/**
	 * THis function sets the value
	 *
	 * @param string $key   - key for config
	 * @param string $value - value for config
	 *
	 * @return void
	 */
	public function set_value($key, $value) {
		$this->configs[$key] = $value;
	}

	/**
	 * Adds value to configs
	 *
	 * @param string $key   - key for config
	 * @param string $value - value for config
	 * @return void
	 */
	public function add_value($key, $value) {
		if (!is_array($this->configs)) {
			$this->configs = array();
		}

		if (!array_key_exists($key, $this->configs)) {
			// It is safe to update the value for this key
			$this->configs[$key] = $value;
		}
	}

	/**
	 * Saves the config
	 *
	 * @return void
	 */
	public function save_config() {
		update_site_option('aiowps_premium_configs', $this->configs);
	}

	/**
	 * Delete all config option.
	 *
	 * @return void
	 */
	public function delete_config() {
		delete_site_option('aiowps_premium_configs');
	}

	/**
	 * Get stacked message
	 *
	 * @param string $key - key for message stack
	 * @return string
	 */
	public function get_stacked_message($key) {
		if (isset($this->message_stack->{$key})) return $this->message_stack->{$key};
		return "";
	}

	/**
	 * Set stacked message
	 *
	 * @param string $key   - key for config
	 * @param string $value - value for config
	 *
	 * @return void
	 */
	public function set_stacked_message($key, $value) {
		$this->message_stack->{$key} = $value;
	}

	/**
	 * Returns the config class
	 *
	 * @return AIOWPS_Premium_Config
	 */
	public static function get_instance() {
		if (empty(self::$_this)) {
			self::$_this = new self();
			self::$_this->load_config();
			return self::$_this;
		}
		return self::$_this;
	}
}

<?php
class wp_autoupdate
{
    /** 
* The plugin current version 
* @var string 
*/
    public $current_version;
    /** 
* The plugin remote update path 
* @var string 
*/
    public $update_path;
    /** 
* Plugin Slug (plugin_directory/plugin_file.php) 
* @var string 
*/
    public $plugin_slug;
    /** 
* Plugin name (plugin_file) 
* @var string 
*/
    public $slug;
    /** 
* Initialize a new instance of the WordPress Auto-Update class 
* @param string $current_version 
* @param string $update_path 
* @param string $plugin_slug 
*/
    function __construct($current_version, $update_path, $plugin_slug)
    {
        // Set the class public variables 
        $this->current_version = $current_version;
        $this->update_path = $update_path;
        $this->plugin_slug = $plugin_slug;
        $this->plugin_key = (get_option('wpadmin_secret_api')) ? get_option('wpadmin_secret_api') : 'trial';
        list ($t1, $t2) = explode('/', $plugin_slug);
        $this->slug = str_replace('.php', '', $t2);
		
		//error_log('$this->current_version '.$this->current_version. '$this->update_path ' . $this->update_path .'$this->plugin_slug '.$this->plugin_slug .'$t1, $t2' . $t1 .', '. $t2 . '$this->slug ' . $this->slug);
		
        // define the alternative API for updating checking 
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));
        // Define the alternative response for information checking 
        add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
    }
    /** 
* Add our self-hosted autoupdate plugin to the filter transient 
* 
* @param $transient 
* @return object $ transient 
*/
    public function check_update($transient)
    {
		
        if (empty($transient->checked)) {
            return $transient;
        }
		$information = $this->getRemote_information();
        // Get the remote version 
        $remote_version = $this->getRemote_version();
        // If a newer version is available, add the update 
        if (version_compare($this->current_version, $remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->plugin = $this->plugin_slug;
            $obj->new_version = $remote_version;
            $obj->url = $this->update_path;
            $obj->package = $information->download_link;
            $transient->response[$this->plugin_slug] = $obj;
        }
        //var_dump($transient);
        return $transient;
    }
    /** 
* Add our self-hosted description to the filter 
* 
* @param boolean $false 
* @param array $action 
* @param object $arg 
* @return bool|object 
*/
    public function check_info($false, $action, $arg)
    {
        if (isset($arg->slug) && $arg->slug === $this->slug) {
            $information = $this->getRemote_information();
            return $information;
        }
        return false;
    }
    /** 
* Return the remote version 
* @return string $remote_version 
*/
    public function getRemote_version()
    {		
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version', 'key' => $this->plugin_key, 'url' => parse_url( get_site_url(), PHP_URL_HOST ))));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			
            return $request['body'];
        }
        return false;
    }
    /** 
* Get information about the remote version 
* @return bool|object 
*/
    public function getRemote_information()
    {
		$request = wp_remote_post($this->update_path, array('body' => array('action' => 'info', 'key' => $this->plugin_key, 'url' => parse_url( get_site_url(), PHP_URL_HOST ) )));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }
        return false;
    }
    /** 
* Return the status of the plugin licensing 
* @return boolean $remote_license 
*/
    public function getRemote_license()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }
}
add_action( 'in_plugin_update_message-e-commerce-wpadmin-v2/e-commerce-wpadmin-v2.php', 'wpadmin_update_message', 10, 2 );
function wpadmin_update_message( $plugin_info_array, $plugin_info_object ) {
	if( empty( $plugin_info_array[ 'package' ] ) ) {
		echo __(' Please renew your license to update. You can change your license key in <a href="/wp-admin/admin.php?page=wpadmin_settings">E-commerce integration v2 > General options</a>', 'wpadminpro');
	}
}


add_action( 'upgrader_process_complete', 'my_upgrade_function',10, 2);

function my_upgrade_function( $upgrader_object, $options ) {
    $current_plugin_path_name = WPAECV2_FILE;
	//error_log('$current_plugin_path_name = '.$current_plugin_path_name);

    if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
       foreach($options['plugins'] as $each_plugin) {
          if ($each_plugin==$current_plugin_path_name) {
			 error_log('$each_plugin='.$each_plugin); 
             flush_rewrite_rules();
			 error_log('flush');
          }
       }
    }
}
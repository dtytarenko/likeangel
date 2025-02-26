<?php
# Check if class exist
if (!class_exists('MORKVAPROLICENSED'))
{
	/**
	 * Class for licensed morkva products
	 * 
	 * */
	Class MORKVAPROLICENSED
	{
		/**
	     * Slug for page in Woo Tab Sections
	     * 
	     * */
	    public $slug = 'morkva-licence-management';

	    /**
	     * Constructor for create menu
	     * 
	     * */
	    public function __construct()
	    {
	    	# Register settings
			add_action('admin_init', array($this, 'mrkv_licence_management_register_settings'));

	        # Add menu
	        add_action('admin_menu', array($this, 'mrkv_licence_management_func'));
	    }

	    public function mrkv_licence_management_register_settings()
	    {
	    	# List of plugin options
	        $options = array(
	            'mrkv_licence_management_api',
	        );

	        # Loop of option
	        foreach ($options as $option) 
	        {
	        	# Register option
	            register_setting('mrkv-licence-management-group', $option);
	        }
	    }

	    /**
	     * Add new page to menu
	     * */
	    public function mrkv_licence_management_func()
	    {
        	if(!isset($GLOBALS['admin_page_hooks'][$this->slug]))
	    	{
	    		# Add menu to WP
	        	add_menu_page(__('Morkva Licence', 'morkva-licence-management'), __('Morkva Licence', 'checkbox'), 'manage_options', $this->slug, array($this, 'mrkv_licence_management_content'), '');
	    	}
	    }

	    /**
	     * Show settings page content
	     * */
	    public function mrkv_licence_management_content()
	    {
	    	?>
	    	<style>
	    		.mrkv_licence_management{
	    			background: #fff;
				    padding: 10px 20px;
				    margin-top: 20px;
				    margin-right: 20px;
	    		}
	    		.mrkv_licence_management__row{
	    			display: flex;
	    			gap: 40px;
	    		}
	    		.mrkv_licence_management__row input{
	    			width: 100%;
	    		}
	    		.mrkv_licence_management__col{
	    			width: calc(50% - 20px);
	    		}
	    		.mrkv_licence_management__row label{
	    			display: block;
	    			margin-bottom: 10px;
	    		}
	    		.mrkv_licence_management .notice{
    			    margin: 0 0 20px 0;
	    		}
	    		.mrkv_licence_management__col .notice-mrkv{
			    	border-radius: 4px;
			    	padding: 10px;
	    		}
	    		.mrkv_licence_management__col ul{
    			    list-style: inside;
	    		}
	    		@media(max-width: 768px){
	    			.mrkv_licence_management__row{
	    				flex-direction: column;
	    			}
	    			.mrkv_licence_management__col{
	    				width: 100%;
	    			}
	    		}
	    	</style>	
	    		<div class="mrkv_licence_management">
	    			<h1><?php echo __('Morkva Licence Management', 'morkva-licence-management'); ?></h1>
	    			<?php settings_errors(); ?>
	    			<div class="mrkv_licence_management__row">
	    				<div class="mrkv_licence_management__col">
	    					<form method="post" action="options.php">
			    				<?php settings_fields('mrkv-licence-management-group'); ?>
			    				<label for="mrkv_licence_management_api"><?php echo __('Api Key', 'morkva-licence-management'); ?></label>
		    					<input id="mrkv_licence_management_api" name="mrkv_licence_management_api" type="password" value="<?php echo get_option('mrkv_licence_management_api'); ?>">
			    				<?php echo submit_button(__('Save', 'morkva-licence-management')); ?>
			    			</form>	
    					</div>
    					<div class="mrkv_licence_management__col">
    						<h3><?php echo __('Morkva Licence Status', 'morkva-licence-management'); ?></h3>
	    					<?php 
	    						$plugins = $this->get_plugin_license_api();

	    						if($plugins && is_array($plugins))
	    						{
	    							?>
	    							<p class="notice-mrkv" style="background-color: #40b740; color: #fff;"><?php echo __('Automatic update and support is available for plugins.', 'morkva-licence-management'); ?></p>
	    							<h3><?php echo __('Supported plugins', 'morkva-licence-management'); ?></h3>
	    							<ul>
	    								<?php 
	    									foreach($plugins as $plugin)
	    									{
	    										if($plugin['enabled'] == 'on')
	    										{
	    											?>
	    												<li><?php echo $plugin['name']; ?></li>
	    											<?php
	    										}
	    									}
	    								?>
	    							</ul>
	    							<?php
	    						}
	    						else
	    						{	
	    							?>
	    								<p class="notice-mrkv" style="background-color: #f34c4c; color: #fff;"><?php echo __('Automatic update and support is unavailable.', 'morkva-licence-management'); ?></p>
	    							<?php
	    						}
	    					?>					
    					</div>
	    			</div>	
	    		</div>
	    	<?php
	    }

	    /**
	     * Get api licensed
	     * */
	    public function get_plugin_license_api()
	    {
	    	$response = wp_remote_get( 'https://morkva.co.ua/wp-json/licenseManagement/v2?license_management=' . get_option('mrkv_licence_management_api'), array(
			    'headers' => array(
			    ),
			    'timeout' => 30,
			    'redirection' => 5,
			    'httpversion' => '1.1',
			    'sslverify' => true
			));

			if(isset($response['body']))
			{
				$data = json_decode($response['body'], true);

				if($data['status'] == 'true')
				{
					return $data['plugins'];
				}
				else
				{
					return false;
				}
			}
	    }
	}
}
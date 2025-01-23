<?php
add_action('admin_head', 'my_custom_fonts');

function my_custom_fonts() {
  echo '<style>
  #wpadminbar .awaiting-mod.wpadminpro {
		background-color: #d63638;
		border-radius: 9px;
		color: #fff;
		display: inline;
		padding: 1px 7px 1px 6px!important;
	}
    #adminmenu .awaiting-mod.wpadmin.pro {
    padding: 0px 3px;
    min-width: 12px;
    height: 12px;
    font-size: 10px;
    line-height: 1; 
} 
  </style>';
}

//Settings link under plugin row in the plugins list

add_filter( 'plugin_action_links_e-commerce-wpadmin-v2/e-commerce-wpadmin-v2.php', 'wpadmin_v2_settings_link' );
function wpadmin_v2_settings_link( $links ) {
    // Build and escape the URL. 
    $url = esc_url( add_query_arg(
        'page',
        'wpadmin_settings',
        get_admin_url() . 'admin.php'
    ) );
    // Create the link.
    $settings_link = "<a href='$url'>" . __( 'Settings', 'wpadminpro' ) . '</a>';
    // Adds the link to the end of the array.
    array_push(
        $links,
        $settings_link
    );
    return $links;
}

//Settings link under wpadmin top bar

add_action( 'admin_bar_menu', 'customize_my_wp_admin_bar', 80 );
function customize_my_wp_admin_bar( $wp_admin_bar ) {
	$notification_count = false; // <- here you should get correct count of updates

    //Get a reference to the view-site node to modify.
    $node = $wp_admin_bar->get_node('site-name');

    //Change target
    $node->title = $notification_count ? sprintf($node->title .' <span class="awaiting-mod wpadminpro">%d</span>', $notification_count) : $node->title;

    //Update Node.
    $wp_admin_bar->add_node($node);

}

function ld_theme_options_admin_bar_menu_v2($wp_admin_bar)
{
    $id = __('Ecommerce WPAdmin options','wpadminpro');
	$notification_count = false; // <- here you should get correct count of updates

    $wp_admin_bar->add_node(array(
        'parent' => 'site-name',
        'title' =>  $notification_count ? sprintf(__('E-commerce integration v2', 'wpadminpro').' <span class="awaiting-mod wpadminpro">%d</span>', $notification_count) : __('E-commerce integration v2', 'wpadminpro'),
        'href' => esc_url( add_query_arg(array('page' => 'wpadmin_settings'), admin_url('admin.php')) ),
        'id' => $id
    ));
}
add_action('admin_bar_menu', 'ld_theme_options_admin_bar_menu_v2');

function wpadmin_v2_options_assets() {
	//https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.css

///https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.js
	wp_enqueue_script( 'wpadmin_v2-script', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js');
	wp_enqueue_style( 'wpadmin_v2-style',  'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css');
}


function wpadmin_v2_setting_page() {
 
// add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )

    $notification_count = false; // <- here you should get correct count of updates
 
	/*$wpadmin_v2_options_page = add_options_page(__('WPadmin E-commerce integration v2', 'wpadminpro'), $notification_count ? sprintf(__('E-commerce integration v2', 'wpadminpro').' <span class="awaiting-mod wpadminpro">%d</span>', $notification_count) : __('E-commerce integration v2', 'wpadminpro'), 'manage_options', 'wpadmin_settings', 'wpadmin_v2_settings_page_callback');*/
	$wpadmin_v2_options_page = add_menu_page( __('WPadmin E-commerce integration v2', 'wpadminpro'), $notification_count ? sprintf(__('E-commerce integration v2', 'wpadminpro').' <span class="awaiting-mod wpadminpro">%d</span>', $notification_count) : __('E-commerce integration v2', 'wpadminpro'), 'manage_options', 'wpadmin_settings', 'wpadmin_v2_settings_page_callback', 'dashicons-chart-line', 69 );
;
 
	// custom_page_html_form is the function in which I have written the HTML for my custom plugin form.
	add_action( "admin_print_scripts-{$wpadmin_v2_options_page}", 'wpadmin_v2_options_assets' );
}
add_action('admin_menu', 'wpadmin_v2_setting_page');
/*
add_action( 'admin_menu', function() {
	global $menu;
	$notification_count = 2; // <- 
	$menu_item = wp_list_filter(
		$menu,
		array( 2 => 'options-general.php' ) // 2 is the position of an array item which contains URL, it will always be 2!
	);
	if ( ! empty( $menu_item )  ) {
		$menu_item_position = key( $menu_item ); // get the array key (position) of the element
		$menu[ $menu_item_position ][0] .= ' <sup><span class="awaiting-mod wpadminpro">!</span></sup>';
	}
});*/


function wpadmin_v2_register_settings() {
   register_setting( 'wpadmin_settings', 'wpadmin_ga4mid' );
   register_setting( 'wpadmin_settings', 'wpadmin_ga4secr' );
   register_setting( 'wpadmin_settings', 'wpadmin_gadsid' );
   register_setting( 'wpadmin_settings', 'wpadmin_gadsremarket' );
   register_setting( 'wpadmin_settings', 'wpadmin_tmid' );
   register_setting( 'wpadmin_settings', 'wpadmin_fbid' );
   register_setting( 'wpadmin_settings', 'wpadmin_fbapisecr' );
   register_setting( 'wpadmin_settings', 'wpadmin_fbapiver', [ 'default' => 'v19.0' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_fbtestcode');
   register_setting( 'wpadmin_settings', 'wpadmin_gfeed', [ 'default' => get_site_url().'/wpadminec-google.xml?&product_feed_lang=uk' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_fbfeed', [ 'default' => get_site_url().'/wpadminec-fb.xml?&product_feed_lang=uk' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_feed_color_attr', [ 'default' => 'pa_color' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_feed_size_attr', [ 'default' => 'pa_size' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_feed_custom_label0' );
   register_setting( 'wpadmin_settings', 'wpadmin_feed_custom_label1' );
   register_setting( 'wpadmin_settings', 'wpadmin_feed_custom_label2' );
   register_setting( 'wpadmin_settings', 'wpadmin_add_tm' , ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox']);
   register_setting( 'wpadmin_settings', 'wpadmin_add_ga' , ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox']);
   register_setting( 'wpadmin_settings', 'wpadmin_add_fb' , ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox']);   
   register_setting( 'wpadmin_settings', 'wpadmin_add_gads' , ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox']);
   register_setting( 'wpadmin_settings', 'wpadmin_remarket_gads_code' , ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox']);
   register_setting( 'wpadmin_settings', 'wpadmin_outofst',  ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox', 'default'  => 'yes']);
   register_setting( 'wpadmin_settings', 'wpadmin_brand');
   register_setting( 'wpadmin_settings', 'wpadmin_brand_taxonomy');
   register_setting( 'wpadmin_settings', 'wpadmin_secret_api', [ 'default' => 'trial' ] );
   register_setting( 'wpadmin_settings', 'wpadmin_adv_options',  ['sanitize_callback' => 'wpadmin_v2_sanitize_checkbox', 'default'  => 'no']);
}
add_action( 'admin_init', 'wpadmin_v2_register_settings' );

/*add_action('add_option_wpadmin_outofst', function( $option_name, $option_value ) {
    if( 'wpadmin_outofst' === $option_name ) {		
		$cache_key = 'wpadmin-xml-output-'.$feed. '-' .$lang;
		if (!$cache) delete_transient( $cache_key );
	}
}, 10, 2);*/

add_filter( 'register_setting_args', 'true_modify_defaults', 25, 4);
 
function true_modify_defaults( $args, $defaults, $option_group, $option_name ) {
	if( 'wpadmin_outofst' === $option_name ) {
		$args[ 'default' ] = 'yes';
	}
	return $args;
}
/*
function sample_admin_notice__success() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Done!', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'sample_admin_notice__success' );
*/

function wpadmin_v2_settings_page_callback(){
	//delete_option('wpadmin_outofst');
?>
	<style>
	.wpadminpro li .collapsible-header {
		background-color: #414548; color: #f0f0f1;
	}
	.wpadminpro li:first-child .collapsible-header {
		background-color: #1d2327; color: #f0f0f1;
	}
	.wpadminpro input:not([type]), .wpadminpro input[type=text]:not(.browser-default), .wpadminpro input[type=password]:not(.browser-default), 
	.wpadminpro input[type=email]:not(.browser-default), .wpadminpro input[type=url]:not(.browser-default), .wpadminpro input[type=time]:not(.browser-default), 
	.wpadminpro input[type=date]:not(.browser-default), .wpadminpro input[type=datetime]:not(.browser-default), .wpadminpro input[type=datetime-local]:not(.browser-default), 
	.wpadminpro input[type=tel]:not(.browser-default), .wpadminpro input[type=number]:not(.browser-default), .wpadminpro input[type=search]:not(.browser-default), .wpadminpro textarea.materialize-textarea {
		padding-top: 1rem;
	}
	</style>
	<div class="wpadminpro row">
		<form method="post" action="options.php">
			<div class="col s12">
				<?php settings_fields( 'wpadmin_settings' ); ?>
				<div class="row">
					<!-- <div class="col s12"></div> -->
					<div class="col s6"><h3><?php _e("Settings","wpadminpro");?></h3></div>
					<div class="col s6">
						<!-- <h3 class="switch" style="float:right;">
							<label>
							  Off
							  <input type="checkbox">
							  <span class="lever"></span>
							  On
							</label>
						</h3>-->
					</div>
				</div>
				<?php //delete_option( 'wpadmin_adv_options' ); 
				$class = (get_option('wpadmin_adv_options') == 'yes') ? '' : 'hide';?>
				
				<ul class="collapsible">
					<li  class="active">
					  <div class="collapsible-header"><?php _e("General options","wpadminpro");?></div>
					  <div class="collapsible-body">					  
						  <div class="row">
							<div class="col m12 s12">
								<h6 class="active"><?php _e("Enter your plugin key","wpadminpro");?></h6>
								  <div class="input-field">
									  <input placeholder="e.g., XORJY123HP5" id="wpadmin_secret_api" name="wpadmin_secret_api" type="text" value="<?php echo get_option('wpadmin_secret_api'); ?>">
									  <label for="wpadmin_secret_api"><?php _e("You'll get your key after purchase <a href='https://wordpress.co.ua/wpadmin-ecommerce' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>							
							<div class="col m4 s12">
								<h6 class="active"><?php _e('Show advanced options? Check to show.','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_adv_options" <?php checked( get_option('wpadmin_adv_options'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
						  </div>
					  </div>
					</li>
					<li>
					  <div class="collapsible-header"><?php _e("GA4 options","wpadminpro");?></div>
					  <div class="collapsible-body">					  
						  <div class="row">
							<div class="col m4 s12">
								<h6 class="active"><?php _e("GA4 Measurement ID","wpadminpro");?></h6>
								  <div class="input-field">
									  <input placeholder="e.g., G-12345" id="wpadmin_ga4mid" name="wpadmin_ga4mid" type="text" value="<?php echo get_option('wpadmin_ga4mid'); ?>">
									  <label for="wpadmin_ga4mid"><?php _e("GA4 Measurement ID you can take  <a href='https://support.google.com/analytics/answer/9539598?sjid=9720146569662259092-EU#find-G-ID' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m4 s12">
								<h6 class="active"><?php _e("GA4 API secret","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., Ev7Zcg5Ln4","wpadminpro");?>" id="wpadmin_ga4secr" name="wpadmin_ga4secr" type="text" value="<?php echo get_option('wpadmin_ga4secr'); ?>">
									<label for="wpadmin_ga4secr" ><?php _e("GA4 API secret you can take <a href='https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference#api_secret' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
						  </div>
					  </div>
					</li>
					<li>
					  <div class="collapsible-header"><?php _e('General options FB','wpadminpro');?></div>
					  <div class="collapsible-body">
						<div class="row">
							<div class="col m4 s12">
								<h6 class="active"><?php _e("FB Pixel ID","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., 1230975293864912321387","wpadminpro");?>" id="wpadmin_fbid" name="wpadmin_fbid" type="text" value="<?php echo get_option('wpadmin_fbid'); ?>">
									<label for="wpadmin_fbid" ><?php _e("FB Pixel ID you can take <a href='https://developers.facebook.com/docs/marketing-api/conversions-api/get-started/#pixel-id' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m4 s12">
								<h6 class="active"><?php _e("FB API token","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., Ev7Zcg5Ln4","wpadminpro");?>" id="wpadmin_fbapisecr" name="wpadmin_fbapisecr" type="text" value="<?php echo get_option('wpadmin_fbapisecr'); ?>">
									<label for="wpadmin_fbapisecr" ><?php _e("FB API token you can take <a href='https://developers.facebook.com/docs/marketing-api/conversions-api/get-started/#access-token' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m4 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("FB API version","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., v19.0","wpadminpro");?>" id="wpadmin_fbapiver" name="wpadmin_fbapiver" type="text" value="<?php echo get_option('wpadmin_fbapiver'); ?>">
									<label for="wpadmin_fbapiver" ><?php _e("Avaliable API versions you can find <a href='https://developers.facebook.com/docs/graph-api/changelog/#available-marketing-api-versions' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m4 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("FB API Test code","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., TEST35891","wpadminpro");?>" id="wpadmin_fbtestcode" name="wpadmin_fbtestcode" type="text" value="<?php echo get_option('wpadmin_fbtestcode'); ?>">
									<label for="wpadmin_fbtestcode" ><?php _e("Code to test server events using the test events tool","wpadminpro");?></label>
								  </div>
							</div>
					  </div>
					</li>
					<li>
					  <div class="collapsible-header"><?php _e('Product feeds options','wpadminpro');?></div>
					  <div class="collapsible-body">
						<div class="row">
							<div class="col m6 s12">
								<h6 class="active"><?php _e("Google Feed","wpadminpro");?></h6>
								  <div class="input-field">
									<input id="wpadmin_gfeed" name="wpadmin_gfeed" type="text" readonly value="<?php if (get_option('wpadmin_gfeed')) { echo get_option('wpadmin_gfeed'); } else { echo get_site_url().'/wpadminec-google.xml?&product_feed_lang=uk';} ?>" style="width: calc(100% - 20px);">
									<label for="wpadmin_gfeed" ><?php _e("Change uk to ru or en for additional language if WPML or Polylang is used (optional)","wpadminpro");?></label>
									<a href="<?php if (get_option('wpadmin_gfeed')) { echo get_option('wpadmin_gfeed'); } else { echo get_site_url().'/wpadminec-google.xml?&product_feed_lang=uk';} ?>" target="_blank">
										<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0 0 24 24" style="fill: firebrick;position: absolute;right: 0;max-width: 16px;top: -12px;">
										<path d="M 19.980469 2.9902344 A 1.0001 1.0001 0 0 0 19.869141 3 L 15 3 A 1.0001 1.0001 0 1 0 15 5 L 17.585938 5 L 8.2929688 14.292969 A 1.0001 1.0001 0 1 0 9.7070312 15.707031 L 19 6.4140625 L 19 9 A 1.0001 1.0001 0 1 0 21 9 L 21 4.1269531 A 1.0001 1.0001 0 0 0 19.980469 2.9902344 z M 5 3 C 3.9069372 3 3 3.9069372 3 5 L 3 19 C 3 20.093063 3.9069372 21 5 21 L 19 21 C 20.093063 21 21 20.093063 21 19 L 21 13 A 1.0001 1.0001 0 1 0 19 13 L 19 19 L 5 19 L 5 5 L 11 5 A 1.0001 1.0001 0 1 0 11 3 L 5 3 z"></path>
										</svg>
									</a>
								  </div>
							</div>
							<div class="col m6 s12">
								<h6 class="active"><?php _e("FB Feed","wpadminpro");?></h6>
								  <div class="input-field">
									<input id="wpadmin_fbfeed" name="wpadmin_fbfeed" type="text" readonly value="<?php if (get_option('wpadmin_fbfeed')) { echo get_option('wpadmin_fbfeed'); } else { echo get_site_url().'/wpadminec-fb.xml?&product_feed_lang=uk';} ?>" style="width: calc(100% - 20px);">
									<label for="wpadmin_fbfeed" ><?php _e("Change uk to ru or en for additional language if WPML or Polylang is used (optional)","wpadminpro");?></label>
									<a href="<?php if (get_option('wpadmin_fbfeed')) { echo get_option('wpadmin_fbfeed'); } else { echo get_site_url().'/wpadminec-fb.xml?&product_feed_lang=uk';} ?>" target="_blank">
										<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0 0 24 24" style="fill:firebrick;position: absolute;right: 0;width: 16px;top: -12px;">
									<path d="M 19.980469 2.9902344 A 1.0001 1.0001 0 0 0 19.869141 3 L 15 3 A 1.0001 1.0001 0 1 0 15 5 L 17.585938 5 L 8.2929688 14.292969 A 1.0001 1.0001 0 1 0 9.7070312 15.707031 L 19 6.4140625 L 19 9 A 1.0001 1.0001 0 1 0 21 9 L 21 4.1269531 A 1.0001 1.0001 0 0 0 19.980469 2.9902344 z M 5 3 C 3.9069372 3 3 3.9069372 3 5 L 3 19 C 3 20.093063 3.9069372 21 5 21 L 19 21 C 20.093063 21 21 20.093063 21 19 L 21 13 A 1.0001 1.0001 0 1 0 19 13 L 19 19 L 5 19 L 5 5 L 11 5 A 1.0001 1.0001 0 1 0 11 3 L 5 3 z"></path>
										</svg>
									</a>
								  </div>
							</div>
							<div class="col m3 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("Choose color attribute","wpadminpro");?></h6>
								  <div class="input-field">				
									<select class="select-one" id="wpadmin_feed_color_attr" name="wpadmin_feed_color_attr">
										<option value="0"> - </option>
										<?php echo wpadmin_v2_get_attributes('wpadmin_feed_color_attr'); ?>		
									</select>								
									<label for="wpadmin_feed_color_attr" ><?php _e("Choose attribute wich is used as color on product if exists.","wpadminpro");?></label>	
								  </div>
							</div>
							<div class="col m3 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("Choose size attribute","wpadminpro");?></h6>
								  <div class="input-field">				
									<select class="select-one" id="wpadmin_feed_size_attr" name="wpadmin_feed_size_attr">
										<option value="0"> - </option>
										<?php echo wpadmin_v2_get_attributes('wpadmin_feed_size_attr'); ?>		
									</select>								
									<label for="wpadmin_feed_size_attr" ><?php _e("Choose attribute wich is used as size on product if exists.","wpadminpro");?></label>	
								  </div>
							</div>
							<div class="col m3 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("Choose attribute for custom_label","wpadminpro");?>_0</h6>
								  <div class="input-field">				
									<select class="select-one" id="wpadmin_feed_custom_label0" name="wpadmin_feed_custom_label0">
										<option value="0"> - </option>
										<?php echo wpadmin_v2_get_attributes('wpadmin_feed_custom_label0'); ?>		
									</select>								
									<label for="wpadmin_feed_custom_label0" ><?php _e("Custom attribute custom_label_X , details <a href='https://support.google.com/merchants/answer/6324473?hl=uk' target='_blank'>here</a>","wpadminpro");?></label>	
								  </div>
							</div>
							<div class="col m3 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e("Choose attribute for custom_label","wpadminpro");?>_1</h6>
								  <div class="input-field">				
									<select class="select-one" id="wpadmin_feed_custom_label1" name="wpadmin_feed_custom_label1">
										<option value="0"> - </option>
										<?php echo wpadmin_v2_get_attributes('wpadmin_feed_custom_label1'); ?>		
									</select>								
									<label for="wpadmin_feed_custom_label1" ><?php _e("Custom attribute custom_label_X , details <a href='https://support.google.com/merchants/answer/6324473?hl=uk' target='_blank'>here</a>","wpadminpro");?></label>	
								  </div>
							</div>
							<div class="col m4 s12 <?php echo $class;?>">
								<h6 class="active"><?php _e('Include OUT of Stock products in feed or not? Check to include.','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_outofst" <?php checked( get_option('wpadmin_outofst'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
					  </div>
					</li>
					<li style="display:none2;">
						<div class="collapsible-header"><?php _e("General options for Google Ads","wpadminpro");?></div>
						<div class="collapsible-body">					  
							<div class="row">
								<div class="col m3 s12">
									<h6 class="active"><?php _e("Google Ads tag ID","wpadminpro");?></h6>
									  <div class="input-field">
										<input placeholder="<?php _e("e.g., AW-XXXXXXXXX","wpadminpro");?>" id="wpadmin_gadsid" name="wpadmin_gadsid" type="text" value="<?php echo get_option('wpadmin_gadsid'); ?>">
										<label for="wpadmin_gadsid" ><?php _e("Google Ads tag ID you can take <a href='https://support.google.com/google-ads/answer/12326985?hl=en' target='_blank'>here</a>","wpadminpro");?></label>
									  </div>
								</div>
								<div class="col m3 s12">
									<h6 class="active"><?php _e("Conversion Label","wpadminpro");?></h6>
									  <div class="input-field">
										<input placeholder="<?php _e("e.g., 6c0C1_11FCOzR3e4","wpadminpro");?>" id="wpadmin_gadsremarket" name="wpadmin_gadsremarket" type="text" value="<?php echo get_option('wpadmin_gadsremarket'); ?>">
										<label for="wpadmin_gadsremarket" ><?php _e("Google Ads conversion Label","wpadminpro");?></label>
									  </div>
								</div>
								<div class="col m3 s12">
									<h6 class="active"><?php _e('Add Dynamic Remarketing code?','wpadminpro');?></h6>
									<div class="switch">
										<label>
										  <?php _e('No','wpadminpro');?>
										  <input type="checkbox" name="wpadmin_remarket_gads_code" <?php checked( get_option('wpadmin_remarket_gads_code'), 'yes' ) ?>>
										  <span class="lever"></span>
										  <?php _e('Yes','wpadminpro');?>
										</label>
									</div>
								</div>
							</div>
						</div>
					</li>
					<li class=" <?php echo $class;?>">
					  <div class="collapsible-header"><?php _e('Additional codes settings','wpadminpro');?></div>
					  <div class="collapsible-body">
						<div class="row">
							<div class="col m6 s12">
								<h6 class="active"><?php _e("Default brand name","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("My own Brand","wpadminpro");?>" id="wpadmin_brand" name="wpadmin_brand" type="text" value="<?php echo get_option('wpadmin_brand'); ?>">
									<label for="wpadmin_brand" ><?php _e("Enter the name of the Brand you wish to use if Brand was not specified in product data","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m6 s12">
								<h6 class="active"><?php _e("Choose brand taxonomy","wpadminpro");?></h6>
								  <div class="input-field">				
									<select class="select-one" id="wpadmin_brand_taxonomy" name="wpadmin_brand_taxonomy">
										<option value="0"> - </option>
										<?php echo wpadmin_v2_get_taxonomies(); ?>		
									</select>								
									<label for="wpadmin_brand_taxonomy" ><?php _e("Choose taxonomy wich is used as brand on product.","wpadminpro");?></label>	
								  </div>
							</div>
						</div>
						<div class="row">
							<div class="col m3 s12">
								<h6 class="active"><?php _e("TAG Manager ID","wpadminpro");?></h6>
								  <div class="input-field">
									<input placeholder="<?php _e("e.g., G-XXXXXXXXX","wpadminpro");?>" id="wpadmin_tmid" name="wpadmin_tmid" type="text" value="<?php echo get_option('wpadmin_tmid'); ?>">
									<label for="wpadmin_tmid" ><?php _e("Additional information you can find <a href='https://support.google.com/tagmanager/answer/11994839?hl=en&ref_topic=12403939&sjid=9720146569662259092-EU' target='_blank'>here</a>","wpadminpro");?></label>
								  </div>
							</div>
							<div class="col m2 s12">
								<h6 class="active"><?php _e('Add TAG Manager code?','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_add_tm" <?php checked( get_option('wpadmin_add_tm'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
							<div class="col m2 s12">
								<h6 class="active"><?php _e('Add analytics code?','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_add_ga" <?php checked( get_option('wpadmin_add_ga'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
							<div class="col m2 s12">
								<h6 class="active"><?php _e('Add FB pixel code?','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_add_fb" <?php checked( get_option('wpadmin_add_fb'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
							<div class="col m2 s12">
								<h6 class="active"><?php _e('Add Google ADS code?','wpadminpro');?></h6>
								<div class="switch">
									<label>
									  <?php _e('No','wpadminpro');?>
									  <input type="checkbox" name="wpadmin_add_gads" <?php checked( get_option('wpadmin_add_gads'), 'yes' ) ?>>
									  <span class="lever"></span>
									  <?php _e('Yes','wpadminpro');?>
									</label>
								</div>
							</div>
						  </div>
					  </div>
					</li>
					<li style="display:none2;">
						<div class="collapsible-header"><?php _e("Advertising with guarantee","wpadminpro");?></div>
						<div class="collapsible-body">					  
							<div class="row">
								<div class="col m12 s12">									
									<?php _e("Text for Advertising with guarantee","wpadminpro");?>
								</div>
							</div>
						</div>
					</li>
				</ul>
			</div>
			<div class="row">
				<div class="col s6">
					<?php  submit_button( __( 'Save changes', 'wpadminpro' ), 'primary btn' ); ?>
				</div>
				<?php if (defined('WPAECV2_INSTALL')) { ?>
				<div class="col s6">
					<p class="submit">
						<a href="<?php echo admin_url(); ?>update-core.php?force-check=1" class="button button-secondary btn"><?php _e( 'Go to update page', 'wpadminpro' );?></a>
					</p>
				</div>
				<?php } ?>
			</div>
		</form>
	</div>
  <script>

  jQuery(document).ready(function($){
    $('.collapsible').collapsible();
    $('.tooltipped').tooltip();
	 $('select').formSelect();
  });
  </script>
<?php
}


// custom sanitization function for a checkbox field
function wpadmin_v2_sanitize_checkbox( $value ) {
	return ('on' === $value OR 'yes' === $value) ? 'yes' : 'no';
}

function wpadmin_v2_get_taxonomies() {
	
	$html = '';
	$args = array(
		'object_type' => array(
			'product',
		),
		//'public'   => true,
		//'_builtin' => false
	  
	); 
	$output = 'objects'; // or objects
	$operator = 'and'; // 'and' or 'or'
	$taxonomies = get_taxonomies( $args, $output, $operator ); 
	if ( $taxonomies ) {
		$selected = get_option('wpadmin_brand_taxonomy');
		//$html = '<select class="icons" id="wpadmin_brand_taxonomy" name="wpadmin_brand_taxonomy">';
		//$html .= '<option value="0"> - </option>';
		
		foreach ( $taxonomies  as $taxonomy ) {
			
			$sel = ($selected == $taxonomy->name) ? 'selected' : '';
			$html .= '<option value="'.$taxonomy->name.'" '.$sel.' >'.$taxonomy->labels->name.'</option>';
		}
		//$html .= '</select>';
	} 
	return $html;
}

function wpadmin_v2_get_attributes($attr) {
	
	$html = '';	
	$selected = get_option($attr);
	
		
	foreach( wc_get_attribute_taxonomies() as $values ) {
		
		$sel = ($selected == 'pa_' .$values->attribute_name) ? 'selected' : '';
		
		//$term_names = get_terms( array('taxonomy' => 'pa_' . $values->attribute_name ) );
		//echo '<li><strong>' . $values->attribute_label . '</strong>: ' . implode(', ', $term_names);
		$html .= '<option value="'.'pa_' .$values->attribute_name.'" '.$sel.' >'.$values->attribute_label.'</option>';
	}
	
	
	return $html;
}
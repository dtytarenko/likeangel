<?php
/***********************************************************************/
/**************************** Log and Debug ****************************/
/***********************************************************************/
function wpadmin_log($data) {
	$file = WPAECV2_PATH.'log.txt';
	// Open the file to get existing content
	//$current = file_get_contents($file);
	//$current .= $data;
	if (defined('DEBUG') && DEBUG == true) {
		file_put_contents($file, PHP_EOL . $data, FILE_APPEND);
	}
}

/******************************************** Feed url register and rewrite ************************************************/
function wpadmin_myxml_rewrite_v2() {
    add_rewrite_rule( '^wpadminec\-google\.xml', 'index.php?product_feed=google', 'top' );
    add_rewrite_rule( '^wpadminec\-fb\.xml', 'index.php?product_feed=fb', 'top' );
	// maybe flush rewrite rules if it was not previously in the option.
    if ( ! isset( $rules[ '^wpadminec\-google\.xml' ] ) OR ! isset( $rules[ '^wpadminec\-fb\.xml' ] ) ) {
        flush_rewrite_rules();
    }
}
add_action( 'init', 'wpadmin_myxml_rewrite_v2', 10 );

function wpadmin_myxml_query_var_v2( $public_query_vars ) {
    $public_query_vars[] = 'product_feed';
    $public_query_vars[] = 'product_feed_lang';
    $public_query_vars[] = 'show_feed_error';
    $public_query_vars[] = 'disable_cache';
    return $public_query_vars;
}
add_filter( 'query_vars', 'wpadmin_myxml_query_var_v2', 10, 1 );


function wpadmin_myxml_request_v2( $wp ) {
	$lang = 'ua';
	$feed = 'google';
	$error = false;
	$cache = true;
    if ( isset( $wp->query_vars['product_feed'] )) {
		if (isset($wp->query_vars['product_feed']) && esc_attr(sanitize_text_field($wp->query_vars['product_feed'])) != '') {
			$feed = esc_attr(sanitize_text_field($wp->query_vars['product_feed']));
		}
		if (isset($wp->query_vars['product_feed_lang']) && esc_attr(sanitize_text_field($wp->query_vars['product_feed_lang'])) != '') {
			$lang = esc_attr(sanitize_text_field($wp->query_vars['product_feed_lang']));
		}
		if (isset($wp->query_vars['show_feed_error']) && esc_attr(sanitize_text_field($wp->query_vars['show_feed_error'])) == 1) {
			$error = true;
		}
		if (isset($wp->query_vars['disable_cache']) && esc_attr(sanitize_text_field($wp->query_vars['disable_cache'])) == 1) {
			$cache = false;
		}
		header('Content-type: application/xml');
		google_feed_v2($lang,$feed,$error,$cache);
		//die;
        exit;
    }
}
add_action( 'parse_request', 'wpadmin_myxml_request_v2', 10, 1 );

/******************************************** /Feed url rewrite ************************************************/


/******************************************* ajax *******************************************************/
//Enqueue  Scripts
function wpadmin_v2_enqueue_scripts() {
	if (is_product()) {
		wp_enqueue_script( 'wpadminpro-ajax',  plugin_dir_url(__DIR__ ) . 'js/wpadminpro-ajax.js', array( 'jquery' ), null, true );
		wp_localize_script( 'wpadminpro-ajax', 'wpadminpro_ajax_object',
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'product_id' => get_the_ID()
			)
		);
	} else if ( is_checkout() && !is_wc_endpoint_url( 'order-received' ) && !is_wc_endpoint_url( 'order-pay' )) {
		wp_enqueue_script( 'wpadminpro-ajax',  plugin_dir_url(__DIR__ ) . 'js/wpadminpro-ajax.js', array( 'jquery' ), null, true );
		wp_localize_script( 'wpadminpro-ajax', 'wpadminpro_ajax_object',
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'checkout' => 1
			)
		);
		
	} elseif( (is_wc_endpoint_url( 'order-pay' ) OR is_wc_endpoint_url( 'order-received' ) ) && !empty( $_GET[ 'key' ] ) ) {
		
        $order_id = wc_get_order_id_by_order_key($_GET["key"]);
        $payorder = wc_get_order($order_id);	
		$wpadmin_gadsid = (get_option('wpadmin_gadsid')) ? get_option('wpadmin_gadsid') : '';
		$wpadmin_gadsremarket = (get_option('wpadmin_gadsremarket')) ? get_option('wpadmin_gadsremarket') : '';
		$wpadmin_add_gads = (get_option('wpadmin_add_gads')) ? get_option('wpadmin_add_gads') : 'no';

		if ($wpadmin_add_gads == 'yes' && $wpadmin_gadsid != '' && $wpadmin_gadsremarket != '') {

			if (!isset($_COOKIE['purchase_'.$order_id])) {
				if (!current_user_can('edit_others_pages')) {
					setcookie('purchase_' . $order_id, $order_id, strtotime('+30 day'));
				}

				$html_google_ads = '
									<script>
									window.dataLayer = window.dataLayer || [];
  									function gtag(){dataLayer.push(arguments);}
									console.log("'.$wpadmin_gadsid.'/'.$wpadmin_gadsremarket.'");
									console.log("value: '.$payorder->get_total().'");
									console.log("currency: '.get_woocommerce_currency().'");
									console.log("transaction_id: '.$order_id.'" );
									  gtag("event", "purchase", {
										  "send_to": "'.$wpadmin_gadsid.'/'.$wpadmin_gadsremarket.'", 
										  "value": '.$payorder->get_total().', 
										  "currency": "'.get_woocommerce_currency().'",
										  "transaction_id": "'.$order_id.'" 
									  });
									</script>

				';

				echo $html_google_ads;

			}
			
		}
    }
}
add_action('wp_enqueue_scripts', 'wpadmin_v2_enqueue_scripts');

// The ajax answer()
function wpadmin_v2_handle_ajax_request() {
    // Перевірка наявності параметра "action" в AJAX-запиті
    if (isset($_POST['action'])) {
        $action = sanitize_text_field($_POST['action']);
        $product_id = absint($_POST['product_id'], true);
        $variation_id = absint($_POST['variation_id'], true);
        $location = esc_url_raw($_POST['location']);

        // Обробка різних дій залежно від значення параметра "action"
        switch ($action) {
            case 'wpadmin_item_view':
                // Ваш код для обробки AJAX-запиту тут
                // Можна викликати функції, виконати операції та повернути результат
                $response = array(
                    'status' => 'success',
                    'message' => 'АJAX-запит успішно оброблено',
                );
                break;
            default:
                $response = array(
                    'status' => 'error',
                    'message' => 'Невідома дія',
                );
                break;
        }

		if ($response['status'] == 'success') {
			if ($product_id > 0) wpadmin_v2_woocommerce_item_view($product_id,$location);
			if ($variation_id > 0) wpadmin_v2_woocommerce_item_view($variation_id,$location);
		}
        // Відправка відповіді назад до JavaScript
        wp_send_json($response);
    }

    // Завершення виконання скрипта
    wp_die();
}

// Додавання обробників AJAX-запитів до WordPress
add_action('wp_ajax_wpadmin_item_view', 'wpadmin_v2_handle_ajax_request');
add_action('wp_ajax_nopriv_wpadmin_item_view', 'wpadmin_v2_handle_ajax_request');



/******************************************* /ajax *******************************************************/

function product_get_variant_line_v2( $variation_id, $for_feed = false) {
	$out            = '';
	$variation_data = wc_get_product_variation_attributes( $variation_id );

	if ( is_array( $variation_data ) && ! empty( $variation_data ) ) {
		$out = "'" . esc_js( wc_get_formatted_variation( $variation_data, true ) ) . "'";
	}

	if ($for_feed) {		
		$product_variant_arr = explode(",",$out);

		foreach($product_variant_arr as $key=>$val) {
			$product_variant_arr[$key] = str_replace(':','',strstr($val, ':'));
		}
		$out = implode(" | ",$product_variant_arr);
	}
	return $out;
}


function wpadmin_v2_get_user_agent(){	
	if ( class_exists( 'WooCommerce' ) ) {
		return wc_get_user_agent();
	} else {
		return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36';
	}
}

function wpadmin_v2_get_ip_address(){
	if ( class_exists( 'WooCommerce' ) ) {
		$e = new WC_Geolocation();
		return $e->get_ip_address();
	} else {
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
			if (array_key_exists($key, $_SERVER) === true){
				foreach (explode(',', $_SERVER[$key]) as $ip){
					$ip = trim($ip);
	 
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
						return $ip;
					}
				}
			}
		}
    }
}

// Helper function for clearing text vars
function wpadmin_clear_text_v2($text) {
    return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
    //return htmlspecialchars(strip_tags(preg_replace('/[^\PC\s]/u', '', $text)), ENT_QUOTES, 'UTF-8');
}

function wpadmin_v2_get_product_data($cur_product_id=0) {
	$product_data =  Array(); 
	
	if ($cur_product_id == 0) return $product_data;
	
	$brand_name = (get_option('wpadmin_brand')) ? wpadmin_clear_text_v2(get_option('wpadmin_brand')) : wpadmin_clear_text_v2(__('BRAND NAME','wpadminpro'));
	
	//Get product instance
	$product = wc_get_product($cur_product_id);
	
	//check is it variation or parent product
	//$is_variation = ((get_post($cur_product_id))->post_type == "product_variation") ? true : false;
	$parent_id = $product->get_parent_id();
	$is_variation = ($parent_id == 0) ? false : true;
	
	if ($is_variation) $parent_product = wc_get_product( $parent_id );
	
	//if parent => set parent id
	//$parent_id = ($is_variation && ($parent_id != 0)) ? $parent_id : $cur_product_id;
	
	//Get Categories
	$product_cat_arr = Array(); 
	
	foreach (get_the_terms(($is_variation) ? $parent_id : $cur_product_id, 'product_cat') as $category) {
		$product_cat_arr[] = $category->name;
	}
	$product_data['cats'] = implode(", ", $product_cat_arr); //робимо список категорій з масиву
	
	 //Get Brands
	 
	if (get_option('wpadmin_brand_taxonomy')) {
		$brand_attr = get_option('wpadmin_brand_taxonomy');
	} else {
		$brand_attr = '';
	}
	
	$brand_terms = get_the_term_list( ($is_variation) ? $parent_id : $cur_product_id, $brand_attr, '', ', ' );
	if (!is_wp_error( $brand_terms ) && $brand_terms) {
		$product_data['brand']  = strip_tags($brand_terms);
	} else {
		$product_data['brand']  = $brand_name;
	}
	
	$product_data['name']  = wpadmin_clear_text_v2($product->get_title());
	
	$product_data['price']  = ( $product->get_price() != "") ? $product->get_price() : 0;
	
	if ($product->is_type( 'variable' ) && !$is_variation) {
		
		$var_price = 0;

		// Перевіряємо, чи встановлено варіації за замовчуванням
		$attributes = $product->get_default_attributes();

		if ($attributes) {
			$attributes_arr = Array();
			foreach( $attributes as $attribute => $value ) {
				$attributes_arr["attribute_".$attribute] = $value;
			}
			$data_store   = WC_Data_Store::load( 'product' );
			$variation_id = $data_store->find_matching_product_variation( $product, $attributes_arr);
			
			if ($variation_id && $variation_id != 0) {
				$product_data['vars'] = product_get_variant_line_v2($variation_id);
				$var_product = wc_get_product( $variation_id );
				$var_price = $var_product->get_price();
			} else {
				$product_data['vars'] = '';
			}
		} else {
			$product_data['vars'] = '';
			$variation_id = $cur_product_id; //якщо дефолтних атрибутів немає, ставимо id продукту
			$var_price = $product_data['price']; //якщо дефолтних атрибутів немає, ставимо вартість продукту
		}
		$product_data['var_id'] = $variation_id;
		$product_data['var_price'] = ($var_price != "") ? $var_price : 0;


	} elseif ($is_variation) {
		$product_data['var_id'] = $cur_product_id;
		$product_data['vars'] = product_get_variant_line_v2($cur_product_id);
		$product_data['var_price'] = $product_data['price'];
		
		//$product_data['description'] = ($product_data['description'] <> '') ? $product_data['description'] :  $product->get_description();
		
		//$parent_product   = wc_get_product( $parent_id );	
		//$product_data['gallery_image_ids'] = $parent_product->get_gallery_image_ids();
		
	} else {
		$product_data['var_id'] = $cur_product_id;
		$product_data['vars'] = "";
		$product_data['gallery_image_ids'] = "";
		$product_data['var_price'] = $product_data['price'];
	}
	return $product_data;
	
}


function wpadmin_v2_header_code_analytics() { 

	if (get_option('wpadmin_add_fb') == 'yes' && !empty(get_option('wpadmin_fbid'))) {
?>
	<!-- Meta Pixel Code -->
	<script>
	!function(f,b,e,v,n,t,s)
	{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};
	if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
	n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];
	s.parentNode.insertBefore(t,s)}(window, document,'script',
	'https://connect.facebook.net/en_US/fbevents.js');
	fbq('init', '<?php echo get_option('wpadmin_fbid');?>');
	fbq('track', 'PageView');
	</script>
	<noscript><img height="1" width="1" style="display:none"
	src="https://www.facebook.com/tr?id=<?php echo get_option('wpadmin_fbid');?>&ev=PageView&noscript=1"
	/></noscript>
	<!-- End Meta Pixel Code -->
<?php
	}	
	
	if (get_option('wpadmin_add_ga') == 'yes' && !empty(get_option('wpadmin_ga4mid'))) {
?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo get_option('wpadmin_ga4mid');?>"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', '<?php echo get_option('wpadmin_ga4mid');?>',{ 'debug_mode':true });
	</script>

<?php 
	}
	
	if (get_option('wpadmin_add_tm') == 'yes' && !empty(get_option('wpadmin_tmid'))) {
?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','<?php echo get_option('wpadmin_tmid');?>');</script>
	<!-- End Google Tag Manager -->
<?php 
	}
	
	if (get_option('wpadmin_add_gads') == 'yes' && !empty(get_option('wpadmin_gadsid'))) {
?>
	<!-- Global site tag (gtag.js) - Google Ads: XXXXXXXX -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo get_option('wpadmin_gadsid');?>"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());
	  gtag('config', '{<?php echo get_option('wpadmin_gadsid');?>}');  //Замінити на значення ідентифікатор глобального тегу, який користувач введе у інтерфейсі адмінки CMS.
	</script>

<?php 
	}

}
add_action( 'wp_head', 'wpadmin_v2_header_code_analytics', 10 );


function wpadmin_v2_body_open_code_analytics()  { 
	if (get_option('wpadmin_add_tm') == 'yes' && !empty(get_option('wpadmin_tmid'))) {
?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo get_option('wpadmin_tmid');?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

<?php 
	}
}
add_action('wp_body_open', 'wpadmin_v2_body_open_code_analytics');
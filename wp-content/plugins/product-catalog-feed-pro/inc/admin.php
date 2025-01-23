<?php
require_once("common.php");
global $woocommerce_wpwoof_common;


function wpwoof_delete_feed( $id ) {
        global $wpdb;
        wpwoof_delete_feed_file($id);
        return $wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_id='".(int)$id."' AND option_name LIKE 'wpwoof_feedlist_%'	");
}

function wpwoof_update_feed( $option_value, $option_id,$flag=false,$feed_name='' ) {
        global $wpdb;
        //wpwoof_delete_feed_file($id);
        if(!$flag) {
            if (empty($option_value['status_feed'])) {
                $option_value['status_feed'] = "";
            }
            $tmpdata = wpwoof_get_feed($option_id);

            if (!empty($tmpdata['status_feed'])) {
                $option_value['status_feed'] = $tmpdata['status_feed'];
            }
        }
        
        $option_value = serialize($option_value);
        $table = "{$wpdb->prefix}options";
        $data = array('option_value'=>$option_value);
        if($feed_name) {
            $data['option_name'] = 'wpwoof_feedlist_'. $feed_name;
        }    
        //trace($data,1) ;        
        $where = array('option_id'=>$option_id);

        $sSet = " option_value=%s".(isset( $data['option_name']) ? ", option_name=%s" : "");
        $aData= array();
        array_push($aData,$option_value);
        if(isset( $data['option_name'])){
            array_push($aData,$data['option_name']);
        }
        array_push($aData, $option_id );
        array_push($aData,'wpwoof_feedlist_%' );

        return $wpdb->query( $wpdb->prepare(" update ".$table." SET  ".$sSet." WHERE option_id=%d AND option_name LIKE %s ", $aData ) );

}


function wpwoof_get_feeds( $search = "" ) {

    global $wpdb;
    $option_name="wpwoof_feedlist_";
    if( $search != '' )
    	$option_name = $search;

    $query = $wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE %s;", "%".$option_name."%");
    $result = $wpdb->get_results($query, 'ARRAY_A');

    return $result;

}

function wpwoof_get_feed( $option_id ) {
    global $wpdb;

    $query = $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_id='%s' AND option_name LIKE 'wpwoof_feedlist_%%'", $option_id);
    $result = $wpdb->get_var($query);
    $result = unserialize($result);
    $result['edit_feed'] = $option_id;
    return $result;
}

function wpwoof_feed_dir( $feedname, $file_type = 'xml' ) {
    $feedname = str_replace(' ', '-', trim( $feedname ) );
    $feedname = strtolower($feedname);
    $upload_dir = wp_upload_dir();
    $base = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];

    $path       = $base . "/wpwoof-feed/" . $file_type;
    $baseurl    = $baseurl . "/wpwoof-feed/" . $file_type;
    $file       = $path . "/" . $feedname . "." . $file_type;
    $fileurl    = $baseurl . "/" . $feedname . "." . $file_type;
    
    return array('path'       => $file,
                 'url'        => $fileurl,
                 'file'       => $feedname . '.'.$file_type,
                 'pathtofile' => $path);
}

function wpwoof_create_feed($data){
    global $wpdb;
    //trace($data,1);
    if(!isset($data['feed_type'])) exit();
    $feedname = sanitize_text_field($data['feed_name']);
    $upload_dir = wpwoof_feed_dir($feedname,  $data['feed_type'] == "adsensecustom" ? "csv" : "xml");
    $file = $upload_dir['path'];
    $file_name = $upload_dir['file'];
    
    if( update_option('wpwoof_feedlist_' . $feedname, $data) ){
        $row = $wpdb->get_row("SELECT * FROM ".$wpdb->options." WHERE option_name = 'wpwoof_feedlist_" . $feedname. "'", ARRAY_A);
        if(empty($row['option_id'])){
            $r = $wpdb->get_row("SELECT MAX(option_id)+1 as id from ".$wpdb->options, ARRAY_A);
            $wpdb->query("update ".$wpdb->options." SET option_id='".$r['id']."' where option_name = 'wpwoof_feedlist_" . $feedname. "'");
            if(!isset($data['edit_feed'])) $data['edit_feed'] = $r['id'];
        } elseif(!isset($data['edit_feed'])) $data['edit_feed'] = $row['option_id'];
    }

    $dir_path = str_replace( $file_name, '', $file );
    wpwoof_checkDir($dir_path);
    wpwoof_product_catalog::schedule_feed($data, time());
}

function wpwoof_checkDir($path){
    if (!file_exists($path)) {
       return wp_mkdir_p($path);
    }
    return true;
}

function wpwoof_delete_feed_file($id){
    $option_id = $id;
    $wpwoof_values = wpwoof_get_feed($option_id);
    $feed_name = sanitize_text_field($wpwoof_values['feed_name']);
    $upload_dir = wpwoof_feed_dir($feed_name);
    $file = $upload_dir['path'];

    if( file_exists($file))
        unlink($file);
}

function wpwoof_refresh($message = '') {
    $settings_page = $_SERVER['REQUEST_URI'];
    if ( strpos( $settings_page, '&' ) !== false ) {
        $settings_page = substr( $settings_page, 0, strpos( $settings_page, '&' ) );
    }
    if ( ! empty( $message ) ) {
        $settings_page .= '&show_msg=true&wpwoof_message=' . $message;
    }
    if(!WPWOOF_DEBUG) header("Location:".$settings_page);
}



add_action('wp_ajax_wpwoofgtaxonmy', 'ajax_wpwoofgtaxonmy');
function ajax_wpwoofgtaxonmy(){
    $taxonomyPath = (isset($_POST['taxonomy'])  ?  $_POST['taxonomy'] : null);
    wp_send_json(wpwoof_getTaxonmyByPath($taxonomyPath));
}

function wpwoof_getTaxonmyByPath($taxonomyPath){
    $categories = LazyTaxonomyReader();
    $lvl = 1;
    $result[0] =  $categories['values'];
    $tmpCatLvl = $categories;
    foreach ( explode(' > ',$taxonomyPath) as $value) {
        if(isset($tmpCatLvl[$value])) {
            $result[$lvl++] = $tmpCatLvl[$value]["values"];
            $tmpCatLvl = $tmpCatLvl[$value];
        } else {
            break;
        }
    }
    return $result;
}



function LazyTaxonomyReader() {
    $categories = array();
    $upload_dir = wp_upload_dir();
    $upload_dir['basedir'];
        
    if(file_exists($upload_dir['basedir']. "/wpwoof-feed/google-taxonomy.en.txt") ) {
        $file = $upload_dir['basedir']. "/wpwoof-feed/google-taxonomy.en.txt";
    } else 
        $file = plugin_dir_path(__FILE__) . 'google-taxonomy.en.txt';

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    // remove first line that has version number
    if (substr($lines[0], 0, 1) == '#')
        unset($lines[0]);
    $categories['values'] = array();
    $tcat[0] = $categories;
    foreach ($lines as $line) {
        $tarr = explode(' > ',$line);
        if (count($tarr)>1) { 
        $val = end($tarr);
        unset($tarr[count($tarr)-1]);
        $arpath = '["'.implode('"]["', $tarr).'"]';
        eval("\$categories".$arpath.'["values"][]="'.$val.'";');
        }
        else {
            $categories["values"][]=$tarr[0];
        }
    }
    return $categories;
}

add_action('wp_ajax_wpwoofcategories', 'ajax_wpwoofcategories');
function ajax_wpwoofcategories(){
    wpwoofcategories( $_POST );
    die();
}



function wpwoofcategories_wmpl($options){
    global $sitepress,$wp_version;
    $general_lang=ICL_LANGUAGE_CODE;
    $options = array_merge(array(), $options);
    $aLanguages = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
    $sell_all =  ( !isset($options['feed_name']) && !isset($options['feed_category_all'])    ) ? true : ( isset($options['feed_category_all']) && $options['feed_category_all']=="-1" ? true : false );
   // trace("options['feed_category_all']:".$options['feed_category_all']);
   // trace("sell_all:".$sell_all);
   
    ?><p><b>Please select categories</b></p>
    <p class="description">You can also select multiple categories</p>
    <ul  id="lang_wpwoof_categories">
        <li><input type="checkbox" value="-1" name="feed_category_all" id="feed_category_all" class="feed_category" <?php
            if($sell_all)  echo " checked='checked' "; ?>>
            <label for="feed_category_all">All Categories</label>
        </li>
    <?php
    $array_terms = array();
    foreach($aLanguages as $lang) {
        //   $lang['language_code']; $lang['translated_name'];
        $terms = null;
        $sitepress->switch_lang($lang['language_code']);
        if (version_compare(floatval($wp_version), '4.5', '>=')) {
            $args = array(
                'taxonomy' => array('product_cat'),
                'hide_empty' => false,
               /* 'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'       => 'wpfoof-exclude-category',
                        'value'     => '0',
                        'compare'   => 'LIKE'
                    ),
                    array(
                        'key' => 'wpfoof-exclude-category',
                        'compare' => 'NOT EXISTS' // doesn't work
                    )

                ),*/
                'orderby' => 'name',
                'order' => 'ASC'
            );
            $terms = get_terms($args);
        } else {
            $terms = get_terms('product_cat', 'orderby=name&order=ASC&hide_empty=0');
        }

        if (empty($options['feed_category'])) {
            $options['feed_category'] = array();
            $options['feed_category_all'] = '-1';
            foreach($terms as $key => $term) {
                if( get_term_meta( $term->term_id, 'wpfoof-exclude-category', true )!="on" ) {
                    $options['feed_category'][] = $term->term_id;
                }else{
                    unset($terms[$key]);
                }
            }

        }

        if (count($terms) > 0) {
            foreach ($terms as $_term) {
                $array_terms[] = $_term->slug;
            }
        }


        echo "<li class='language_".$lang['language_code']." language_all'><b><i>".$lang['translated_name']."</i></b></li>";
        foreach ($terms as $key => $term) {
                $haystacks = isset($options['feed_category']) ? $options['feed_category'] : array();
                $cat_key = array_search($term->term_id, $haystacks);
                $cat_id = isset($haystacks[$cat_key]) ? $haystacks[$cat_key] : -1;
                ?>
                <li class="language_<?php echo $lang['language_code']?> language_all">
                    <input type="checkbox" value="<?php echo $term->term_id; ?>" name="feed_category[]"
                           id="feed_category_<?php echo $term->term_id; ?>"
                           class="feed_category" <?php  if($sell_all)  { echo " checked='checked' "; } else { checked($term->term_id, $cat_id, true);} ?>>
                    <label for="<?php echo 'feed_category_' . $term->term_id; ?>"><?php echo $term->name; ?> &nbsp; &nbsp;
                        (<?php echo $term->count; ?>)</label>
                </li>
            <?php
         } ?>
        <?php
    }//foreach($aLanguages as $lang) {

    ?>
    </ul>
    <br>
    <div id="wpwoof-popup-bottom"><a href="#done" class="button button-secondary wpwoof-popup-done">Done</a></div>
    <?php
    $sitepress->switch_lang( $general_lang );
}
function wpwoofcategories( $options = array() ) {
    if (WoocommerceWpwoofCommon::isActivatedWPML()) {
        wpwoofcategories_wmpl($options);
        return;
    }
    global $wp_version;
    $options = array_merge(array(), $options);
?>
    <p><b>Please select categories</b></p>
    <?php
    $terms = null;
    if( version_compare( floatval( $wp_version ), '4.5', '>=' ) ) {
        $args = array(
           'taxonomy'      => array('product_cat'),
           'hide_empty'    => false,
           'meta_query' => array(
               'relation' => 'OR',
               array(
                   'key'       => 'wpfoof-exclude-category',
                   'value'     => 'on',
                   'compare'   => 'NOT LIKE'
               ),
                array(
                    'key' => 'wpfoof-exclude-category',
                    'compare' => 'NOT EXISTS' // doesn't work
                )
            ),
            'orderby'       => 'name',
            'order'         => 'ASC'
        );


        $terms =  get_terms( $args );
    }else{
        $terms =  get_terms( 'product_cat', 'orderby=name&order=ASC&hide_empty=0' );
    }





    if( empty( $options['feed_category'] ) ) {
        $options['feed_category'] = array();
        $options['feed_category_all'] = '-1';
        $options['feed_category'][] = '0';
        foreach($terms as $key => $term) {
            if( get_term_meta( $term->term_id, 'wpfoof-exclude-category', true )!="on" ) {
                $options['feed_category'][] = $term->term_id;
            }else{
                unset($terms[$key]);
            }
        }
        
    }

    ?>
    <p class="description">You can also select multiple categories</p>
    <ul>
        <li><input type="checkbox" value="-1" name="feed_category_all" id="feed_category_all" class="feed_category" <?php checked( -1, (isset($options['feed_category_all']) ? $options['feed_category_all'] : '0'), true); ?>>
        <label for="feed_category_all">All Categories</label></li>
        <?php foreach ($terms as $key => $term) { 
            $haystacks = isset($options['feed_category']) ? $options['feed_category'] : array();
            $cat_key = array_search($term->term_id, $haystacks);
            $cat_id = isset($haystacks[$cat_key]) ? $haystacks[$cat_key] : -1;
            ?>
            <li><input type="checkbox" value="<?php echo $term->term_id; ?>" name="feed_category[]" id="feed_category_<?php echo $term->term_id; ?>" class="feed_category" <?php checked( $term->term_id, $cat_id, true); ?>><label for="<?php echo 'feed_category_' . $term->term_id; ?>"><?php echo $term->name; ?> &nbsp; &nbsp; (<?php echo $term->count; ?>)</label></li> 
        <?php } ?>
    </ul>
    <br>
    <div id="wpwoof-popup-bottom"><a href="#done" class="button button-secondary wpwoof-popup-done">Done</a></div>
        
<?php
}
function wpwoof_create_csv($path, $file, $content, $columns, $info=array()) {
    $info = array_merge(array('delimiter'=>'tab', 'enclosure' => 'double' ), $info);
    if(wpwoof_checkDir($path)) {
        $fp = fopen($file, "w");
        $delimiter = $info['delimiter'];
        if ($delimiter == 'tab') {
            $delimiter = "\t";
        }
        $enclosure = $info['enclosure'];
        if ($enclosure == "double")
            $enclosure = chr(34);
        else if ($enclosure == "single")
            $enclosure = chr(39);
        else
            $enclosure = '"';
        if (!empty($columns) ) {
            $header = array();
            foreach ($columns as $column_name => $value) {
                $header[] = $column_name;
            }
            fputcsv($fp, $header, $delimiter, $enclosure);
        }
        if (!empty($content) ) {
            foreach ($content as $fields) {
                if( count($fields) != count($columns) )
                    continue;
                fputcsv($fp, $fields, $delimiter, $enclosure);
            }
        }
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

if(strpos($_SERVER['REQUEST_URI'], '/edit.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] == 'product' ){
    add_filter( 'manage_edit-product_columns', 'wpwoof_products_columns', 20 );
    function wpwoof_products_columns( $columns_array ) {

        $id = array_search('product_tag',array_keys($columns_array))?:5;
        $id++;
        return array_slice( $columns_array, 0, $id, true )
        + array( 'feed' => 'Feed' ) 
        + array_slice( $columns_array, $id, NULL, true );


    }

    add_action( 'manage_posts_custom_column', 'wpwoof_products_populate_columns' );
    function wpwoof_products_populate_columns( $column_name ) {
        global $wpdb, $product;
        if( $column_name  == 'feed' ) {
            $product_type = version_compare( WC_VERSION, '3.0', '>=' ) ? $product->get_type() : $product->product_type;
            $_var = get_post_meta( get_the_ID(), 'wpfoof-exclude-product', true );
            if (!in_array($product_type, array('variable','variable-subscription'))|| $_var ) {
                echo $_var?'No':'Yes';
            } else {
                $variations = $product->get_children();
                if (!empty($variations)){
                    $query = "SELECT DISTINCT post_id 
                                FROM $wpdb->postmeta
                                WHERE `meta_key` LIKE 'wpfoof-exclude-product' 
                                AND `meta_value` != '0' 
                                AND post_id IN (". implode(", ", $variations).")";

                      $product_qty = $wpdb->query($query);
                      if ($product_qty== count($variations)) {
                          echo 'No';
                      } else {
                          echo 'Yes';
                      }
                      return true;
                }
                echo 'No';

            }

        }

    }
}
 
if(strpos($_SERVER['REQUEST_URI'], '/edit-tags.php') !== false && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat' ){
    add_filter( 'manage_edit-product_cat_columns', 'wpwoof_product_cats_columns', 20 );
    function wpwoof_product_cats_columns( $columns_array ) {

        $id = count($columns_array)-1;
        return array_slice( $columns_array, 0, $id, true )
        + array( 'feed' => 'Feed' ) 
        + array_slice( $columns_array, $id, NULL, true );


    }

    add_action( 'manage_product_cat_custom_column', 'wpwoof_cat_populate_columns', 10, 3);
    function wpwoof_cat_populate_columns( $columns, $column, $term_id ) {
        if( $column  == 'feed' ) {
            echo ( get_term_meta( $term_id, 'wpfoof-exclude-category', true )!="on" ) ?  "Yes":"No";
        }

    }
}
 
if(strpos($_SERVER['REQUEST_URI'], '/edit-tags.php') !== false && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_tag' ){
    add_filter( 'manage_edit-product_tag_columns', 'wpwoof_product_tags_columns', 20 );
    function wpwoof_product_tags_columns( $columns_array ) {

        $id = count($columns_array);
        return array_slice( $columns_array, 0, $id, true )
        + array( 'feed' => 'Feed' ) 
        + array_slice( $columns_array, $id, NULL, true );


    }

    add_action( 'manage_product_tag_custom_column', 'wpwoof_tag_populate_columns', 10, 3);
    function wpwoof_tag_populate_columns( $columns, $column, $term_id ) {
        if( $column  == 'feed' ) {
            echo ( get_term_meta( $term_id, 'wpfoof-exclude-category', true )!="on" ) ?  "Yes":"No";
        }

    }
}
 
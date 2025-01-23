<?php
global $woocommerce_wpwoof_common;
include('feed-manage-list.php');
require_once dirname(__FILE__).'/../../inc/feedfbgooglepro.php';

$myListTable = new Wpwoof_Feed_Manage_list();
$myListTable->prepare_items();

?>
    <script>
        function storeWpWoofdata(){
            var data = jQuery('#iDwpwoofGLS').serialize()+"&action=set_wpwoof_global_data";
            jQuery.fn.saveWPWoofParam( data, function () {
                /*$('#idWpWoofGCats').html($('#feed_google_category').val());*/
            });
        }
        jQuery(function($){
            $('#IDextraGlobal input' ).change(storeWpWoofdata);
            $('#IDextraGlobal select').change(storeWpWoofdata);
        });
    </script>
<div class="wpwoof-content-top wpwoof-box headerManagePage">
    <div a>
        <a class="wpwoof-button wpwoof-button-orange1" id="idWpWoofAddNewFeed" href="#">Create New Feed</a>
    </div>
    <div b>
        <vr></vr>
    </div>
    <div c>
         <a a target="_blank" href="https://www.pixelyoursite.com/product-catalog-for-woocommerce-video-tutorials">VIDEO: Watch these short video tutorials for tips about the plugin.</a>
         <a a target="_blank" href="https://www.pixelyoursite.com/woocommerce-product-catalog-feed-help">Learn how to use the plugin</a>
         <a target="_blank" href="https://www.pixelyoursite.com/facebook-product-catalog-feed">Learn how to create a Facebook Product Catalog</a>
    </div>
</div>

<form id="contact-filter" method="post">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>"/>
	<?php //$myListTable->search_box('search', 'search_id'); ?>
	<!-- Now we can render the completed list table -->
	<?php $myListTable->display() ?>
</form>
<div class="wpwoof-box">
    <form method="post" action="#" id="iDwpwoofGLS">
        <h3>Global Settings:</h3>
        <table class="form-table manage_global_settings_block">
            <tr>
                <th>Regenerate active feeds:</th>
                <td>
                    <?php $current_interval = $woocommerce_wpwoof_common->getInterval();  ?>
                    <select name="wpwoof_schedule" id="wpwoof_schedule" onchange="jQuery.fn.saveWPWoofParam({'action':'set_wpwoof_schedule'});">
                        <?php

                        $intervals = array(
                            /*
                            '604800'    => '1 Week',
                            '86400'     => '24 Hours',
                            '43200'     => '12 Hours',
                            '21600'     => '6 Hours',
                            '3600'      => '1 Hour',
                            '900'       => '15 Minutes',
                            '300'       => '5 Minutes',
                            */
                            '0'         => 'Never',
                            '3600'      => 'Hourly',
                            '86400'     => 'Daily',
                            '43200'     => 'Twice daily',
                            '604800'    => 'Weekly'
                        );
                        foreach($intervals as $interval => $interval_name) {
                            ?><option <?php
                                if($interval==$current_interval OR !$current_interval AND !$interval ) {
                                    echo " selected ";
                                } ?> value="<?php
                                                echo $interval;
                                            ?>"><?php echo $interval_name;
                            ?></option><?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Start regeneration from:</th>
                <td>
                    <input type="time" name="wpwoof_schedule_from" value="<?=get_option('wpwoof_schedule_from',"")?>"
                        onchange="jQuery.fn.saveWPWoofParam({'action':'set_wpwoof_schedule'});">
                </td>
            </tr>
            <tr>
                <th>Global Google Taxonomy:</th>
                <td>
                        <?php
                        $data = $woocommerce_wpwoof_common->getGlobalGoogleCategory();
                        ?>
                        <input class="wpwoof_google_category_g_name" type="hidden" name="feed_google_category" value="<?php echo $data['name']; ?>" />
                        <input type="text"   name="wpwoof_google_category" onchange="storeTaxonomyParams(this);" class="wpwoof_google_category_g" value="" style='display:none;' />
                        <?php
                        $taxSrc = admin_url('admin-ajax.php');
                        $taxSrc = add_query_arg( array( 'action'=>'wpwoofgtaxonmy'), $taxSrc);
                        ?>
                        <script>
                            var WPWOOFpreselect =  '<?php echo $data['name'] ?>';
                            jQuery(function($) {
                                wpwoof_taxonomyPreLoad["<?= empty($data['name'])?'root':$data['name']?>"]=<?=json_encode(wpwoof_getTaxonmyByPath($data['name']))?>;
                                loadTaxomomy(".wpwoof_google_category_g", function(){
                                    var sNames = jQuery('.wpwoof_google_category_g_name').val();
                                    if( WPWOOFpreselect != sNames ) {
                                        jQuery.fn.saveWPWoofParam({
                                            'action': 'set_wpwoof_category',
                                            'wpwoof_feed_google_category': sNames,
                                        }, function () {
                                            WPWOOFpreselect = sNames;
                                        });
                                    }
                                });
                            });
                        </script>
                    </td>
            </tr>
            <tr>
                    <th>Global Image:</th>
                    <td>
                        <!-- input type="button" class="button wpfoof-box-upload-button" value="Upload" / -->
                        <?php
                        $value =  $woocommerce_wpwoof_common->getGlobalImg();
                        $image =  empty($value) ? '' : wp_get_attachment_image( $value, 'full', false, array('style' => 'display:block;/*margin-left:auto*/;margin-right:auto;max-width:30%;height:auto;') );
                        ?>
                        <span class="wrap wpwoof-required-value">
                            <input type='hidden' id='_value-Maine-Img'      name='wpfoof-box-media[Maine-Img]'   value='<?php echo $value?>' />
                            <input type='button' id='Maine-Img'        onclick="jQuery.fn.clickWPfoofClickUpload(this);"     class='button wpfoof-box-upload-button'        value='Upload' />
                            <input type='button' id='Maine-Img-remove' onclick="jQuery.fn.clickWPfoofClickRemove(this);" <?php if(empty($image)) {?>style="display:none;"<?php } ?> class='button wpfoof-box-upload-button-remove' value='Remove' />
                         </span>
                        <span  id='IDprev-Maine-Img' class='image-preview'><?php echo ($image) ? ("<br/><br/>".$image."<br/>") : "" ?></span>
                        <span data-size='1200X628'  id='Maine-Img-alert'></span>
                    </td>
             </tr>
            <tr>
                <td colspan="3"><hr class="wpwoof-break" /></td>
            </tr>
            <tr>
                <th>Brand:</th><td><b>The plugin will fill brand in this order:</b></td>
            </tr>

            <tr><td></td>
                <td>
					<p>Custom Brand. The plugin adds a dedicated Brand field to every product.</p><br>
<?php                  ////////////////////////////////////////////////////////////////// Brand BLOCk  //////////////////////////////////////////////////////////////////////////////////////
     $wpwoof_values    = $woocommerce_wpwoof_common->getGlobalData();
     if (!isset($wpwoof_values['tmp_storage'])) {
         $wpwoof_values['tmp_storage'] = 'disk';
     }
     $attributes       = wpwoof_get_all_attributes();
    $all_fields = wpwoof_get_all_fields();
    $meta_keys = wpwoof_get_product_fields();
    $meta_keys_sort = wpwoof_get_product_fields_sort();
    $oFeed = new FeedFBGooglePro($meta_keys, $meta_keys_sort, $attributes);
//     trace($wpwoof_values,1);
?>

    <p class="p_inline_block stl-facebook stl-google" style="display: inline-block;">This value: </p>
    <select  onchange="storeWpWoofdata();" name="brand[value]"
             class="stl-facebook stl-google wpwoof_mapping wpwoof_mapping_option"  style="display: inline-block;" ><?php
        $html = '';
        $html .= '<optgroup label="">';
        $html .= '<option value="">select</option>';
        $html .= '</optgroup>';


        $html .= '<optgroup label="Global Product Attributes">';
        foreach ($attributes['global'] as $key => $value) {
            if ($key=='product_visibility') continue;
            $html .= '<option value="wpwoofattr_' . $key . '" ' . (isset($wpwoof_values['brand']['value']) ? selected('wpwoofattr_' . $key, $wpwoof_values['brand']['value'], false) : '') . ' >' . $value . '</option>';
        }
        $html .= '</optgroup>';
        if(isset($attributes['pa']) and count($attributes['pa'])) {
            $html .= '<optgroup label="Product Attributes">';
            foreach ($attributes['pa'] as $key => $value) {
                $html .= '<option value="wpwoofattr_' . $key . '" ' . (isset($wpwoof_values['brand']['value']) ? selected('wpwoofattr_' . $key, $wpwoof_values['brand']['value'], false) : '') . ' >' . $value . '</option>';
            }
            $html .= '</optgroup>';
        }
        if(isset($attributes['meta']) and count($attributes['meta'])) {
            $html .= '<optgroup label="Custom Fields">';
            foreach ($attributes['meta'] as $key => $value) {
                $html .= '<option value="wpwoofattr_' . $value . '" ' . (isset($wpwoof_values['brand']['value']) ? selected('wpwoofattr_' . $value, $wpwoof_values['brand']['value'], false) : '') . ' >' . $value . '</option>';
            }
            $html .= '</optgroup>';
        }

        echo $html;
        ?></select><br><br>
        <script>jQuery("select[name='brand[value]']").fastselect();</script>

                <?php
                if ( is_plugin_active( WPWOOF_BRAND_YWBA ) ){
                ?>  <label class="stl-facebook stl-google" >
                            <input onchange="storeWpWoofdata();" name="brand[WPWOOF_BRAND_YWBA]" value="1" type="checkbox" <?php
                            if( !empty($wpwoof_values['brand']['WPWOOF_BRAND_YWBA']) || !isset($wpwoof_values['brand']['WPWOOF_BRAND_YWBA']) ) echo ' checked';
                            ?> />  YITH WooCommerce Brands Add-on plugin detected, use it when possible
                        <br><br></label>
                    <?php
                }
                if ( is_plugin_active( WPWOOF_BRAND_PEWB ) ) {
                    ?> <label class="stl-facebook stl-google" >
                        <input onchange="storeWpWoofdata();" type="checkbox" name="brand[WPWOOF_BRAND_PEWB]" value="1" <?php
                        if( !empty($wpwoof_values['brand']['WPWOOF_BRAND_PEWB']) || !isset($wpwoof_values['brand']['WPWOOF_BRAND_PEWB']) ) echo ' checked';
                        ?> /> Perfect WooCommerce Brands. Use it when possible
                        <br><br></label>
                    <?php
                }
                if ( is_plugin_active( WPWOOF_BRAND_PRWB ) ) {
                    ?> <label class="stl-facebook stl-google">
                        <input onchange="storeWpWoofdata();" type="checkbox" name="brand[WPWOOF_BRAND_PRWB]" value="1" <?php
                        if( !empty($wpwoof_values['brand']['WPWOOF_BRAND_PRWB']) || !isset($wpwoof_values['brand']['WPWOOF_BRAND_PRWB']) ) echo ' checked';
                        ?> /> Premmerce WooCommerce Brands. Use it when possible
                        <br><br></label>
                    <?php
                }
                if ( is_plugin_active( WPWOOF_BRAND_PBFW ) ) {
                    ?> <label class="stl-facebook stl-google" >
                        <input onchange="storeWpWoofdata();" type="checkbox" name="brand[WPWOOF_BRAND_PBFW]" value="1" <?php
                        if( !empty($wpwoof_values['brand']['WPWOOF_BRAND_PBFW']) || !isset($wpwoof_values['brand']['WPWOOF_BRAND_PBFW']) ) echo ' checked';
                        ?> /> Product Brands For WooCommerce. Use it when possible
                        <br><br></label>
                    <?php
                }
                ?>
                 <label class="stl-facebook stl-google" >
                        <input name="brand[autodetect]" type="hidden" value="0" />
                        <input onclick="storeWpWoofdata();" name="brand[autodetect]" type="checkbox" value="1" <?php
                        if( !empty($wpwoof_values['brand']['autodetect']) || !isset($wpwoof_values['brand']['autodetect']) ) echo ' checked';
                        ?>/> Possible "brand" field autodetected. Use it when possible
                     <br><br></label>

                    <p class="p_inline_block stl-facebook stl-google">Use this when brand is missing: </p>
                    <input onchange="storeWpWoofdata();" class="stl-facebook stl-google" name="brand[define]" type="text" value="<?php
                       echo !empty($wpwoof_values['brand']['define']) ? $wpwoof_values['brand']['define'] : get_bloginfo( 'name' ) ;  ?>"/>
<?php   ////////////////////////////////////////////////////////////////// END Brand BLOCk  ////////////////////////////////////////////////////////////////////////////////////// ?>
                </td>
                <td style="padding-bottom: 100px;"><?= isset($all_fields['notoutput']['brand'])?$oFeed->getHelpLinks($all_fields['notoutput']['brand']):''?></td>
            </tr>
            <tr>
                <td colspan="3"><hr class="wpwoof-break" /></td>
            </tr>
            <tr>
                <th>GTIN:</th>
				<td><b>The plugin will fill GTIN in this order:</b></td>
            </tr>
            <tr>
                <th></th>
				<td>
					<p>Custom GTIN. The plugin adds a dedicated GTIN field (If value exists; overrides feed setting).
                    </p><p>Feed settings (If value is set; overrides global setting).</p><br>
					<p style="display: inline-block">This value:</p>
                <?php $value = isset($wpwoof_values['extra']['gtin']['value'])?$wpwoof_values['extra']['gtin']:array('value'=>'','custom_value'=>'');
                echo $oFeed->renderExtraFieldsForMapping('gtin',$value );
                ?>
                    (Optional).
                </td>
					<td style="vertical-align: bottom;padding-bottom: 23px;width: 100px;"><?= isset($all_fields['dashboardRequired']['gtin'])?$oFeed->getHelpLinks($all_fields['dashboardRequired']['gtin']):''?></td>
            </tr>
            <tr>
                <td colspan="3"><hr class="wpwoof-break" /></td>
            </tr>
            <tr>
                <th>MPN:</th>
				<td>
					<b>The plugin will fill MPN in this order:</b>
                </td>
            </tr>
            <tr>
                <td></td>
				<td>
					<p>Custom MPN. The plugin adds a dedicated MPN field (if value exists; overrides feed setting).</p>
                    <p>Feed settings (Іf value is set; overrides global setting).</p><br>
					<p style="display: inline-block">This value:</p>
                    <?php $value = isset($wpwoof_values['extra']['mpn']['value'])?$wpwoof_values['extra']['mpn']:array('value'=>'','custom_value'=>'');
                    echo $oFeed->renderExtraFieldsForMapping('mpn',$value);?>
                    (Required. Not applicable for Reviews for Google Merchant feed).
                </td>
                <td style="vertical-align: bottom;padding-bottom: 23px;"><?= isset($all_fields['dashboardRequired']['mpn'])?$oFeed->getHelpLinks($all_fields['dashboardRequired']['mpn']):''?></td>
            </tr>
            <tr>
                <td colspan="3"><hr class="wpwoof-break" /></td>
            </tr>
            <tr>
                <?php $val = isset($wpwoof_values['extra']['identifier_exists']['custom_value'])? $wpwoof_values['extra']['identifier_exists']['custom_value']:''; ?>
                <th>Identifier exists:</th><td><p style="display: inline-block">This value:</p>
                <select name="extra[identifier_exists][custom_value]" class="wpwoof_mapping wpwoof_mapping_option">
                    <option value="true">select</option>
                    <option <?php selected($val,'yes') ?> value="yes"> Yes</option>
                    <option <?php selected($val,'no') ?> value="no">No</option>
                </select>
                    <input type="hidden" name="extra[identifier_exists][value]" value="custom_value" >
                </td>
                <td><?= isset($all_fields['dashboardRequired']['identifier_exists'])?$oFeed->getHelpLinks($all_fields['dashboardRequired']['identifier_exists']):''?></td>
            </tr>
        </table>
        <table class="form-table product-catalog-feed-pro__settings">
            <tr>
                <td colspan="4">
                    <?php

                    $select_values= $helpLinks = array();
                    foreach ($all_fields['dashboardExtra'] as $key => $value) {
                        if (isset($value['custom']) && !empty($value['custom'])) {
                            $select_values[$key] = $value['custom'];
                        }
                        $helpLinks[$key]= $oFeed->getHelpLinks($value);
                    }
                    ?><hr class="wpwoof-break" />
                    <h3>Map extra fields:</h3>
                    <p>Add extra fields and map them to product attributes or custom fields. You can also edit products or variations and add additional fields.</p>
                </td>
            </tr>
            <?php
            $editorsId4init = array();
            foreach ($wpwoof_values['extra'] as $key => $value):
                if ($value['value']==='' || in_array($key, array('identifier_exists','mpn','gtin')))continue;
                $isCustomTag = isset($value['custom_tag_name'])?true:false;
                ?>

            <tr>
                <td style="width:250px;">
                    <?php if ($isCustomTag){ ?>
                    <input type="text" name="extra[<?=$key?>][custom_tag_name]"  value="<?=$value['custom_tag_name']?>"  style="width: 100%;">
                    <?php } else echo '<b>'.$key.':</b>'; ?>

                </td>
                <td class="input-cell" style="min-width: 550px;">
                    <?php
                    echo $oFeed->renderExtraFieldsForMapping($key, $value);
                    if (isset($select_values[$key])) {
                        echo '<select name="extra['.$key.'][custom_value]" class="catalog_pro_dashboard_select" style="display: '.($value['value']=='custom_value' ?'inline' : "none").';" >';
                        if (isset($select_values[$key])) {
                            foreach ($select_values[$key] as $keySel => $valueSel) {
                                echo '<option value="'.$keySel.'" '.selected($keySel,$value['custom_value'],false).'>'.$valueSel.'</option>';
                            }
                        }
                        echo '</select>';
                    } else {
                        if ($value['value']=='custom_value_editor') {
                            echo '<textarea name="extra['.$key.'][editor_value]" id="wpwoof-editor-'.$key.'" cols="110">'.stripslashes($value['custom_value']).'</textarea>';
                            $editorsId4init[] = $key;
                        } else {
                            echo '<textarea id="wpwoof-editor-'.$key.'" cols="110" style="display: none"></textarea>';
                        }?>

                    <input type="text" name="extra[<?=$key?>][custom_value]" placeholder="Custom value" value="<?=esc_html($value['custom_value'])?>" class="catalog_pro_dashboard_input" style="display: <?=$value['value']=='custom_value' ?'inline' : "none"?>;">
                    <?php }
                    if($isCustomTag):?>
                    <br><br>
						<div class="extra-input__item">
							<input type="checkbox"  name="extra[<?=$key?>][feed_type][facebook]" id="extra[<?=$key?>][feed_type][facebook]" <?php checked( isset($value['feed_type']['facebook']));  ?>>
                            <label for="extra[<?=$key?>][feed_type][facebook]">Facebook</label>&emsp;&emsp;
						</div>
						<div class="extra-input__item">
                     <input type="checkbox" name="extra[<?=$key?>][feed_type][google]" id="extra[<?=$key?>][feed_type][google]" <?php checked( isset($value['feed_type']['google']));  ?>>
                               <label for="extra[<?=$key?>][feed_type][google]">Google Merchant</label>&emsp;&emsp;
						</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="extra[<?=$key?>][feed_type][adsensecustom]" id="extra[<?=$key?>][feed_type][adsensecustom]" <?php checked( isset($value['feed_type']['adsensecustom']));  ?>>
                               <label for="extra[<?=$key?>][feed_type][adsensecustom]">Google Custom Remarketing</label>&emsp;&emsp;
						</div>
						<div class="extra-input__item">
                     <input type="checkbox" name="extra[<?=$key?>][feed_type][pinterest]" id="extra[<?=$key?>][feed_type][pinterest]" <?php checked( isset($value['feed_type']['pinterest']));  ?>>
                               <label for="extra[<?=$key?>][feed_type][pinterest]" >Pinterest</label>
						</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="extra[<?=$key?>][feed_type][tiktok]" id="extra[<?=$key?>][feed_type][tiktok]" <?php checked( isset($value['feed_type']['tiktok']));  ?>>
                               <label for="extra[<?=$key?>][feed_type][tiktok]" >TikTok</label>
					</div>
					<div class="extra-input__item">
                               <br><br>
                     <input type="checkbox" name="extra[<?=$key?>][feed_type][mapping]" id="extra[<?=$key?>][feed_type][mapping]" <?php checked( isset($value['feed_type']['mapping']));  ?>>
                               <label for="extra[<?=$key?>][feed_type][mapping]" >Use for mapping (limited to 100 chars if mapped to custom labels)</label>
					</div>
                               <?php endif?>
                </td>
                <td>
                    <div style="display: inline-block; line-height: 30px;float:right;width: 100px;">
                        <?= isset($all_fields['dashboardExtra'][$key]) ? $oFeed->getHelpLinks($all_fields['dashboardExtra'][$key]) : '' ?>
                    </div>
                    <input type="button" onclick="" class="button remove-extra-field-btn" value="remove" style="float:left;">
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="tr-befor-add-new-field">
                <td colspan="4"><hr class="wpwoof-break" /></td>
            </tr>
            <tr><td colspan="3">
					<div class="catalog_pro_dashboard_extra_field_container">
						<?php
						$oFeed->renderFieldsForDropbox($all_fields['dashboardExtra']);
						?>
					</div>
					<input type="button" id="add-extra-field-btn" class="button" value="Add new field" style="height: 31px;">
                </td>
            </tr>
			<tr><td colspan="3">
					<?php
						if (isset($all_fields['toedittab']['shipping']['desc']))
							echo '* ' . $all_fields['toedittab']['shipping']['desc'] ;
					?>
				</td></tr>
                        <tr>
            <th>Store temporary data to:</th>
                <td>
                    <select  onchange="storeWpWoofdata();" name="tmp_storage" id="tmp_storage">
                        <option <?php selected($wpwoof_values['tmp_storage'],'disk') ?> value="disk">Disk</option>
                        <option <?php selected($wpwoof_values['tmp_storage'],'db') ?> value="db">Database</option>
                    </select>
                </td>
            </tr>
        </table>
    </form>
    <table id="wpwoof-def-extra-row" style="display: none">
                 <tr>
                <td style="width:250px;">

                    <input type="text" name="wpwoof-def[custom_tag_name]" placeholder="Custom field"  style="width: 100%;">
                    <input type="hidden" name="wpwoof-def[input_type]" value="text">
                    <b id="wpwoof-def-title"></b>

                </td>
                <td class="input-cell" style="min-width: 550px;">
                    <?php
                    echo $oFeed->renderExtraFieldsForMapping('wpwoof-def', array());

                    ?><input type="text" name="wpwoof-def[custom_value]" placeholder="Custom value" value="" class="catalog_pro_dashboard_input" style="display: none;">
                    <select name="wpwoof-def[custom_value]" class="catalog_pro_dashboard_select" style="display: none;"></select>
                    <textarea id="wpwoof-editor-def" cols="110" style="display: none"></textarea>
                    <br><br>

					<div class="extra-input__item">
					<input type="checkbox"  name="wpwoof-def[feed_type][facebook]" checked>
                               <label for="wpwoof-def[feed_type][facebook]">Facebook</label>&emsp;&emsp;
					</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="wpwoof-def[feed_type][google]" checked>
                               <label for="wpwoof-def[feed_type][google]">Google Merchant</label>&emsp;&emsp;
					</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="wpwoof-def[feed_type][adsensecustom]" checked>
                               <label for="wpwoof-def[feed_type][adsensecustom]">Google Custom Remarketing</label>
					</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="wpwoof-def[feed_type][pinterest]" checked>
                               <label for="wpwoof-def[feed_type][pinterest]">Pinterest</label>
					</div>
					<div class="extra-input__item">
                     <input type="checkbox" name="wpwoof-def[feed_type][tiktok]" checked>
                               <label for="wpwoof-def[feed_type][tiktok]">TikTok</label>
					</div>
					<div class="extra-input__item">
                               <br><br>
                     <input type="checkbox" name="wpwoof-def[feed_type][mapping]" checked>
                               <label for="wpwoof-def[feed_type][mapping]">Use for mapping (limited to 100 chars if mapped to custom labels)</label>
					</div>
                </td>
                <td>
                    <div class="extra-link-2-wrapper-dashboard" style="display: inline-block; line-height: 30px;float:right;width: 100px;">
						&nbsp;&nbsp;FB | G
					</div>
					<input type="button" onclick="" class="button remove-extra-field-btn" value="remove" style="float:left">
				</td>
            </tr>
    </table>
</div>
<script>
	jQuery("select[name*='extra['][name$='[value]']").fastselect();
	jQuery("select[name*='extra['][name*='[identifier_exists]']").fastselect();
let wpwoof_select_values = <?= json_encode($select_values)?>;
let wpwoof_help_links = <?= json_encode($helpLinks) ?>;
let wpwoof_editorsId4init = <?= json_encode($editorsId4init) ?>;
let wpwoof_current_page = 'dashboard'; </script>

<?php
wp_enqueue_editor();
include('info-settings.php');